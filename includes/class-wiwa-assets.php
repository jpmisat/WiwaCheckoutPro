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
                    'processing'     => __('Processing...', 'wiwa-checkout'),
                    'addError'       => __('Error adding to cart.', 'wiwa-checkout'),
                    'connError'      => __('A connection error occurred. Try again.', 'wiwa-checkout'),
                    'addToCart'      => __('<span class="icon-cart"></span> Add to Cart', 'wiwa-checkout'),
                    'addedToCart'    => __('¡Agregado al carrito!', 'wiwa-checkout'),
                    'viewCart'       => __('Ver carrito', 'wiwa-checkout'),
                    'bookNow'        => __('Reservar ahora', 'wiwa-checkout'),
                    'moreActivities' => __('Más actividades que te pueden gustar', 'wiwa-checkout'),
                ]
            ]);
        }

        // --- CART PAGE SPECIFIC ASSETS ---
        // Load on native WC cart page OR when our custom [wiwa_checkout_cart] shortcode is used
        // Using aggressive multi-fallback detection because is_cart() can fail with Elementor/WPML
        $is_wiwa_cart = false;

        // Method 1: Native WooCommerce detection
        if (function_exists('is_cart') && is_cart()) {
            $is_wiwa_cart = true;
        }

        // Method 2: WIWA_RENDERING_CART constant (set by our shortcode)
        if (!$is_wiwa_cart && defined('WIWA_RENDERING_CART') && WIWA_RENDERING_CART) {
            $is_wiwa_cart = true;
        }

        // Method 3: Match current page ID to WooCommerce cart page ID
        if (!$is_wiwa_cart && function_exists('wc_get_page_id')) {
            $cart_page_id = wc_get_page_id('cart');
            if ($cart_page_id > 0) {
                global $post;
                $current_page_id = is_object($post) ? $post->ID : 0;
                // Also check for WPML translated page IDs
                if ($current_page_id === $cart_page_id) {
                    $is_wiwa_cart = true;
                } elseif (function_exists('icl_object_id')) {
                    // WPML: check if current page is a translation of the cart page
                    $translated_cart_id = icl_object_id($cart_page_id, 'page', true);
                    if ($current_page_id === $translated_cart_id) {
                        $is_wiwa_cart = true;
                    }
                }
            }
        }

        // Method 4: URL slug fallback (catches /carrito/, /cart/, /panier/)
        if (!$is_wiwa_cart && !is_admin()) {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $cart_slugs = ['carrito', 'cart', 'panier', 'warenkorb'];
            foreach ($cart_slugs as $slug) {
                if (preg_match('#/' . $slug . '(/|$|\?)#i', $request_uri)) {
                    $is_wiwa_cart = true;
                    break;
                }
            }
        }

        // Method 5: Shortcode scan in post content
        if (!$is_wiwa_cart && !is_admin()) {
            global $post;
            if ($post && is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'wiwa_checkout_cart')) {
                $is_wiwa_cart = true;
            }
        }

        if ( !is_admin() && $is_wiwa_cart ) {
            // Tailwind CDN has been moved to print_critical_css to guarantee optimal execution order and bypass Rocket Loader completely.
            
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
                    'addToCart' => __('Add to cart', 'wiwa-checkout')
                ]
            ]);
        }
    }
}
