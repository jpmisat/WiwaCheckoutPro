<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Wiwa_Cache_Compat
 *
 * Disables Varnish Cache on all pages that display dynamic prices
 * or currency-related content. This ensures FOX Currency Switcher
 * always works correctly regardless of Varnish configuration.
 *
 * Strategy: AGGRESSIVE NO-CACHE for all WooCommerce/price pages.
 * ------------------------------------------------------------------
 * Pages with cache DISABLED (dynamic):
 *   - All WooCommerce product pages (tours, shop, etc.)
 *   - Cart, Checkout, My Account
 *   - Any page with [dynamic_deposit_currency] shortcode
 *   - Any page with ?currency= query string
 *   - admin-ajax.php and wc-ajax requests
 *
 * Pages with cache ENABLED (static):
 *   - Blog posts, static pages without prices
 *   - Homepage (if no WooCommerce products)
 *   - Contact, About, etc.
 * ------------------------------------------------------------------
 *
 * @since 2.18.3
 */
class Wiwa_Cache_Compat
{
    /**
     * Initialize cache compatibility hooks.
     */
    public static function init()
    {
        // Very early — before any output
        add_action('init', [__CLASS__, 'early_cache_check'], 1);

        // After WordPress knows the query — can detect WooCommerce pages
        add_action('template_redirect', [__CLASS__, 'disable_cache_on_price_pages'], 1);

        // Ensure AJAX/wc-ajax requests are never cached
        add_action('admin_init', [__CLASS__, 'protect_ajax_from_cache']);

        // Intercept wc-ajax before WooCommerce processes it
        add_action('init', [__CLASS__, 'protect_wc_ajax_from_cache'], 0);
    }

    // -----------------------------------------------------------------
    // Main cache control
    // -----------------------------------------------------------------

    /**
     * Early check on 'init' — catches obvious cases before WordPress
     * even processes the main query.
     */
    public static function early_cache_check()
    {
        if (is_admin()) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Always bypass cache for currency query strings
        if (isset($_GET['currency']) && !empty($_GET['currency'])) {
            self::send_no_cache_headers();
            return;
        }

        // Always bypass for admin-ajax.php
        if (strpos($uri, 'admin-ajax.php') !== false) {
            self::send_no_cache_headers();
            return;
        }

        // Always bypass for wc-ajax requests (cart fragments, add-to-cart, etc.)
        if (isset($_GET['wc-ajax']) || strpos($uri, 'wc-ajax=') !== false) {
            self::send_no_cache_headers();
            return;
        }

        // Bypass for non-default currency cookie (user already switched)
        if (self::has_non_default_currency_cookie()) {
            self::send_no_cache_headers();
            return;
        }

        // Bypass for common WooCommerce URL patterns
        $wc_paths = [
            '/cart/',
            '/checkout/',
            '/finalizar-compra/',
            '/my-account/',
            '/mi-cuenta/',
            '/en/finalize-purchase/',
            '/fr/finaliser-lachat/',
        ];

        foreach ($wc_paths as $path) {
            if (strpos($uri, $path) !== false) {
                self::send_no_cache_headers();
                return;
            }
        }
    }

    /**
     * On 'template_redirect': WordPress knows what page we're on.
     * Disable cache on ALL WooCommerce pages and any page that
     * could display prices or currency-dependent content.
     */
    public static function disable_cache_on_price_pages()
    {
        if (is_admin()) {
            return;
        }

        $should_bypass = false;

        // 1. WooCommerce product pages (single product, shop, categories)
        if (function_exists('is_product') && is_product()) {
            $should_bypass = true;
        }

        if (function_exists('is_shop') && is_shop()) {
            $should_bypass = true;
        }

        if (function_exists('is_product_category') && is_product_category()) {
            $should_bypass = true;
        }

        if (function_exists('is_product_tag') && is_product_tag()) {
            $should_bypass = true;
        }

        // 2. WooCommerce utility pages
        if (function_exists('is_cart') && is_cart()) {
            $should_bypass = true;
        }

        if (function_exists('is_checkout') && is_checkout()) {
            $should_bypass = true;
        }

        if (function_exists('is_account_page') && is_account_page()) {
            $should_bypass = true;
        }

        // 3. OvaTourBooking single tour pages (custom post type)
        if (is_singular('ovatb_tour')) {
            $should_bypass = true;
        }

        // 4. Any page with our dynamic_deposit_currency shortcode
        if (!$should_bypass) {
            global $post;
            if ($post && is_a($post, 'WP_Post')) {
                if (
                    has_shortcode($post->post_content, 'dynamic_deposit_currency') ||
                    has_shortcode($post->post_content, 'wiwa_checkout_cart') ||
                    has_shortcode($post->post_content, 'wiwa_checkout_form') ||
                    has_shortcode($post->post_content, 'woocommerce_cart') ||
                    has_shortcode($post->post_content, 'woocommerce_checkout')
                ) {
                    $should_bypass = true;
                }
            }
        }

        // 5. Any page using Elementor that has WooCommerce widgets
        // (catch-all for WooCommerce pages built with page builders)
        if (!$should_bypass && function_exists('is_woocommerce') && is_woocommerce()) {
            $should_bypass = true;
        }

        // 6. Any page with ?currency= query string
        if (isset($_GET['currency']) && !empty($_GET['currency'])) {
            $should_bypass = true;
        }

        // 7. User has non-default currency selected (via cookie)
        if (self::has_non_default_currency_cookie()) {
            $should_bypass = true;
        }

        if ($should_bypass) {
            self::send_no_cache_headers();
        }
    }

    // -----------------------------------------------------------------
    // Detection helpers
    // -----------------------------------------------------------------

    /**
     * Check if the user has a non-default currency selected via cookie.
     *
     * @return bool
     */
    private static function has_non_default_currency_cookie()
    {
        $default = defined('WIWA_DEFAULT_CURRENCY') ? WIWA_DEFAULT_CURRENCY : 'COP';

        $cookie_names = [
            'woocs_current_currency',
            'woocommerce_currency',
        ];

        foreach ($cookie_names as $name) {
            if (isset($_COOKIE[$name]) && !empty($_COOKIE[$name]) && $_COOKIE[$name] !== $default) {
                return true;
            }
        }

        return false;
    }

    // -----------------------------------------------------------------
    // Header management
    // -----------------------------------------------------------------

    /**
     * Send headers that disable Varnish Cache (CloudPanel).
     * X-Cache-Lifetime: 0 tells CloudPanel's Varnish to bypass cache.
     */
    private static function send_no_cache_headers()
    {
        static $sent = false;
        if ($sent || headers_sent()) {
            return;
        }
        $sent = true;

        // CloudPanel-specific: 0 = bypass Varnish completely
        header('X-Cache-Lifetime: 0', true);

        // Standard no-cache headers
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT', true);

        // Vary by cookie so CDN/proxies know response is user-specific
        header('Vary: Cookie', false);
    }

    /**
     * Protect admin-ajax.php from Varnish caching.
     */
    public static function protect_ajax_from_cache()
    {
        if (wp_doing_ajax()) {
            self::send_no_cache_headers();
        }
    }

    /**
     * Protect wc-ajax requests from Varnish caching.
     * These run via /?wc-ajax=action, not through admin-ajax.php.
     */
    public static function protect_wc_ajax_from_cache()
    {
        if (isset($_GET['wc-ajax'])) {
            self::send_no_cache_headers();
        }
    }
}
