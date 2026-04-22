<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Wiwa_Cache_Compat
 *
 * Per-currency Varnish cache strategy for FOX Currency Switcher.
 *
 * Strategy: PER-CURRENCY CACHING — Varnish caches a separate entry
 * for each currency variant, so ALL visitors get fast cached pages.
 * ------------------------------------------------------------------
 *
 * PREREQUISITES (CloudPanel Varnish Settings):
 *   - "currency" must be REMOVED from "Excluded Parameters"
 *     so Varnish includes ?currency=XXX in the cache hash.
 *   - This means /tour-x/ and /tour-x/?currency=EUR are cached
 *     as two separate entries.
 *
 * Works together with currency-links.js (v2.18.5+) which injects
 * ?currency=XXX into all internal links when a non-default currency
 * is active.
 *
 * Pages with cache DISABLED (always dynamic / user-specific):
 *   - Cart, Checkout, My Account (user-specific data)
 *   - admin-ajax.php and wc-ajax requests
 *
 * Pages with PER-CURRENCY CACHE:
 *   - Homepage, all tour/product pages, shop, categories
 *   - /tour-x/              → cached as COP version
 *   - /tour-x/?currency=EUR → cached as EUR version
 *   - /tour-x/?currency=USD → cached as USD version
 *   - Blog, contact, about → cached (single version, no prices)
 *
 * Edge case — user with non-default currency cookie but NO ?currency=
 * param (e.g., typed URL directly, external link):
 *   → PHP redirects to same URL with ?currency=XXX appended
 *   → This ensures Varnish serves the correct cached version
 *
 * How to rollback:
 *   git checkout v2.18.6-safe-rollback
 *   (Re-add "currency" to CloudPanel Excluded Parameters)
 * ------------------------------------------------------------------
 *
 * @since 2.18.3
 * @updated 2.18.7 — Per-currency Varnish caching
 */
class Wiwa_Cache_Compat
{
    /**
     * Initialize cache compatibility hooks.
     */
    public static function init()
    {
        // Redirect users with currency cookie but missing URL param
        add_action('template_redirect', [__CLASS__, 'redirect_currency_from_cookie'], 0);

        // Disable cache only on truly dynamic pages
        add_action('template_redirect', [__CLASS__, 'bypass_dynamic_pages'], 1);

        // Ensure AJAX requests are never cached
        add_action('admin_init', [__CLASS__, 'protect_ajax_from_cache']);

        // Intercept wc-ajax before WooCommerce processes it
        add_action('init', [__CLASS__, 'protect_wc_ajax_from_cache'], 0);

        // Protect admin-ajax early
        add_action('init', [__CLASS__, 'protect_admin_ajax'], 1);
    }

    // -----------------------------------------------------------------
    // Per-currency redirect
    // -----------------------------------------------------------------

    /**
     * If a user has a non-default currency cookie but arrived WITHOUT
     * ?currency= in the URL, redirect to include it.
     *
     * This ensures Varnish serves the correct per-currency cached version
     * instead of the default COP version.
     *
     * Covers edge cases:
     *   - User types URL directly in browser
     *   - External link without ?currency=
     *   - Bookmark without ?currency=
     *
     * Does NOT redirect on:
     *   - Cart, Checkout, My Account (always dynamic, no caching)
     *   - AJAX requests
     *   - POST requests
     *   - Admin pages
     */
    public static function redirect_currency_from_cookie()
    {
        // Skip if not a GET request
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        // Skip admin, AJAX, cron
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        // Skip if ?currency= is already present
        if (isset($_GET['currency'])) {
            return;
        }

        // Skip wc-ajax
        if (isset($_GET['wc-ajax'])) {
            return;
        }

        // Skip dynamic pages (no point redirecting, they bypass cache)
        if (self::is_always_dynamic_page()) {
            return;
        }

        // Check for non-default currency in cookie
        $cookie_currency = self::get_currency_from_cookie();
        if (!$cookie_currency) {
            return; // Default currency, no redirect needed
        }

        // Build redirect URL with ?currency= param
        $redirect_url = add_query_arg('currency', $cookie_currency);

        // Prevent redirect loops
        if (isset($_GET['_wc_redirect'])) {
            return;
        }

        wp_redirect($redirect_url, 302);
        exit;
    }

    // -----------------------------------------------------------------
    // Cache bypass (only truly dynamic pages)
    // -----------------------------------------------------------------

    /**
     * Disable cache ONLY on pages that contain user-specific data
     * and should never be cached regardless of currency.
     */
    public static function bypass_dynamic_pages()
    {
        if (is_admin()) {
            return;
        }

        if (!self::is_always_dynamic_page()) {
            return;
        }

        self::send_no_cache_headers();
    }

    /**
     * Check if the current page is always dynamic (user-specific).
     *
     * @return bool
     */
    private static function is_always_dynamic_page()
    {
        // Cart
        if (function_exists('is_cart') && is_cart()) {
            return true;
        }

        // Checkout
        if (function_exists('is_checkout') && is_checkout()) {
            return true;
        }

        // My Account
        if (function_exists('is_account_page') && is_account_page()) {
            return true;
        }

        // Pages with cart/checkout shortcodes
        global $post;
        if ($post && is_a($post, 'WP_Post')) {
            $shortcodes = [
                'wiwa_checkout_cart',
                'wiwa_checkout_form',
                'woocommerce_cart',
                'woocommerce_checkout',
            ];
            foreach ($shortcodes as $sc) {
                if (has_shortcode($post->post_content, $sc)) {
                    return true;
                }
            }
        }

        // URL-based detection for cart/checkout paths
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $dynamic_paths = [
            '/cart/', '/checkout/', '/finalizar-compra/',
            '/my-account/', '/mi-cuenta/',
            '/en/finalize-purchase/', '/fr/finaliser-lachat/',
        ];
        foreach ($dynamic_paths as $path) {
            if (strpos($uri, $path) !== false) {
                return true;
            }
        }

        return false;
    }

    // -----------------------------------------------------------------
    // Cookie detection
    // -----------------------------------------------------------------

    /**
     * Get the non-default currency from cookies, or null if default.
     *
     * @return string|null Currency code (e.g., "EUR") or null
     */
    private static function get_currency_from_cookie()
    {
        $default = defined('WIWA_DEFAULT_CURRENCY') ? WIWA_DEFAULT_CURRENCY : 'COP';

        $cookie_names = [
            'woocs_current_currency',
            'woocommerce_currency',
        ];

        foreach ($cookie_names as $name) {
            if (isset($_COOKIE[$name]) && !empty($_COOKIE[$name]) && $_COOKIE[$name] !== $default) {
                return sanitize_text_field($_COOKIE[$name]);
            }
        }

        return null;
    }

    // -----------------------------------------------------------------
    // Header management
    // -----------------------------------------------------------------

    /**
     * Send headers that disable Varnish Cache (CloudPanel).
     */
    private static function send_no_cache_headers()
    {
        static $sent = false;
        if ($sent || headers_sent()) {
            return;
        }
        $sent = true;

        header('X-Cache-Lifetime: 0', true);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT', true);
        header('Vary: Cookie', false);
    }

    // -----------------------------------------------------------------
    // AJAX protection
    // -----------------------------------------------------------------

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
     * Protect admin-ajax.php early (init hook).
     */
    public static function protect_admin_ajax()
    {
        if (is_admin()) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, 'admin-ajax.php') !== false) {
            self::send_no_cache_headers();
        }
    }

    /**
     * Protect wc-ajax requests from Varnish caching.
     */
    public static function protect_wc_ajax_from_cache()
    {
        if (isset($_GET['wc-ajax'])) {
            self::send_no_cache_headers();
        }
    }
}
