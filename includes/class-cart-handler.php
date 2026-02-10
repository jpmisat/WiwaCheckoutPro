<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Cart_Handler
{

    public function __construct()
    {
        // Override empty cart template with high priority
        add_filter('wc_get_template', [$this, 'override_empty_cart_template'], 999, 5);
        
        // Enqueue side cart script
        add_action('wp_enqueue_scripts', [$this, 'enqueue_side_cart_script']);
    }

    /**
     * Override default WooCommerce cart templates
     */
    public function override_empty_cart_template($located, $template_name, $args, $template_path, $default_path)
    {
        // Nullify other template searches if we match
        if ($template_name === 'cart/cart-empty.php') {
            $custom_template = WIWA_CHECKOUT_PATH . 'templates/cart/empty-cart.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if ($template_name === 'cart/cart.php') {
            $custom_template = WIWA_CHECKOUT_PATH . 'templates/cart/cart.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $located;
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
