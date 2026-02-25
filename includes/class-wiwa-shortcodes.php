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
