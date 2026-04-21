<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Wiwa_Cache_Compat
 *
 * Ensures compatibility between FOX Currency Switcher (WOOCS) and
 * CloudPanel's Varnish Cache.
 *
 * Strategy:
 * ------------------------------------------------------------------
 * FOX in QUERY STRING mode (?currency=USD) is the recommended setup.
 * Varnish treats each unique URL as a separate cache entry, so:
 *
 *   /tour-x/              → cached (COP)    ← 95% of traffic
 *   /tour-x/?currency=USD → cached (USD)    ← separate cache entry
 *   /tour-x/?currency=EUR → cached (EUR)    ← separate cache entry
 *
 * Varnish stays FULLY ENABLED for ALL visitors. Each currency gets
 * its own fast cached response. No performance penalty.
 * ------------------------------------------------------------------
 *
 * This class only bypasses cache when:
 * 1. A cookie indicates non-default currency WITHOUT query string
 *    (legacy fallback — only while FOX transitions to query mode)
 * 2. admin-ajax.php requests (must always be dynamic)
 * 3. WooCommerce wc-ajax requests (cart fragments, etc.)
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
        // Send headers before output — priority 1
        add_action('send_headers', [__CLASS__, 'handle_cache_headers'], 1);

        // Ensure AJAX requests are never cached
        add_action('admin_init', [__CLASS__, 'protect_ajax_from_cache']);

        // Early check on 'init' for cookie-only detection (before WOOCS loads)
        add_action('init', [__CLASS__, 'early_currency_cache_check'], 1);
    }

    // -----------------------------------------------------------------
    // Detection methods
    // -----------------------------------------------------------------

    /**
     * Check if a currency query string is present.
     * When ?currency=XXX is in the URL, Varnish will cache this as a
     * SEPARATE entry — so we DO NOT need to bypass cache.
     *
     * @return bool True if ?currency= param is present (any value)
     */
    private static function has_currency_query_string()
    {
        return isset($_GET['currency']) && !empty($_GET['currency']);
    }

    /**
     * Check WOOCS global for non-default currency.
     *
     * @return bool
     */
    private static function is_non_default_currency_from_woocs()
    {
        global $WOOCS;

        if (!isset($WOOCS) || !is_object($WOOCS)) {
            return false;
        }

        $current = $WOOCS->current_currency ?? '';
        $default = $WOOCS->default_currency ?? '';

        if (empty($current) || empty($default)) {
            return false;
        }

        return ($current !== $default);
    }

    /**
     * Check raw cookie for non-default currency (before WOOCS loads).
     *
     * @return bool
     */
    private static function is_non_default_currency_from_cookie()
    {
        $default = defined('WIWA_DEFAULT_CURRENCY') ? WIWA_DEFAULT_CURRENCY : 'COP';

        $cookie_names = [
            'woocs_current_currency',
            'woocommerce_currency',
        ];

        foreach ($cookie_names as $name) {
            if (isset($_COOKIE[$name]) && !empty($_COOKIE[$name])) {
                if ($_COOKIE[$name] !== $default) {
                    return true;
                }
            }
        }

        return false;
    }

    // -----------------------------------------------------------------
    // Cache control hooks
    // -----------------------------------------------------------------

    /**
     * Early check on 'init'.
     *
     * If query string is present → let Varnish cache normally (each URL is unique).
     * If only cookie is present → bypass cache (Varnish can't distinguish by cookie).
     */
    public static function early_currency_cache_check()
    {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        // Query string mode: Varnish handles this automatically via unique URLs.
        // DO NOT send no-cache headers — let Varnish cache each variant.
        if (self::has_currency_query_string()) {
            return;
        }

        // Cookie-only mode (legacy): Varnish ignores cookies, so bypass cache.
        if (self::is_non_default_currency_from_cookie()) {
            self::send_no_cache_headers();
        }
    }

    /**
     * On 'send_headers': same logic as early check, plus WOOCS state.
     */
    public static function handle_cache_headers()
    {
        if (is_admin()) {
            return;
        }

        // Query string mode → Varnish caches each URL variant separately.
        // No need to bypass cache.
        if (self::has_currency_query_string()) {
            return;
        }

        // Cookie-only fallback: bypass cache for non-default currencies
        if (self::is_non_default_currency_from_woocs() || self::is_non_default_currency_from_cookie()) {
            self::send_no_cache_headers();
        }
    }

    // -----------------------------------------------------------------
    // Header management
    // -----------------------------------------------------------------

    /**
     * Send headers that tell Varnish (CloudPanel) to NOT cache this response.
     * Only called when the request has a non-default currency via cookie
     * (not via query string — query string caching is handled by Varnish).
     */
    private static function send_no_cache_headers()
    {
        static $sent = false;
        if ($sent) {
            return;
        }
        $sent = true;

        if (headers_sent()) {
            return;
        }

        // CloudPanel's Varnish respects this header — 0 bypasses cache
        header('X-Cache-Lifetime: 0', true);

        // Standard no-cache headers for browsers and CDN proxies
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT', true);

        // Tell proxies that response varies by cookie
        header('Vary: Cookie', false);
    }

    /**
     * Ensure admin-ajax.php and wc-ajax requests are never cached.
     * These MUST be dynamic for WooCommerce cart fragments and FOX AJAX redraw.
     */
    public static function protect_ajax_from_cache()
    {
        if (wp_doing_ajax()) {
            header('X-Cache-Lifetime: 0', true);
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
            header('Pragma: no-cache', true);
        }
    }
}
