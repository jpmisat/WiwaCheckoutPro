<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Wiwa_Shortcodes
 * 
 * Handles registration of custom shortcodes.
 */
class Wiwa_Shortcodes {

    public static function init() {
        add_shortcode('dynamic_deposit_currency', [__CLASS__, 'render_dynamic_deposit']);
        add_shortcode('wiwa_google_rating', [__CLASS__, 'render_google_rating']);
    }

    /**
     * Renders the cart shortcode [wiwa_checkout_cart].
     * Delegates to WooCommerce's native [woocommerce_cart] shortcode,
     * which is then intercepted by Wiwa_Cart_Handler::override_cart_templates()
     * to load our custom Stitch-based cart template.
     *
     * Also sets a global flag so Wiwa_Assets knows to enqueue
     * Tailwind and cart-specific assets on non-standard cart pages.
     */
    public static function checkout_cart($atts) {
        // Signal to asset loader that cart assets are needed on this page
        if (!defined('WIWA_RENDERING_CART')) {
            define('WIWA_RENDERING_CART', true);
        }

        // Delegate to WooCommerce's native cart shortcode
        return do_shortcode('[woocommerce_cart]');
    }

    /**
     * Renders the checkout form shortcode [wiwa_checkout_form].
     * Delegates to WooCommerce's native [woocommerce_checkout] shortcode,
     * which is then intercepted by Wiwa_Checkout_Handler to load
     * our custom checkout template with the multi-step flow.
     */
    public static function checkout_form($atts) {
        return do_shortcode('[woocommerce_checkout]');
    }

    /**
     * Renders the thank you page shortcode [wiwa_checkout_thankyou].
     * Displays a branded confirmation message after order placement.
     * Falls back gracefully if no order key is present in the URL.
     */
    public static function checkout_thankyou($atts) {
        $atts = shortcode_atts([
            'order_id' => 0,
        ], $atts, 'wiwa_checkout_thankyou');

        // Try to get order from URL parameters (standard WC flow)
        $order_id = intval($atts['order_id']);
        if (!$order_id && isset($_GET['order-received'])) {
            $order_id = absint($_GET['order-received']);
        }

        // Validate order key if present
        if ($order_id && isset($_GET['key'])) {
            $order = wc_get_order($order_id);
            if (!$order || !hash_equals($order->get_order_key(), wc_clean(wp_unslash($_GET['key'])))) {
                $order_id = 0; // Invalid key, reset
            }
        }

        // If no valid order, show a generic thank you
        if (!$order_id) {
            ob_start();
            ?>
            <div class="wiwa-thankyou-wrapper" style="text-align:center; padding: 60px 20px;">
                <h2><?php esc_html_e('Thank you for your booking!', 'wiwa-checkout'); ?></h2>
                <p><?php esc_html_e('Your adventure awaits. Check your email for confirmation details.', 'wiwa-checkout'); ?></p>
            </div>
            <?php
            return ob_get_clean();
        }

        // Delegate to WooCommerce's native thank you page rendering
        return do_shortcode('[woocommerce_checkout]');
    }

    /**
     * Renders the dynamic deposit currency shortcode.
     * [dynamic_deposit_currency id="optional_product_id"]
     */
    public static function render_dynamic_deposit($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'dynamic_deposit_currency');

        $product_id = intval($atts['id']);
        
        // If no ID is provided, try to get it from the current global post
        if (!$product_id) {
            global $post;
            if ($post && $post->post_type === 'product') {
                $product_id = $post->ID;
            }
        }

        if (!$product_id) {
            return '';
        }

        $product = wc_get_product($product_id);
        if (!$product || $product->get_type() !== 'ovatb_tour') {
            return '';
        }

        // Get regular price and deposit settings from Ova Tour Booking
        $regular_price = get_post_meta($product_id, '_regular_price', true);
        $deposit_type = get_post_meta($product_id, 'ovatb_deposit_type', true);
        $deposit_amount = get_post_meta($product_id, 'ovatb_deposit_amount', true);

        // Get active currency code (e.g. USD, COP, EUR)
        $currency_code = Wiwa_FOX_Integration::get_current_currency();

        // If no deposit is configured, display the regular "From" price
        if (!$regular_price || $deposit_type === 'none' || empty($deposit_amount)) {
            $formatted_price = Wiwa_FOX_Integration::format_price($regular_price);
            
            ob_start();
            ?>
            <span class="wiwa-dynamic-deposit">
                <?php printf(esc_html__('From %s', 'wiwa-checkout'), $formatted_price); ?>
                <span class="wiwa-dynamic-deposit-currency"><?php echo esc_html($currency_code); ?></span>
            </span>
            <?php
            return ob_get_clean();
        }

        $deposit_value = 0;
        if ($deposit_type === 'percent') {
            $deposit_value = floatval($regular_price) * (floatval($deposit_amount) / 100);
        } else {
            $deposit_value = floatval($deposit_amount);
        }

        // Apply WOOCS conversion and formatting
        $formatted_deposit = Wiwa_FOX_Integration::format_price($deposit_value);

        ob_start();
        ?>
        <span class="wiwa-dynamic-deposit">
            <?php printf(esc_html__('Book from %s', 'wiwa-checkout'), $formatted_deposit); ?>
            <span class="wiwa-dynamic-deposit-currency"><?php echo esc_html($currency_code); ?></span>
        </span>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the dynamic Google Rating shortcode.
     * [wiwa_google_rating]
     */
    public static function render_google_rating() {
        if (!class_exists('Wiwa_Google_Reviews')) {
            return '';
        }

        $rating_data = Wiwa_Google_Reviews::get_rating_data();
        
        // Fallback defaults if API fails or is not configured (Current valid data for Wiwa Tour)
        $rating = !empty($rating_data['rating']) ? $rating_data['rating'] : 4.8;
        $count = !empty($rating_data['count']) ? $rating_data['count'] : 1854;
        
        // Ensure valid ranges
        $rating = max(0, min(5, (float)$rating));
        
        ob_start();
        ?>
        <div class="wiwa-google-reviews-widget" style="display: flex !important; align-items: center !important; flex-wrap: nowrap !important; gap: 8px !important; flex-direction: row !important; white-space: nowrap !important; width: max-content !important; min-width: fit-content !important;">
            <div class="wiwa-stars-container" style="display: inline-flex !important; align-items: center !important; flex-wrap: nowrap !important; flex-shrink: 0 !important;">
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    $fill = 0;
                    if ($rating >= $i) {
                        $fill = 100;
                    } elseif ($rating > ($i - 1) && $rating < $i) {
                        $fill = ($rating - ($i - 1)) * 100;
                    }

                    // Render SVG star with gradient stop for fractional fill
                    $uuid = uniqid('star_');
                    ?>
                    <svg class="wiwa-star" style="display: inline-block !important; vertical-align: middle !important; margin-right: 2px !important; flex-shrink: 0 !important;" viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="grad_<?php echo $uuid; ?>" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="<?php echo $fill; ?>%" stop-color="#FFB900" />
                                <stop offset="<?php echo $fill; ?>%" stop-color="#E0E0E0" />
                            </linearGradient>
                        </defs>
                        <path fill="url(#grad_<?php echo $uuid; ?>)" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                    </svg>
                    <?php
                }
                ?>
            </div>
            <div class="wiwa-reviews-text" style="display: inline-flex !important; align-items: center !important; white-space: nowrap !important; gap: 4px !important; flex-wrap: nowrap !important; flex-shrink: 0 !important; color: #4b5563 !important;">
                <span class="wiwa-rating-score" style="display: inline-block !important; font-weight: 600 !important;"><?php echo number_format($rating, 1, '.', ''); ?></span>
                <span class="wiwa-separator" style="display: inline-block !important;">&bull;</span>
                <span class="wiwa-reviews-link" style="display: inline-block !important; white-space: nowrap !important;">
                    <a href="https://maps.app.goo.gl/sAR8Qj8RStF8uPQeA" target="_blank" rel="noopener noreferrer" style="white-space: nowrap !important; display: inline-block !important; text-decoration: none !important; color: inherit !important;">
                        <u style="text-decoration: underline !important; text-underline-offset: 2px !important;"><?php echo number_format_i18n($count); ?> reseñas</u>
                    </a>
                </span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
