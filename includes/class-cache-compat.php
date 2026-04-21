<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Wiwa_Cache_Compat
 *
 * Ensures compatibility between FOX Currency Switcher (WOOCS) and
 * CloudPanel's Varnish Cache. By default Varnish caches all pages for 24h
 * and strips cookies, so currency changes are never reflected.
 *
 * This class:
 * 1. Detects when the user has a non-default currency selected
 * 2. Sends X-Cache-Lifetime: 0 header so Varnish bypasses its cache
 * 3. Adds proper Cache-Control headers for CDNs/proxies
 * 4. Ensures admin-ajax.php is never cached (breaks WooCommerce + FOX AJAX)
 *
 * @since 2.18.3
 */
class Wiwa_Cache_Compat
{
    /**
     * Initialize cache compatibility hooks.
     * Must run very early before any output is sent.
     */
    public static function init()
    {
        // Run before any output — priority 1 on 'send_headers'
        add_action('send_headers', [__CLASS__, 'maybe_bypass_cache_for_currency'], 1);

        // Ensure AJAX requests are never cached
        add_action('admin_init', [__CLASS__, 'protect_ajax_from_cache']);

        // Also hook into 'init' as a fallback for pages where send_headers fires too late
        add_action('init', [__CLASS__, 'early_currency_cache_check'], 1);
    }

    /**
     * Check if the current user has a non-default currency selected.
     *
     * @return bool True if currency is non-default and cache should be bypassed.
     */
    private static function is_non_default_currency()
    {
        global $WOOCS;

        // FOX not active — nothing to do
        if (!isset($WOOCS) || !is_object($WOOCS)) {
            return false;
        }

        // Compare current currency with the default
        $current = $WOOCS->current_currency ?? '';
        $default = $WOOCS->default_currency ?? '';

        if (empty($current) || empty($default)) {
            return false;
        }

        return ($current !== $default);
    }

    /**
     * Also check the raw cookie before WOOCS initializes.
     * This catches cases where WOOCS hasn't set $current_currency yet.
     *
     * @return bool
     */
    private static function is_non_default_currency_from_cookie()
    {
        // FOX stores the selected currency in this cookie
        $cookie_names = [
            'woocs_current_currency',
            'woocommerce_currency',
        ];

        foreach ($cookie_names as $name) {
            if (isset($_COOKIE[$name]) && !empty($_COOKIE[$name])) {
                // We need to know the default currency to compare
                // COP is the known default for wiwatour.com
                $default = defined('WIWA_DEFAULT_CURRENCY') ? WIWA_DEFAULT_CURRENCY : 'COP';
                if ($_COOKIE[$name] !== $default) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a non-default currency is requested via query string.
     * FOX can use ?currency=USD mode which is Varnish-friendly since
     * each query string creates a separate cache entry.
     *
     * @return bool
     */
    private static function is_non_default_currency_from_query()
    {
        $default = defined('WIWA_DEFAULT_CURRENCY') ? WIWA_DEFAULT_CURRENCY : 'COP';

        // FOX uses 'currency' as the query string parameter
        if (isset($_GET['currency']) && !empty($_GET['currency'])) {
            return ($_GET['currency'] !== $default);
        }

        return false;
    }

    /**
     * Early check on 'init' — runs before WOOCS might fully initialize.
     * Uses cookie-based and query-string-based detection as fallbacks.
     */
    public static function early_currency_cache_check()
    {
        // Skip admin, AJAX, and REST API (they have their own cache rules)
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (
            self::is_non_default_currency_from_query() ||
            self::is_non_default_currency_from_cookie()
        ) {
            self::send_no_cache_headers();
        }
    }

    /**
     * On 'send_headers', check WOOCS state and bypass cache if needed.
     */
    public static function maybe_bypass_cache_for_currency()
    {
        // Skip admin pages
        if (is_admin()) {
            return;
        }

        if (
            self::is_non_default_currency() ||
            self::is_non_default_currency_from_query() ||
            self::is_non_default_currency_from_cookie()
        ) {
            self::send_no_cache_headers();
        }
    }

    /**
     * Send headers that tell Varnish (CloudPanel) and CDNs to NOT cache this response.
     */
    private static function send_no_cache_headers()
    {
        // Prevent duplicate header sending
        static $sent = false;
        if ($sent) {
            return;
        }
        $sent = true;

        // Don't send headers if they've already been sent (too late)
        if (headers_sent()) {
            return;
        }

        // CloudPanel's Varnish respects this header — setting to 0 bypasses cache
        header('X-Cache-Lifetime: 0', true);

        // Standard cache-control headers for browsers and CDN proxies
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT', true);

        // Tell proxies that response varies by cookie
        header('Vary: Cookie', false); // false = append, don't replace
    }

    /**
     * Ensure admin-ajax.php requests are never cached.
     * Varnish was caching GET requests to admin-ajax.php with max-age=86400.
     */
    public static function protect_ajax_from_cache()
    {
        if (wp_doing_ajax()) {
            // These headers prevent Varnish from caching AJAX responses
            header('X-Cache-Lifetime: 0', true);
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true);
            header('Pragma: no-cache', true);
        }
    }
}
