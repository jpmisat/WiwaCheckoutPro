<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Wiwa_Cache_Compat
 *
 * Smart Varnish cache compatibility for FOX Currency Switcher.
 *
 * Strategy: CACHE-FRIENDLY — Let Varnish cache as much as possible,
 * only bypass when absolutely necessary.
 * ------------------------------------------------------------------
 * Works together with currency-links.js (v2.18.5+) which injects
 * ?currency=XXX into all internal links when a non-default currency
 * is active. This lets Varnish's "Excluded Parameters" feature
 * handle the bypass automatically.
 *
 * Pages with cache DISABLED (always dynamic):
 *   - Cart, Checkout, My Account (user-specific data)
 *   - admin-ajax.php and wc-ajax requests
 *   - Requests with ?currency=XXX query string (Varnish excluded param)
 *   - Fallback: user has non-default currency cookie but no ?currency= param
 *
 * Pages with cache ENABLED (Varnish serves from cache):
 *   - Homepage
 *   - All tour/product pages (WITHOUT ?currency= param)
 *   - Blog, contact, about, etc.
 *   - Shop, product categories, product tags
 *
 * How it works end-to-end:
 *   1. User visits site → COP → Varnish serves cached page (FAST)
 *   2. User selects EUR → page reloads with ?currency=EUR
 *   3. Varnish bypasses (excluded param) → PHP renders EUR prices
 *   4. currency-links.js updates ALL links to include ?currency=EUR
 *   5. User clicks a tour → navigates with ?currency=EUR → bypass
 *   6. User returns to COP → no param → Varnish serves cached page
 * ------------------------------------------------------------------
 *
 * @since 2.18.3
 * @updated 2.18.6 — Switched from aggressive to smart cache strategy
 */
class Wiwa_Cache_Compat
{
    /**
     * Initialize cache compatibility hooks.
     */
    public static function init()
    {
        // Very early — catch AJAX and obvious cases before output
        add_action('init', [__CLASS__, 'early_cache_check'], 1);

        // After WordPress knows the query — only bypass truly dynamic pages
        add_action('template_redirect', [__CLASS__, 'smart_cache_control'], 1);

        // Ensure AJAX requests are never cached
        add_action('admin_init', [__CLASS__, 'protect_ajax_from_cache']);

        // Intercept wc-ajax before WooCommerce processes it
        add_action('init', [__CLASS__, 'protect_wc_ajax_from_cache'], 0);
    }

    // -----------------------------------------------------------------
    // Main cache control
    // -----------------------------------------------------------------

    /**
     * Early check on 'init' — catches requests that should NEVER be cached
     * regardless of what page WordPress resolves.
     */
    public static function early_cache_check()
    {
        if (is_admin()) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // 1. Always bypass for ?currency=XXX query string
        //    (Varnish excluded params should handle this too, but belt + suspenders)
        if (isset($_GET['currency']) && !empty($_GET['currency'])) {
            self::send_no_cache_headers();
            return;
        }

        // 2. Always bypass for admin-ajax.php
        if (strpos($uri, 'admin-ajax.php') !== false) {
            self::send_no_cache_headers();
            return;
        }

        // 3. Always bypass for wc-ajax requests (cart fragments, etc.)
        if (isset($_GET['wc-ajax']) || strpos($uri, 'wc-ajax=') !== false) {
            self::send_no_cache_headers();
            return;
        }

        // 4. Bypass for WooCommerce checkout/cart URL patterns
        //    These are always user-specific and should never be cached.
        $always_dynamic_paths = [
            '/cart/',
            '/checkout/',
            '/finalizar-compra/',
            '/my-account/',
            '/mi-cuenta/',
            '/en/finalize-purchase/',
            '/fr/finaliser-lachat/',
        ];

        foreach ($always_dynamic_paths as $path) {
            if (strpos($uri, $path) !== false) {
                self::send_no_cache_headers();
                return;
            }
        }

        // 5. FALLBACK: User has non-default currency cookie but arrived
        //    WITHOUT ?currency= param (e.g. typed URL directly, or
        //    a link from external site without the param).
        //    This is rare but important for consistency.
        if (self::has_non_default_currency_cookie()) {
            self::send_no_cache_headers();
            return;
        }
    }

    /**
     * On 'template_redirect': WordPress knows the page type.
     * Only disable cache for transactional pages that contain
     * user-specific data (cart contents, checkout forms, account info).
     *
     * Tour pages, shop pages, and product pages are NOW CACHEABLE
     * because currency-links.js handles the ?currency= propagation.
     */
    public static function smart_cache_control()
    {
        if (is_admin()) {
            return;
        }

        $should_bypass = false;

        // 1. Cart — always dynamic (user-specific cart contents)
        if (function_exists('is_cart') && is_cart()) {
            $should_bypass = true;
        }

        // 2. Checkout — always dynamic (user-specific order data)
        if (function_exists('is_checkout') && is_checkout()) {
            $should_bypass = true;
        }

        // 3. My Account — always dynamic (user-specific)
        if (function_exists('is_account_page') && is_account_page()) {
            $should_bypass = true;
        }

        // 4. Pages with our cart/checkout shortcodes
        if (!$should_bypass) {
            global $post;
            if ($post && is_a($post, 'WP_Post')) {
                if (
                    has_shortcode($post->post_content, 'wiwa_checkout_cart') ||
                    has_shortcode($post->post_content, 'wiwa_checkout_form') ||
                    has_shortcode($post->post_content, 'woocommerce_cart') ||
                    has_shortcode($post->post_content, 'woocommerce_checkout')
                ) {
                    $should_bypass = true;
                }
            }
        }

        // 5. ?currency= param (redundant with early_cache_check, but safe)
        if (isset($_GET['currency']) && !empty($_GET['currency'])) {
            $should_bypass = true;
        }

        // 6. Non-default currency cookie without ?currency= param
        if (self::has_non_default_currency_cookie()) {
            $should_bypass = true;
        }

        // ----------------------------------------------------------------
        // NOTE: The following are now CACHEABLE (removed from bypass):
        //   - is_product()           → Tour detail pages
        //   - is_shop()              → Shop/tours archive
        //   - is_product_category()  → Tour categories
        //   - is_product_tag()       → Tour tags
        //   - is_singular('ovatb_tour') → OvaTourBooking pages
        //   - is_woocommerce()       → General WooCommerce pages
        //
        // These pages show COP prices from Varnish cache by default.
        // When a user selects EUR, currency-links.js adds ?currency=EUR
        // to all links, causing Varnish to bypass cache for those requests.
        // ----------------------------------------------------------------

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
