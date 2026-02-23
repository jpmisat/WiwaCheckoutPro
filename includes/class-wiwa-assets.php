<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Assets
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_scripts']);
    }

    public function enqueue_custom_scripts()
    {
        // --- ADD TO CART POPUP ASSETS (Global) ---
        if ( !is_admin() ) {
            wp_enqueue_style('wiwa-add-to-cart', WIWA_CHECKOUT_URL . 'assets/css/add-to-cart.css', [], WIWA_CHECKOUT_VERSION);
            wp_enqueue_script('wiwa-add-to-cart', WIWA_CHECKOUT_URL . 'assets/js/add-to-cart.js', ['jquery'], WIWA_CHECKOUT_VERSION, true);
            // Support Multilingual Plugins (WPML, Polylang) for admin-ajax
            $ajax_url = admin_url('admin-ajax.php');
            $lang = apply_filters('wpml_current_language', null);
            if (!$lang && function_exists('pll_current_language')) {
                $lang = pll_current_language();
            }
            if ($lang) {
                $ajax_url = add_query_arg('lang', $lang, $ajax_url);
            }

            wp_localize_script('wiwa-add-to-cart', 'wiwaAjax', [
                'ajax_url' => $ajax_url,
                'nonce'    => wp_create_nonce('wiwa_checkout_nonce'),
                'strings'  => [
                    'processing' => __('Procesando...', 'wiwa-checkout'),
                    'addError'   => __('Error al agregar al carrito.', 'wiwa-checkout'),
                    'connError'  => __('Ocurrió un error de conexión. Intente nuevamente.', 'wiwa-checkout'),
                    'addToCart'  => __('<span class="icon-cart"></span> Agregar al Carrito', 'wiwa-checkout'),
                ]
            ]);
        }

        // --- CART PAGE SPECIFIC ASSETS ---
        if ( !is_admin() && is_cart() ) {
            // 1. Tailwind CSS CDN (cart page only to avoid breaking other pages)
            wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com?plugins=forms,container-queries', [], null, false);

            // 2. Tailwind config with Stitch design tokens (inline after tailwind loads)
            $tw_config = "
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                'wiwa-cream': '#fdfbf7',
                                'wiwa-bg': '#f9f9f9',
                                'wiwa-green': '#1a3c28',
                                'wiwa-green-light': '#2b4c3b',
                                'wiwa-text-gray': '#4b5563',
                                'wiwa-border': '#e5e7eb',
                            },
                            fontFamily: {
                                sans: ['Montserrat', 'Roboto', 'sans-serif'],
                            }
                        }
                    }
                }
            ";
            wp_add_inline_script('tailwindcss', $tw_config);

            // 3. Google Material Symbols (icons used in the design)
            wp_enqueue_style('material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', [], null);

            // 4. Montserrat Font
            wp_enqueue_style('google-montserrat', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap', [], null);

            // 5. Custom cart styles (overrides + Stitch extras)
            wp_enqueue_style('wiwa-cart-styles', WIWA_CHECKOUT_URL . 'assets/css/wiwa-cart-styles.css', [], WIWA_CHECKOUT_VERSION);
        }

        // --- MINI CART / SIDEBAR ASSETS (Global) ---
        if ( !is_admin() ) {
            wp_enqueue_style('wiwa-cart-styles-global', WIWA_CHECKOUT_URL . 'assets/css/wiwa-cart-styles.css', [], WIWA_CHECKOUT_VERSION);
            wp_enqueue_script('wiwa-mini-cart', WIWA_CHECKOUT_URL . 'assets/js/wiwa-mini-cart.js', ['jquery'], WIWA_CHECKOUT_VERSION, true);
            wp_localize_script('wiwa-mini-cart', 'wiwa_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wiwa_checkout_nonce'),
                'strings'  => [
                    'addToCart' => __('Agregar al carrito', 'wiwa-checkout')
                ]
            ]);
        }
    }
}
