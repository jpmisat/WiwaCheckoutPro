<?php
if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_SEO_Schema {

    public static function init() {
        add_action('wp_head', [__CLASS__, 'render_tour_schema']);
    }

    public static function render_tour_schema() {
        // Only output on single product pages
        if (!is_product()) {
            return;
        }

        global $post;
        if (!$post || $post->post_type !== 'product') {
            return;
        }

        $product = wc_get_product($post->ID);
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
            'description' => wp_strip_all_tags($post->post_excerpt ?: $post->post_content),
            'image'       => wp_get_attachment_url($product->get_image_id()),
            'url'         => get_permalink($post->ID),
        ];

        // Ensure we always have some description
        if (empty($schema['description'])) {
            $schema['description'] = $schema['name'] . ' - Wiwa Tours';
        }

        // Offers / Price
        $price = get_post_meta($post->ID, '_regular_price', true);
        if (empty($price)) {
            $price = get_post_meta($post->ID, 'ovatb_deposit_amount', true); // Fallback to deposit if regular not set
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
                'url'           => get_permalink($post->ID),
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
