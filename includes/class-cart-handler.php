<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Cart_Handler
{

    public function __construct()
    {
        // Override empty cart and main cart templates with highest possible priority
        add_filter('wc_get_template', [$this, 'override_cart_templates'], PHP_INT_MAX, 5);
        add_filter('woocommerce_locate_template', [$this, 'override_cart_templates'], PHP_INT_MAX, 3);
        
        // Enqueue side cart script
        add_action('wp_enqueue_scripts', [$this, 'enqueue_side_cart_script']);
        
        // Handle custom cart updates (Pax & Guest Info)
        add_action('woocommerce_update_cart_action_cart_updated', [$this, 'handle_custom_cart_updates']);
        
        // Critial CSS injection
        add_action('wp_head', [$this, 'print_critical_css'], 999);

        // EMERGENCY FIX: Sanitize Cart Session on Load to remove bad data causing warnings
        add_action('wp_loaded', [$this, 'sanitize_cart_session_data']);
    }

    /**
     * Emergency Sanitize: Fix malformed ovatb_guest_info in session
     */
    public function sanitize_cart_session_data() {
        if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        $cart = WC()->cart->get_cart();
        $changes = false;

        foreach ( $cart as $key => $item ) {
            if ( isset( $item['ovatb_guest_info'] ) && is_array( $item['ovatb_guest_info'] ) ) {
                $is_corrupted = false;
                foreach ( $item['ovatb_guest_info'] as $guest_type => $guest_data ) {
                    // Level 1: Guest Types (adults, children) - should be array
                    if ( ! is_array( $guest_data ) ) {
                        // FIX: If it's not array, it's definitely broken. Remove it.
                        $is_corrupted = true; 
                        break;
                    }

                    // Level 2: List of guests - should be array of arrays
                    foreach ( $guest_data as $index => $info_fields ) {
                        if ( ! is_array( $info_fields ) ) {
                             // This is the error: foreach() on string
                             // Found a string where an array of fields should be
                             $is_corrupted = true;
                             break 2;
                        }
                    }
                }

                if ( $is_corrupted ) {
                    // Remove the bad data to stop the fatal error/warning
                    unset( WC()->cart->cart_contents[$key]['ovatb_guest_info'] );
                    $changes = true;
                }
            }
        }

        if ( $changes ) {
            WC()->cart->set_session();
        }
    }

    /**
     * Process custom updates (Pax counts + Guest Info) from the cart page
     */
    public function handle_custom_cart_updates($cart_updated)
    {
        if (empty($_POST['cart']) || !is_array($_POST['cart'])) {
            return;
        }

        $cart = WC()->cart->get_cart();
        $changes_made = false;

        foreach ($_POST['cart'] as $cart_item_key => $values) {
            if (!isset($cart[$cart_item_key])) continue;

            $item_changed = false;

            // 1. Handle Pax Counts (numberof_*)
            if (isset($values['guests']) && is_array($values['guests'])) {
                $total_guests = 0;
                
                foreach ($values['guests'] as $guest_name => $qty) {
                    $qty = max(0, intval($qty)); // Sanitize
                    
                    // Update specific guest count
                    if (!isset($cart[$cart_item_key]['numberof_' . $guest_name]) || $cart[$cart_item_key]['numberof_' . $guest_name] != $qty) {
                         $cart[$cart_item_key]['numberof_' . $guest_name] = $qty;
                         $item_changed = true;
                    }
                    
                    $total_guests += $qty;
                }

                if ($item_changed) {
                    $cart[$cart_item_key]['numberof_guests'] = $total_guests;
                }
            }

            // 2. Handle Guest Info (ovatb_guest_info)
            if (isset($values['ovatb_guest_info'])) {
                $guest_info = $values['ovatb_guest_info'];
                
                // STRICT VALIDATION: Only save if structure is correct
                // Expect: [ type => [ index => [ field => value ] ] ]
                if (is_array($guest_info)) {
                    $valid = true;
                    foreach ($guest_info as $g_data) {
                        if (!is_array($g_data)) { $valid = false; break; }
                        foreach ($g_data as $fields) {
                            if (!is_array($fields)) { $valid = false; break; }
                        }
                    }

                    if ($valid) {
                        $cart[$cart_item_key]['ovatb_guest_info'] = $guest_info;
                        $item_changed = true;
                    }
                }
            }

            if ($item_changed) {
                WC()->cart->cart_contents[$cart_item_key] = $cart[$cart_item_key];
                $changes_made = true;
            }
        }
        
        if ($changes_made) {
            WC()->cart->set_session();
        }
    }

    /**
     * Override default WooCommerce cart templates
     */
    public function override_cart_templates($template, $template_name, $template_path)
    {
        // Custom template path
        $custom_cart = WIWA_CHECKOUT_PATH . 'templates/cart/cart.php';
        $custom_empty = WIWA_CHECKOUT_PATH . 'templates/cart/empty-cart.php';

        if ($template_name === 'cart/cart.php' && file_exists($custom_cart)) {
            return $custom_cart;
        }

        if ($template_name === 'cart/cart-empty.php' && file_exists($custom_empty)) {
            return $custom_empty;
        }

        return $template;
    }

    /**
     * Enqueue script for Elementor Side Cart customization
     */
    public function enqueue_side_cart_script()
    {
        if (is_admin()) return;

        wp_enqueue_script(
            'wiwa-side-cart',
            WIWA_CHECKOUT_URL . 'assets/js/side-cart.js',
            ['jquery'],
            WIWA_CHECKOUT_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('wiwa-side-cart', 'wiwaSideCart', [
            'homeUrl' => esc_url(home_url('/tours/')),
            'emptyText' => __('Tu carrito está vacío', 'wiwa-checkout'),
            'emptyDesc' => __('Parece que aún no has agregado ningún tour. ¡Explora nuestros destinos!', 'wiwa-checkout'),
            'btnText' => __('Explorar Tours', 'wiwa-checkout'),
            'iconUrl' => WIWA_CHECKOUT_URL . 'assets/images/empty-cart.svg'
        ]);
        
        // Enqueue Persistence Script (for Main Cart)
        if (is_cart()) {
            wp_enqueue_script(
                'wiwa-cart-persistence',
                WIWA_CHECKOUT_URL . 'assets/js/cart-persistence.js',
                ['jquery'],
                WIWA_CHECKOUT_VERSION,
                true
            );
        }
        
        // Enqueue styles for side cart specific tweaks
        wp_enqueue_style(
            'wiwa-side-cart-css', 
            WIWA_CHECKOUT_URL . 'assets/css/checkout.css', 
            [], 
            WIWA_CHECKOUT_VERSION
        );

    }

    /**
     * Force critical CSS into head to avoid caching/enqueue issues
     */
    public function print_critical_css() {
        if (is_admin()) return;
        ?>
        <style id="wiwa-critical-css">
            /* CRITICAL: Side Cart Z-Index */
            body .elementor-menu-cart__container,
            body .elementor-menu-cart__main,
            body .elementor-menu-cart__wrapper {
                z-index: 2147483647 !important;
                position: fixed !important;
            }
            body .elementor-menu-cart__overlay {
                z-index: 2147483646 !important;
                position: fixed !important;
            }

            /* CRITICAL: WooCommerce Notices */
            .woocommerce-notices-wrapper .woocommerce-message, 
            .woocommerce-notices-wrapper .woocommerce-info, 
            .woocommerce-notices-wrapper .woocommerce-error {
                background-color: #f8f9fa !important;
                color: #374151 !important;
                border-top: 3px solid #1E3A2B !important;
                border-radius: 8px !important;
                padding: 15px 20px !important;
                margin-bottom: 25px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; 
            }
            .woocommerce-notices-wrapper .woocommerce-error {
                border-top-color: #EB5757 !important;
                background-color: #FFF5F5 !important;
                color: #9B2C2C !important;
            }
            .woocommerce-notices-wrapper .woocommerce-info {
                border-top-color: #2F80ED !important;
            }
            .woocommerce-notices-wrapper .woocommerce-message .button {
                float: none !important;
                margin-left: auto !important;
            }
        </style>
        <?php
    }
}
