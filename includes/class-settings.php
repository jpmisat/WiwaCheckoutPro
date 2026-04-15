<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Settings
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', ['Wiwa_Admin_Page', 'enqueue_assets']);
    }

    public static function add_admin_menu()
    {
        add_menu_page(
            'Wiwa Checkout',
            'Wiwa Checkout',
            'manage_options',
            'wiwa-checkout-settings',
        ['Wiwa_Admin_Page', 'render'],
            'dashicons-cart',
            56
        );
    }

    public static function register_settings()
    {
        // Tab: General
        register_setting('wiwa_checkout_general', 'wiwa_checkout_enabled');
        register_setting('wiwa_checkout_general', 'wiwa_override_wc_checkout');
        register_setting('wiwa_checkout_general', 'wiwa_override_wc_cart');
        register_setting('wiwa_checkout_general', 'wiwa_checkout_page_id');

        // Tab: GeoIP (Integration)
        register_setting('wiwa_checkout_integrations', 'wiwa_geoip_strategy');
        register_setting('wiwa_checkout_integrations', 'wiwa_maxmind_account_id');
        register_setting('wiwa_checkout_integrations', 'wiwa_maxmind_license_key');
        register_setting('wiwa_checkout_integrations', 'wiwa_geoip_autocomplete_city');
        register_setting('wiwa_checkout_integrations', 'wiwa_geoip_detect_country');

        // Tab: FOX (Integration)
        register_setting('wiwa_checkout_integrations', 'wiwa_show_currency_selector');
        register_setting('wiwa_checkout_integrations', 'wiwa_currency_selector_style');
        register_setting('wiwa_checkout_integrations', 'wiwa_currency_auto_update');

        // Tab: SEO & Reviews
        register_setting('wiwa_checkout_integrations', 'wiwa_google_places_api_key');
        register_setting('wiwa_checkout_integrations', 'wiwa_google_place_id');
    }
}
