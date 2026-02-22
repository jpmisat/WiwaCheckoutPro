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
        // Frontend GeoIP auto-fill (handled by GeoIP Detect via JS API instead)

        // Update order data (Step 1 -> Session)
        add_action('wp_ajax_wiwa_update_order_data', [$this, 'update_order_data']);
        add_action('wp_ajax_nopriv_wiwa_update_order_data', [$this, 'update_order_data']);

        // AJAX Add to Cart Hooks
        add_action('wp_ajax_wiwa_ajax_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_wiwa_ajax_add_to_cart', [$this, 'ajax_add_to_cart']);

        // Mini Cart Qty
        add_action('wp_ajax_wiwa_update_mini_cart_qty', [$this, 'ajax_update_mini_cart_qty']);
        add_action('wp_ajax_nopriv_wiwa_update_mini_cart_qty', [$this, 'ajax_update_mini_cart_qty']);

        // Smart Pax Update
        add_action('wp_ajax_wiwa_update_tour_pax', [$this, 'ajax_update_tour_pax']);
        add_action('wp_ajax_nopriv_wiwa_update_tour_pax', [$this, 'ajax_update_tour_pax']);
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

                // Reconstruct guest_info array to match Ova Tour Booking expected structure
                if ($guest_fields_config) {
                    $guest_breakdown = $this->get_cart_item_guest_breakdown($cart[$cart_key]);
                    
                    // If no breakdown found, fallback to 'pax' with total_pax
                    if (empty($guest_breakdown)) {
                        $guest_breakdown['pax'] = [
                            'key' => 'numberof_pax',
                            'label' => 'Pax',
                            'count' => $total_pax
                        ];
                    }

                    $global_pax_index = 1;

                    foreach ($guest_breakdown as $guest_type => $type_data) {
                        $count = $type_data['count'];
                        $guest_info[$guest_type] = [];

                        for ($i = 0; $i < $count; $i++) {
                            $guest_info[$guest_type][$i] = [];
                            // Calculate global guest index as used in form (e.g. 101, 001)
                            $guest_index = ($tour_index * 100) + $global_pax_index;

                            foreach ($guest_fields_config as $field_key => $field_config) {
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
                                    $doc_type = sanitize_text_field($_POST[$input_name . '_type']);
                                    if ($val) {
                                        $val = $doc_type . ': ' . $val;
                                    }
                                }

                                // Special handling for Country / Nationality (Map code to name)
                                if ($field_key === 'guest_nationality' && class_exists('WC_Countries')) {
                                    $countries = WC()->countries->get_countries();
                                    if (isset($countries[$val])) {
                                        $val = $countries[$val];
                                    }
                                }

                                if ($val !== '') {
                                    $guest_info[$guest_type][$i][$field_key] = [
                                        'label' => isset($field_config['label']) ? $field_config['label'] : ucfirst($field_key),
                                        'type' => isset($field_config['type']) ? $field_config['type'] : 'text',
                                        'value' => $val
                                    ];
                                }
                            }
                            $global_pax_index++;
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

    /**
     * AJAX Handler: Update Mini Cart Quantity
     */
    public function ajax_update_mini_cart_qty()
    {
        check_ajax_referer('wiwa_checkout_nonce', 'security');

        $cart_key = isset($_POST['cart_key']) ? sanitize_text_field($_POST['cart_key']) : '';
        $qty = isset($_POST['qty']) ? max(0, min(99, intval($_POST['qty']))) : 0;

        if (!$cart_key || !isset(WC()->cart->get_cart()[$cart_key])) {
            wp_send_json_error(['message' => 'Missing cart key']);
        }

        $item_removed = false;

        if ($qty <= 0) {
            WC()->cart->remove_cart_item($cart_key);
            $item_removed = true;
        } else {
            WC()->cart->set_quantity($cart_key, $qty, true); // true = refresh totals
        }

        WC()->cart->calculate_totals();
        WC()->cart->maybe_set_cart_cookies();

        wp_send_json_success([
            'message' => 'Updated',
            'item_removed' => $item_removed,
            'item_subtotal' => $item_removed ? '' : $this->get_cart_item_subtotal_html($cart_key),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_total' => WC()->cart->get_total(),
            'totals_html' => $this->get_cart_totals_html(),
        ]);
    }

    /**
     * Render cart totals block for AJAX responses.
     */
    private function get_cart_totals_html()
    {
        if (!function_exists('woocommerce_cart_totals')) {
            return '';
        }

        ob_start();
        woocommerce_cart_totals();
        return ob_get_clean();
    }

    /**
     * Resolve line subtotal HTML for a specific cart item.
     */
    private function get_cart_item_subtotal_html($cart_item_key)
    {
        $cart = WC()->cart ? WC()->cart->get_cart() : [];
        if (!isset($cart[$cart_item_key])) {
            return '';
        }

        $cart_item = $cart[$cart_item_key];
        if (!isset($cart_item['data'])) {
            return '';
        }

        return WC()->cart->get_product_subtotal($cart_item['data'], $cart_item['quantity']);
    }

    /**
     * Extract and normalize guest breakdown from cart item metadata.
     */
    private function get_cart_item_guest_breakdown($cart_item)
    {
        $breakdown = [];

        foreach ((array) $cart_item as $key => $value) {
            if (strpos($key, 'numberof_') !== 0 || $key === 'numberof_guests' || !is_numeric($value)) {
                continue;
            }

            $count = max(0, intval($value));
            if ($count <= 0) {
                continue;
            }

            $slug = str_replace('numberof_', '', $key);
            $breakdown[$slug] = [
                'key' => $key,
                'label' => ucwords(str_replace(['_', '-'], ' ', $slug)),
                'count' => $count,
            ];
        }

        return $breakdown;
    }

    /**
     * Determine the editable passenger metadata key for a cart item.
     */
    private function resolve_target_guest_key($cart_item, $requested_key = '')
    {
        $all_guest_keys = [];

        foreach ((array) $cart_item as $key => $value) {
            if (strpos($key, 'numberof_') === 0 && $key !== 'numberof_guests' && is_numeric($value)) {
                $all_guest_keys[] = $key;
            }
        }

        foreach (['numberof_pax', 'numberof_adult', 'numberof_adults'] as $fallback_key) {
            if (isset($cart_item[$fallback_key]) && !in_array($fallback_key, $all_guest_keys, true)) {
                $all_guest_keys[] = $fallback_key;
            }
        }

        if (empty($all_guest_keys)) {
            return ['', []];
        }

        if ($requested_key) {
            $normalized = strpos($requested_key, 'numberof_') === 0 ? $requested_key : 'numberof_' . $requested_key;
            if (in_array($normalized, $all_guest_keys, true)) {
                return [$normalized, $all_guest_keys];
            }
        }

        if (in_array('numberof_pax', $all_guest_keys, true)) {
            return ['numberof_pax', $all_guest_keys];
        }

        foreach ($all_guest_keys as $guest_key) {
            if (!empty($cart_item[$guest_key]) && intval($cart_item[$guest_key]) > 0) {
                return [$guest_key, $all_guest_keys];
            }
        }

        return [$all_guest_keys[0], $all_guest_keys];
    }


    /**
     * AJAX Handler for Add to Cart
     */
    public function ajax_add_to_cart()
    {
        // Verificar nonce
        // check_ajax_referer('ovatb-admin-ajax', 'security'); 

        $product_id = isset($_POST['ovatb-product-id']) ? intval($_POST['ovatb-product-id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(['message' => 'ID de producto inválido.']);
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Producto no encontrado.']);
        }

        // --- VALIDACIÓN ---
        // Replicamos la logica de OVATB_Ajaxs::ovatb_calculate_total para validar
        
        // 1. Fechas y Horas
        $checkin_date_str = isset($_POST['checkin_date']) ? sanitize_text_field($_POST['checkin_date']) : '';
        $checkout_date_str = isset($_POST['checkout_date']) ? sanitize_text_field($_POST['checkout_date']) : '';
        $start_time_str = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';

        $checkin_date = strtotime($checkin_date_str);
        $checkout_date = strtotime($checkout_date_str);
        $start_time = strtotime($start_time_str);

        // Convert input date (Logic from ovatb)
        if (function_exists('OVATB')) {
            $new_dates = OVATB()->options->convert_input_date($product_id, $checkin_date, $checkout_date, $start_time);
            $checkin_date = strtotime($new_dates['checkin_date']);
            $checkout_date = strtotime($new_dates['checkout_date']);
        }

        // 2. Validate Booking
        if (function_exists('OVATB') && isset(OVATB()->booking)) {
            $passed = OVATB()->booking->booking_validation($product_id, $checkin_date, $checkout_date, isset($_POST['form_name']) ? $_POST['form_name'] : '');
            if ($passed && $passed !== true) {
                wp_send_json_error(['message' => $passed]);
            }
        }

        // 3. Guests Validation
        $data = [
            'product_id' => $product_id,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
        ];
        
        // Recopilar numberof_*
        $numberof_guests = 0;
        if (function_exists('OVATB') && $product->is_type('ovatb_tour')) {
            $guest_options = $product->get_guests();
            if ($guest_options) {
                foreach ($guest_options as $guest) {
                    $val = isset($_POST['numberof_' . $guest['name']]) ? intval($_POST['numberof_' . $guest['name']]) : 0;
                    if (!$val && isset($_POST['ovatb_numberof_' . $guest['name']])) {
                        $val = intval($_POST['ovatb_numberof_' . $guest['name']]);
                    }

                    $data['numberof_' . $guest['name']] = $val;
                    $numberof_guests += $val;
                }
            }
        }
        $data['numberof_guests'] = $numberof_guests;

        if (function_exists('OVATB') && isset(OVATB()->booking)) {
            $mesg = OVATB()->booking->numberof_guests_validation($data, $product);
            if ($mesg && $mesg !== true) {
                 wp_send_json_error(['message' => $mesg]);
            }
            
            // Availability Check
             $available = OVATB()->booking->get_numberof_available_guests($product_id, $checkin_date, $checkout_date, $numberof_guests, isset($_POST['form_name']) ? $_POST['form_name'] : '');
             if (isset($available['error']) && $available['error']) {
                 wp_send_json_error(['message' => $available['error']]);
             }
        }

        // --- AGREGAR AL CARRITO ---
        $added = WC()->cart->add_to_cart($product_id, 1);

        if ($added) {
            wp_send_json_success([
                'message' => 'Producto agregado al carrito',
                'product_title' => $product->get_name(),
                'cart_url' => wc_get_cart_url()
            ]);
        } else {
            // Recopilar errores de WC
            $errors = wc_get_notices('error');
            wc_clear_notices(); 
            
            $msg = 'No se pudo agregar al carrito.';
            if (!empty($errors)) {
                 $msg .= ' Verifique los datos.';
            }
            wp_send_json_error(['message' => $msg]);
        }
    }

    /**
     * AJAX Handler: Update TOUR Pax (Metadata)
     * Handles the complex logic of updating 'numberof_X' metadata.
     */
    public function ajax_update_tour_pax()
    {
        check_ajax_referer('wiwa_checkout_nonce', 'security');

        $cart_key = isset($_POST['cart_key']) ? sanitize_text_field($_POST['cart_key']) : '';
        $action = isset($_POST['update_action']) ? sanitize_text_field($_POST['update_action']) : 'update';
        $requested_qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
        $requested_guest_key = isset($_POST['guest_key']) ? sanitize_key($_POST['guest_key']) : '';

        $cart = WC()->cart->get_cart();

        if (!$cart_key || !isset($cart[$cart_key])) {
            wp_send_json_error(['message' => 'Item not found']);
        }

        $cart_item = $cart[$cart_key];

        list($target_key, $all_guest_keys) = $this->resolve_target_guest_key($cart_item, $requested_guest_key);
        if (!$target_key) {
            wp_send_json_error(['message' => 'Passenger metadata not found.']);
        }

        $current_val = isset($cart_item[$target_key]) ? intval($cart_item[$target_key]) : 1;

        if ($requested_qty > 0) {
            $new_val = $requested_qty;
        } elseif ($action === 'increase') {
            $new_val = $current_val + 1;
        } else {
            $new_val = $current_val - 1;
        }

        $new_val = max(1, min(99, intval($new_val)));

        WC()->cart->cart_contents[$cart_key][$target_key] = $new_val;

        $total_guests = 0;
        foreach ($all_guest_keys as $guest_key) {
            $value = isset(WC()->cart->cart_contents[$cart_key][$guest_key]) ? intval(WC()->cart->cart_contents[$cart_key][$guest_key]) : 0;
            $value = max(0, $value);
            WC()->cart->cart_contents[$cart_key][$guest_key] = $value;
            $total_guests += $value;
        }

        if ($total_guests <= 0) {
            WC()->cart->cart_contents[$cart_key][$target_key] = 1;
            $new_val = 1;
            $total_guests = 1;
        }

        WC()->cart->cart_contents[$cart_key]['numberof_guests'] = $total_guests;
        WC()->cart->set_session();
        WC()->cart->calculate_totals();
        WC()->cart->maybe_set_cart_cookies();

        $cart_after = WC()->cart->get_cart();
        if (!isset($cart_after[$cart_key])) {
            wp_send_json_error(['message' => 'Unable to refresh cart item.']);
        }

        $updated_item = $cart_after[$cart_key];
        $breakdown_data = $this->get_cart_item_guest_breakdown($updated_item);

        $guest_breakdown = [];
        $guest_breakdown_text = [];
        foreach ($breakdown_data as $slug => $guest_data) {
            $guest_breakdown[$slug] = $guest_data['count'];
            $guest_breakdown_text[] = sprintf('%d %s', $guest_data['count'], $guest_data['label']);
        }

        wp_send_json_success([
            'new_qty' => $new_val,
            'total_pax' => isset($updated_item['numberof_guests']) ? intval($updated_item['numberof_guests']) : $new_val,
            'target_key' => $target_key,
            'guest_breakdown' => $guest_breakdown,
            'guest_breakdown_text' => implode(' - ', $guest_breakdown_text),
            'item_subtotal' => $this->get_cart_item_subtotal_html($cart_key),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_total' => WC()->cart->get_total(),
            'totals_html' => $this->get_cart_totals_html(),
            'message' => 'Pax updated',
        ]);
    }
}
