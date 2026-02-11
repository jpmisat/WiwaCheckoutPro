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
        
        // Handle custom cart updates (Pax)
        add_action('woocommerce_update_cart_action_cart_updated', [$this, 'handle_pax_updates_in_cart']);
    }

    /**
     * Process custom Pax (guest) updates from the cart page
     */
    public function handle_pax_updates_in_cart($cart_updated)
    {
        if (empty($_POST['cart']) || !is_array($_POST['cart'])) {
            return;
        }

        $cart = WC()->cart->get_cart();

        foreach ($_POST['cart'] as $cart_item_key => $values) {
            if (isset($values['guests']) && is_array($values['guests']) && isset($cart[$cart_item_key])) {
                
                $total_guests = 0;
                $needs_update = false;

                foreach ($values['guests'] as $guest_name => $qty) {
                    $qty = max(0, intval($qty)); // Sanitize
                    
                    // Update specific guest count
                    if (isset($cart[$cart_item_key]['numberof_' . $guest_name]) && $cart[$cart_item_key]['numberof_' . $guest_name] != $qty) {
                         $cart[$cart_item_key]['numberof_' . $guest_name] = $qty;
                         $needs_update = true;
                    } elseif (!isset($cart[$cart_item_key]['numberof_' . $guest_name])) {
                         // If key didn't exist but we have input (unlikely but possible)
                         $cart[$cart_item_key]['numberof_' . $guest_name] = $qty;
                         $needs_update = true;
                    }
                    
                    $total_guests += $qty;
                }

                if ($needs_update) {
                    $cart[$cart_item_key]['numberof_guests'] = $total_guests;
                    
                    // If total guests is 0, maybe remove item? Or let standard validation handle it?
                    // For now, we update the session data.
                    WC()->cart->cart_contents[$cart_item_key] = $cart[$cart_item_key];
                }
            }
        }
        
        // Persist changes to session
        WC()->cart->set_session();
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
            'iconUrl' => WIWA_CHECKOUT_URL . 'assets/images/empty-cart.svg' // We might need to handle the SVG inline or via URL
        ]);
        
        // Enqueue styles for side cart specific tweaks
        wp_enqueue_style(
            'wiwa-side-cart-css', 
            WIWA_CHECKOUT_URL . 'assets/css/checkout.css', 
            [], 
            WIWA_CHECKOUT_VERSION
        );
    }
}
