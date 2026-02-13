<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Checkout_Handler
{

    public function __construct()
    {
        add_shortcode('wiwa_checkout', [$this, 'render_checkout']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX: Save Step 1 Data
        add_action('wp_ajax_wiwa_update_order_data', [$this, 'handle_checkout_step_1']);
        add_action('wp_ajax_nopriv_wiwa_update_order_data', [$this, 'handle_checkout_step_1']);

        // Hook to save session data to Valid Order
        add_action('woocommerce_checkout_create_order', [$this, 'save_custom_data_to_order'], 10, 2);

        // Override standard checkout when option is enabled
        if (get_option('wiwa_checkout_enabled')) {
            add_filter('the_content', [$this, 'replace_checkout_content']);
            add_action('template_redirect', [$this, 'redirect_cart_to_checkout']);

            // Customize fields classes based on configuration
            add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields'], 99);
        }
    }

    /**
     * AJAX Handler: Save Checkout Step 1 Data to Session
     */
    public function handle_checkout_step_1()
    {
        check_ajax_referer('wiwa_checkout_nonce', 'nonce');

        $data = $_POST;
        $guest_data = [];

        // Save standard billing fields to WC Session customer
        // This ensures they pre-fill if user reloads
        if (!empty($data['billing_first_name'])) WC()->customer->set_billing_first_name(sanitize_text_field($data['billing_first_name']));
        if (!empty($data['billing_last_name']))  WC()->customer->set_billing_last_name(sanitize_text_field($data['billing_last_name']));
        if (!empty($data['billing_email']))      WC()->customer->set_billing_email(sanitize_email($data['billing_email']));
        if (!empty($data['billing_phone']))      WC()->customer->set_billing_phone(sanitize_text_field($data['billing_phone']));
        if (!empty($data['billing_country']))    WC()->customer->set_billing_country(sanitize_text_field($data['billing_country']));
        
        // Save Custom Guest Data to Session
        // We look for any keys starting with 'guest_' or inside an array
        // Assuming the form sends data like guest[0][name], etc OR flat keys.
        // Based on previous context, use 'wiwa_guest_data' session key.
        
        // Store the specific POST data relevant to us
        WC()->session->set('wiwa_checkout_data', $data);
        WC()->session->save_data();

        wp_send_json_success(['message' => 'Datos guardados']);
    }

    /**
     * Save Session Data to Order Meta
     */
    public function save_custom_data_to_order($order, $data)
    {
        $session_data = WC()->session->get('wiwa_checkout_data');
        
        if ($session_data) {
            // Save raw data for debugging/reference
            $order->update_meta_data('_wiwa_checkout_raw', $session_data);

            // Process specific fields if needed
            // Example: Map 'guest_details' to line items? 
            // Usually OvaTourBooking handles its own meta if fields are named correctly.
            // But if we have custom fields, we save them here.
        }
    }

    /**
     * Customize checkout fields classes based on settings
     */
    public function customize_checkout_fields($fields)
    {
        $custom_fields = Wiwa_Fields_Manager::get_fields();
        $billing_fields = isset($custom_fields['billing']) ? $custom_fields['billing'] : [];

        // Map Wiwa positions to WooCommerce classes
        $pos_map = [
            'left' => 'form-row-first',
            'right' => 'form-row-last',
            'full' => 'form-row-wide'
        ];

        foreach ($billing_fields as $key => $field_data) {
            if (isset($fields['billing'][$key])) {
                // Remove existing positioning classes
                $current_classes = isset($fields['billing'][$key]['class']) ? $fields['billing'][$key]['class'] : [];
                $current_classes = array_diff($current_classes, ['form-row-first', 'form-row-last', 'form-row-wide']);

                // Add new positioning class
                $position = isset($field_data['position']) ? $field_data['position'] : (isset($field_data['width']) ? $field_data['width'] : 'full');

                // Handle legacy widths
                if ($position === 'half')
                    $position = 'left';
                if ($position === 'quarter')
                    $position = 'left';
                if ($position === 'three-quarter')
                    $position = 'full';

                if (isset($pos_map[$position])) {
                    $current_classes[] = $pos_map[$position];
                }
                else {
                    $current_classes[] = 'form-row-wide'; // Default to full
                }

                $fields['billing'][$key]['class'] = $current_classes;

                // Update label/placeholder if changed
                if (!empty($field_data['label'])) {
                    $fields['billing'][$key]['label'] = $field_data['label'];
                }
                if (!empty($field_data['placeholder'])) {
                    $fields['billing'][$key]['placeholder'] = $field_data['placeholder'];
                }
                if (isset($field_data['required'])) {
                    $fields['billing'][$key]['required'] = (bool)$field_data['required'];
                }

                // Update priority (order)
                if (isset($field_data['order'])) {
                    $fields['billing'][$key]['priority'] = intval($field_data['order']) * 10;
                }
            }
        }

        return $fields;
    }

    /**
     * Redirect cart page to checkout when override is enabled
     */
    public function redirect_cart_to_checkout()
    {
        // Redirect disabled to allow access to Cart page
        /*
        if (is_cart() && get_option('wiwa_checkout_enabled') && !WC()->cart->is_empty()) {
            $checkout_page_id = get_option('wiwa_checkout_page_id');
            $checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : wc_get_checkout_url();
            wp_safe_redirect($checkout_url);
            exit;
        }
        */
    }

    /**
     * Check if current page is the configured Wiwa checkout page
     */
    private function is_wiwa_checkout_page()
    {
        $checkout_page_id = get_option('wiwa_checkout_page_id');

        // Check if we're on the configured checkout page
        if ($checkout_page_id && is_page($checkout_page_id)) {
            return true;
        }

        // Legacy: check for 'checkout-wiwa' slug
        if (is_page('checkout-wiwa')) {
            return true;
        }

        return false;
    }

    /**
     * Replace content on WooCommerce checkout page when override is enabled
     */
    public function replace_checkout_content($content)
    {
        // Only replace if checkout is enabled and we're on WooCommerce checkout (not order-received)
        if (
        get_option('wiwa_checkout_enabled') &&
        is_checkout() &&
        !is_wc_endpoint_url('order-received') &&
        !is_wc_endpoint_url('order-pay') &&
        in_the_loop() &&
        is_main_query()
        ) {
            return $this->render_checkout();
        }
        return $content;
    }

    /**
     * Render the custom checkout (via shortcode or content replacement)
     */
    public function render_checkout()
    {
        if (!class_exists('WooCommerce')) {
            return '<div class="woocommerce-error">' . __('WooCommerce no está activo.', 'wiwa-checkout') . '</div>';
        }

        if (WC()->cart->is_empty()) {
            ob_start();
            include WIWA_CHECKOUT_PATH . 'templates/cart/empty-cart.php';
            return ob_get_clean();
        }

        // Detectar paso actual
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;

        ob_start();
        include WIWA_CHECKOUT_PATH . 'templates/checkout/wrapper.php';
        return ob_get_clean();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets()
    {
        // Always enqueue guest form hide script and CSS on product/tour pages
        if (is_product() || is_singular('product') || is_singular('rental') || is_singular('tour')) {
            wp_enqueue_style(
                'wiwa-ova-override',
                WIWA_CHECKOUT_URL . 'assets/css/ova-tour-override.css',
            [],
                WIWA_CHECKOUT_VERSION
            );

            wp_enqueue_script(
                'wiwa-hide-guest-form',
                WIWA_CHECKOUT_URL . 'assets/js/hide-guest-form.js',
            ['jquery'],
                WIWA_CHECKOUT_VERSION,
                true
            );
        }



        // Check if we should load checkout assets
        if (!$this->is_wiwa_checkout_page() && !is_checkout()) {
            return;
        }

        // Select2 for typeahead functionality
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
        [],
            '4.1.0'
        );
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
        ['jquery'],
            '4.1.0',
            true
        );

        // CSS
        wp_enqueue_style(
            'wiwa-checkout-css',
            WIWA_CHECKOUT_URL . 'assets/css/checkout.css',
        ['dashicons', 'select2'],
            WIWA_CHECKOUT_VERSION
        );

        // JS
        wp_enqueue_script(
            'wiwa-checkout-js',
            WIWA_CHECKOUT_URL . 'assets/js/checkout.js',
        ['jquery', 'wc-checkout'], // Add dependency on wc-checkout if available, or just make sure wc-checkout is loaded
            WIWA_CHECKOUT_VERSION,
            true
        );

        // Force enqueue WooCommerce Checkout script
        if (function_exists('wc_get_asset_url')) {
            wp_enqueue_script('wc-checkout');
        }

        // GeoIP integration
        wp_enqueue_script(
            'wiwa-geoip-js',
            WIWA_CHECKOUT_URL . 'assets/js/geoip.js',
        ['jquery'],
            WIWA_CHECKOUT_VERSION,
            true
        );

        wp_localize_script('wiwa-checkout-js', 'wiwaCheckout', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wiwa_checkout_nonce'),
            'homeUrl' => home_url('/'),
            'strings' => [
                'processing' => __('Procesando...', 'wiwa-checkout'),
                'error' => __('Error en la solicitud', 'wiwa-checkout')
            ],
            'geoIp' => [
                'autoComplete' => get_option('wiwa_geoip_autocomplete_city'),
                'detectCountry' => get_option('wiwa_geoip_detect_country')
            ]
        ]);
    }
}
