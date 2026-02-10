<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Ajax_Handler
{

    public function __construct()
    {
        // Frontend AJAX
        add_action('wp_ajax_wiwa_change_currency', [$this, 'change_currency']);
        add_action('wp_ajax_nopriv_wiwa_change_currency', [$this, 'change_currency']);
        add_action('wp_ajax_wiwa_apply_coupon', [$this, 'apply_coupon']);
        add_action('wp_ajax_nopriv_wiwa_apply_coupon', [$this, 'apply_coupon']);

        // Checkout processing
        add_action('wp_ajax_wiwa_process_checkout', [$this, 'process_checkout']);
        add_action('wp_ajax_nopriv_wiwa_process_checkout', [$this, 'process_checkout']);

        // Admin AJAX
        add_action('wp_ajax_wiwa_save_general_settings', [$this, 'save_general_settings']);
        add_action('wp_ajax_wiwa_save_fields', [$this, 'save_fields']);
        add_action('wp_ajax_wiwa_test_maxmind', [$this, 'test_maxmind']);

        // Update order data (Step 1 -> Session)
        add_action('wp_ajax_wiwa_update_order_data', [$this, 'update_order_data']);
        add_action('wp_ajax_nopriv_wiwa_update_order_data', [$this, 'update_order_data']);
    }

    /**
     * Change currency via FOX
     */
    public function change_currency()
    {
        check_ajax_referer('wiwa_checkout_nonce', 'nonce');

        $currency = sanitize_text_field($_POST['currency']);

        if (class_exists('Wiwa_FOX_Integration') && Wiwa_FOX_Integration::set_currency($currency)) {
            wp_send_json_success(['currency' => $currency]);
        }
        else {
            wp_send_json_error(['message' => __('Moneda no válida', 'wiwa-checkout')]);
        }
    }

    /**
     * Apply coupon code
     */
    public function apply_coupon()
    {
        check_ajax_referer('wiwa_checkout_nonce', 'nonce');

        $coupon_code = sanitize_text_field($_POST['coupon_code']);

        if (empty($coupon_code)) {
            wp_send_json_error(['message' => __('Ingresa un código de cupón', 'wiwa-checkout')]);
        }

        $result = WC()->cart->apply_coupon($coupon_code);

        if ($result) {
            wp_send_json_success([
                'message' => __('¡Cupón aplicado!', 'wiwa-checkout'),
                'new_total' => wc_price(WC()->cart->get_total('edit'))
            ]);
        }
        else {
            wp_send_json_error(['message' => __('Cupón no válido', 'wiwa-checkout')]);
        }
    }

    /**
     * Save general settings (Admin AJAX)
     */
    public function save_general_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin autorización', 'wiwa-checkout')]);
        }

        check_ajax_referer('wiwa_save_settings', 'nonce');

        // Save each option
        update_option('wiwa_checkout_enabled', isset($_POST['wiwa_checkout_enabled']) ? 1 : 0);
        update_option('wiwa_override_wc_checkout', isset($_POST['wiwa_override_wc_checkout']) ? 1 : 0);
        update_option('wiwa_override_wc_cart', isset($_POST['wiwa_override_wc_cart']) ? 1 : 0);
        update_option('wiwa_checkout_page_id', absint($_POST['wiwa_checkout_page_id']));

        wp_send_json_success(['message' => __('Configuración guardada', 'wiwa-checkout')]);
    }

    /**
     * Save custom fields (Admin AJAX)
     */
    public function save_fields()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin autorización', 'wiwa-checkout')]);
        }

        check_ajax_referer('wiwa_save_fields', 'wiwa_fields_nonce');

        if (!isset($_POST['wiwa_fields'])) {
            wp_send_json_error(['message' => __('No se recibieron campos', 'wiwa-checkout')]);
        }

        $fields_data = $_POST['wiwa_fields'];
        $sanitized_fields = [];

        foreach ($fields_data as $group => $group_fields) {
            $group = sanitize_key($group);
            $sanitized_fields[$group] = [];

            foreach ($group_fields as $key => $field) {
                $key = sanitize_key($key);
                $sanitized_fields[$group][$key] = [
                    'label' => sanitize_text_field($field['label'] ?? ''),
                    'type' => sanitize_key($field['type'] ?? 'text'),
                    'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                    'position' => sanitize_key($field['position'] ?? 'full'),
                    'required' => !empty($field['required']),
                    'enabled' => !empty($field['enabled']),
                    'width' => sanitize_key($field['width'] ?? 'full'),
                    'order' => isset($field['order']) ? intval($field['order']) : 0,
                ];
            }
        }

        Wiwa_Fields_Manager::update_fields($sanitized_fields);

        // Save passenger required overrides (for Ova Tour Booking fields)
        if (isset($_POST['wiwa_passenger_required']) && is_array($_POST['wiwa_passenger_required'])) {
            $passenger_required = [];
            foreach ($_POST['wiwa_passenger_required'] as $key => $value) {
                $passenger_required[sanitize_key($key)] = true;
            }
            update_option('wiwa_passenger_required', $passenger_required);
        }
        else {
            // No checkboxes checked = all optional
            update_option('wiwa_passenger_required', []);
        }

        wp_send_json_success(['message' => __('Campos guardados', 'wiwa-checkout')]);
    }

    /**
     * Test MaxMind GeoIP (Admin AJAX)
     */
    public function test_maxmind()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin autorización', 'wiwa-checkout')]);
        }

        if (!class_exists('Wiwa_GeoIP_Integration')) {
            wp_send_json_error(['message' => __('GeoIP no disponible', 'wiwa-checkout')]);
        }

        $city_data = Wiwa_GeoIP_Integration::detect_city();

        if (!empty($city_data['city'])) {
            wp_send_json_success($city_data);
        }
        else {
            wp_send_json_error(['message' => __('No se pudo detectar la ciudad', 'wiwa-checkout')]);
        }
    }

    /**
     * Process checkout via AJAX
     * Integrates with WooCommerce checkout processing
     */
    public function process_checkout()
    {
        // ... existing process_checkout code ...
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wiwa_checkout_nonce')) {
            wp_send_json([
                'result' => 'failure',
                'messages' => __('Sesión expirada. Por favor recarga la página.', 'wiwa-checkout')
            ]);
            return;
        }

        // Get step 1 data from session and merge with current POST
        $step_1_data = WC()->session->get('wiwa_step_1_data', []);
        $_POST = array_merge($_POST, $step_1_data);

        // Set up billing fields from POST data
        $billing_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_email',
            'billing_phone',
            'billing_country',
            'billing_city',
            'billing_address_1',
            'billing_postcode',
        ];

        foreach ($billing_fields as $field) {
            if (isset($_POST[$field])) {
                $_POST[$field] = sanitize_text_field($_POST[$field]);
            }
        }

        // Ensure required WooCommerce fields
        if (empty($_POST['billing_address_1'])) {
            $_POST['billing_address_1'] = 'N/A';
        }
        if (empty($_POST['billing_postcode'])) {
            $_POST['billing_postcode'] = '000000';
        }

        // Set ship to billing
        $_POST['ship_to_different_address'] = 0;

        // Trigger WooCommerce checkout processing
        try {
            // Define WOOCOMMERCE_CHECKOUT constant if not defined
            if (!defined('WOOCOMMERCE_CHECKOUT')) {
                define('WOOCOMMERCE_CHECKOUT', true);
            }

            // Set payment method
            if (!empty($_POST['payment_method'])) {
                WC()->session->set('chosen_payment_method', sanitize_text_field($_POST['payment_method']));
            }

            // Process checkout
            WC()->checkout()->process_checkout();

        }
        catch (Exception $e) {
            wp_send_json([
                'result' => 'failure',
                'messages' => $e->getMessage()
            ]);
        }

        // If we get here without redirect, something went wrong
        wp_send_json([
            'result' => 'failure',
            'messages' => __('Error procesando el pago. Por favor intenta de nuevo.', 'wiwa-checkout')
        ]);
    }

    /**
     * Update order data (Step 1 to Session)
     */
    public function update_order_data()
    {
        check_ajax_referer('wiwa_checkout_step_1', 'wiwa_step_1_nonce');

        // 1. Save general Step 1 data to session (for later use in process_checkout)
        WC()->session->set('wiwa_step_1_data', $_POST);

        // 2. Process Passenger Data for Ova Tour Booking
        if (isset($_POST['tours']) && is_array($_POST['tours']) && class_exists('Wiwa_Tour_Booking_Integration')) {
            $tours = $_POST['tours'];
            $guest_fields_config = Wiwa_Tour_Booking_Integration::get_guest_info_fields();
            $cart = WC()->cart->get_cart();
            $updated = false;

            foreach ($tours as $tour_index => $tour_data) {
                $cart_key = isset($tour_data['cart_key']) ? $tour_data['cart_key'] : '';

                if (!$cart_key || !isset($cart[$cart_key])) {
                    continue;
                }

                $total_pax = isset($tour_data['total_pax']) ? intval($tour_data['total_pax']) : 1;
                $guest_info = [];

                // Reconstruct guest_info array: [ 'field_key' => [ 'val_pax_1', 'val_pax_2' ] ]
                if ($guest_fields_config) {
                    foreach ($guest_fields_config as $field_key => $field_config) {
                        $guest_info[$field_key] = [];

                        for ($pax = 1; $pax <= $total_pax; $pax++) {
                            // Calculate global guest index as used in form (e.g. 101, 001)
                            // In form: $guest_index = ($tour_index * 100) + $pax;
                            $guest_index = ($tour_index * 100) + $pax;
                            $input_name = "guest_{$field_key}_{$guest_index}";

                            $val = isset($_POST[$input_name]) ? sanitize_text_field($_POST[$input_name]) : '';

                            // Special handling for Country Code + Phone
                            if (isset($_POST[$input_name . '_code'])) {
                                $code = sanitize_text_field($_POST[$input_name . '_code']);
                                if ($val) {
                                    $val = $code . ' ' . $val;
                                }
                            }

                            // Special handling for Document Type + Number
                            if (isset($_POST[$input_name . '_type'])) {
                                $type = sanitize_text_field($_POST[$input_name . '_type']);
                                if ($val) {
                                    $val = $type . ': ' . $val; // OVA format usually just strings, we concatenate
                                }
                            }

                            $guest_info[$field_key][] = $val;
                        }
                    }
                }

                // Update Cart Item
                if (!empty($guest_info)) {
                    WC()->cart->cart_contents[$cart_key]['ovatb_guest_info'] = $guest_info;
                    $updated = true;
                }
            }

            if ($updated) {
                WC()->cart->set_session();
            }
        }

        wp_send_json_success(['message' => 'Data updated']);
    }
}
