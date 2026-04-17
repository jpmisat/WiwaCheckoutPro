<?php
if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_SEO_Schema {

    public static function init() {
        add_action('wp_head', [__CLASS__, 'render_tour_schema']);

        // Inject aggregateRating into Rank Math's Product schema to fix
        // "Falta el campo aggregateRating" warning in Rich Results Test
        add_filter('rank_math/json_ld', [__CLASS__, 'enrich_rankmath_product_schema'], 99, 2);

        // Remove WooCommerce's default structured data on tour pages
        // to avoid duplicate/conflicting Product schemas
        add_action('wp_head', [__CLASS__, 'remove_wc_structured_data'], 1);
    }

    /**
     * Remove WooCommerce's default structured data output on tour product pages.
     * This prevents a third competing Product schema from being generated.
     */
    public static function remove_wc_structured_data() {
        if (!is_singular('product')) {
            return;
        }

        $post_id = get_queried_object_id();
        $product = $post_id ? wc_get_product($post_id) : null;

        if ($product && $product->get_type() === 'ovatb_tour') {
            // Remove WooCommerce structured data output
            remove_action('wp_footer', ['WC_Structured_Data', 'output_structured_data'], 10);

            // Also try to remove via the singleton instance
            if (function_exists('WC') && isset(WC()->structured_data)) {
                remove_action('wp_footer', [WC()->structured_data, 'output_structured_data'], 10);
                remove_action('woocommerce_single_product_summary', [WC()->structured_data, 'generate_product_data'], 60);
            }
        }
    }

    /**
     * Inject aggregateRating into Rank Math's Product schema.
     * Rank Math generates a Product node in its @graph but without review data.
     */
    public static function enrich_rankmath_product_schema($data, $json_ld) {
        if (!is_singular('product')) {
            return $data;
        }

        // Get Google Rating Data
        $rating_data = false;
        if (class_exists('Wiwa_Google_Reviews')) {
            $rating_data = Wiwa_Google_Reviews::get_rating_data();
        }

        if (!$rating_data || empty($rating_data['rating']) || empty($rating_data['count']) || $rating_data['count'] <= 0) {
            return $data;
        }

        $aggregate = [
            '@type'       => 'AggregateRating',
            'ratingValue' => $rating_data['rating'],
            'reviewCount' => $rating_data['count'],
            'bestRating'  => '5',
            'worstRating' => '1'
        ];

        // Inject into any Product entity found in Rank Math's output
        foreach ($data as $key => &$entity) {
            // Direct Product type
            if (isset($entity['@type']) && (
                $entity['@type'] === 'Product' ||
                (is_array($entity['@type']) && in_array('Product', $entity['@type']))
            )) {
                if (!isset($entity['aggregateRating'])) {
                    $entity['aggregateRating'] = $aggregate;
                }
            }

            // Inside @graph array
            if ($key === '@graph' && is_array($entity)) {
                foreach ($entity as &$node) {
                    if (isset($node['@type']) && (
                        $node['@type'] === 'Product' ||
                        (is_array($node['@type']) && in_array('Product', $node['@type']))
                    )) {
                        if (!isset($node['aggregateRating'])) {
                            $node['aggregateRating'] = $aggregate;
                        }
                    }
                }
                unset($node);
            }
        }
        unset($entity);

        return $data;
    }

    public static function render_tour_schema() {
        // Only output on single product pages
        if (!is_singular('product')) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product || $product->get_type() !== 'ovatb_tour') {
            return;
        }

        // Get Google Rating Data
        $rating_data = false;
        if (class_exists('Wiwa_Google_Reviews')) {
            $rating_data = Wiwa_Google_Reviews::get_rating_data();
        }

        // Setup base schema (TouristTrip & Product overlay to make WooCommerce/Google rich snippets happy)
        $schema = [
            '@context'    => 'https://schema.org/',
            '@type'       => ['Product', 'TouristTrip'],
            'name'        => $product->get_name(),
            'description' => wp_strip_all_tags(has_excerpt($post_id) ? get_the_excerpt($post_id) : get_post_field('post_content', $post_id)),
            'image'       => wp_get_attachment_url($product->get_image_id()),
            'url'         => get_permalink($post_id),
        ];

        // Ensure we always have some description
        if (empty($schema['description'])) {
            $schema['description'] = $schema['name'] . ' - Wiwa Tours';
        }

        // Offers / Price
        $price = get_post_meta($post_id, '_regular_price', true);
        if (empty($price)) {
            $price = get_post_meta($post_id, 'ovatb_deposit_amount', true); // Fallback to deposit if regular not set
        }

        if ($price) {
            $currency = 'COP';
            if (class_exists('Wiwa_FOX_Integration')) {
                $currency = Wiwa_FOX_Integration::get_current_currency();
            }

            $schema['offers'] = [
                '@type'         => 'Offer',
                'priceCurrency' => $currency,
                'price'         => floatval($price),
                'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url'           => get_permalink($post_id),
                'seller'        => [
                    '@type' => 'Organization',
                    'name'  => 'Wiwa Tours'
                ]
            ];
        }

        // Aggregate Rating from Google Places
        if ($rating_data && isset($rating_data['rating']) && isset($rating_data['count']) && $rating_data['count'] > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $rating_data['rating'],
                'reviewCount' => $rating_data['count'],
                'bestRating'  => '5',
                'worstRating' => '1'
            ];
        }

        // Print Schema
        echo "\n<!-- Wiwa Tour Checkout Pro: Dynamic JSON-LD Schema -->\n";
        echo '<script type="application/ld+json">' . wp_json_encode($schema) . "</script>\n";
    }
}
