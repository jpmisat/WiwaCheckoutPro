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
}
