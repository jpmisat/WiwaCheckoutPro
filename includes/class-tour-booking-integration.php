<?php
/**
 * Tour Booking Integration
 * Syncs with OvaTheme Tour Booking plugin guest information
 * Based on OVATB_Booking class cart item data structure
 * 
 * Cart item keys used by Tour Booking (no prefix):
 * - checkin_date - start date
 * - checkout_date - end date
 * - numberof_guests - total guests count
 * - numberof_<guest_type> - count per guest type (e.g., numberof_pax, numberof_adult)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Tour_Booking_Integration
{
    /**
     * Get the total pax count from cart item data
     * Tour Booking stores numberof_guests as total, and numberof_<type> per guest type
     */
    public static function get_pax_count($cart_item)
    {
        if (empty($cart_item) || !is_array($cart_item)) {
            return 1;
        }

        // Priority 1: numberof_guests (total from Tour Booking - most reliable)
        if (isset($cart_item['numberof_guests']) && intval($cart_item['numberof_guests']) > 0) {
            return intval($cart_item['numberof_guests']);
        }

        // Priority 2: Sum all numberof_<type> keys
        $total = 0;
        foreach ($cart_item as $key => $value) {
            if (strpos($key, 'numberof_') === 0 && $key !== 'numberof_guests' && is_numeric($value)) {
                $total += intval($value);
            }
        }
        if ($total > 0) {
            return $total;
        }

        // Priority 3: Check for specific common keys
        $keys_to_check = ['numberof_pax', 'numberof_adult', 'numberof_adults', 'quantity'];
        foreach ($keys_to_check as $key) {
            if (isset($cart_item[$key]) && intval($cart_item[$key]) > 0) {
                return intval($cart_item[$key]);
            }
        }

        // Fallback: Use cart quantity
        if (isset($cart_item['quantity']) && intval($cart_item['quantity']) > 0) {
            return intval($cart_item['quantity']);
        }

        return 1;
    }

    /**
     * Get tour date from cart item data
     * Tour Booking stores as 'checkin_date'
     */
    public static function get_tour_date($cart_item)
    {
        if (empty($cart_item) || !is_array($cart_item)) {
            return '';
        }

        // Primary: checkin_date (standard Tour Booking key)
        if (isset($cart_item['checkin_date']) && !empty($cart_item['checkin_date'])) {
            return $cart_item['checkin_date'];
        }

        // Fallback keys
        $keys_to_check = ['checkout_date', 'pickup_date', 'date', 'tour_date'];
        foreach ($keys_to_check as $key) {
            if (isset($cart_item[$key]) && !empty($cart_item[$key])) {
                return $cart_item[$key];
            }
        }

        return '';
    }

    /**
     * Get checkout/end date from cart item data
     */
    public static function get_checkout_date($cart_item)
    {
        if (empty($cart_item) || !is_array($cart_item)) {
            return '';
        }

        if (isset($cart_item['checkout_date']) && !empty($cart_item['checkout_date'])) {
            return $cart_item['checkout_date'];
        }

        return '';
    }

    /**
     * Get tour time from cart item data
     */
    public static function get_tour_time($cart_item)
    {
        if (empty($cart_item) || !is_array($cart_item)) {
            return '';
        }

        $keys_to_check = ['start_time', 'checkin_time', 'time', 'tour_time'];
        foreach ($keys_to_check as $key) {
            if (isset($cart_item[$key]) && !empty($cart_item[$key])) {
                return $cart_item[$key];
            }
        }

        return '';
    }

    /**
     * Get guest breakdown by type
     * Returns array like ['Adult' => 2, 'Child' => 1]
     */
    public static function get_guest_breakdown($cart_item, $product = null)
    {
        $breakdown = [];

        if (empty($cart_item) || !is_array($cart_item)) {
            return $breakdown;
        }

        // Try to get guest labels from product
        $guest_labels = [];
        if ($product && method_exists($product, 'get_guests')) {
            $guests = $product->get_guests();
            if (is_array($guests)) {
                foreach ($guests as $guest) {
                    if (isset($guest['name']) && isset($guest['label'])) {
                        $guest_labels[$guest['name']] = $guest['label'];
                    }
                }
            }
        }

        // Extract numberof_<type> values
        foreach ($cart_item as $key => $value) {
            if (strpos($key, 'numberof_') === 0 && $key !== 'numberof_guests' && is_numeric($value) && intval($value) > 0) {
                $type = str_replace('numberof_', '', $key);
                $label = isset($guest_labels[$type]) ? $guest_labels[$type] : ucfirst($type);
                $breakdown[$label] = intval($value);
            }
        }

        return $breakdown;
    }

    /**
     * Get guest information fields from Ova Tour Booking
     * Applies wiwa_passenger_required overrides for frontend validation
     */
    public static function get_guest_info_fields()
    {
        // Primary: Get fields from Ova Tour Booking
        if (self::is_active()) {
            $ovatb_fields = self::get_ovatb_guest_fields();
            if (!empty($ovatb_fields)) {
                // Apply Wiwa required overrides
                $wiwa_required = get_option('wiwa_passenger_required', []);
                foreach ($ovatb_fields as $key => &$field) {
                    if (isset($wiwa_required[$key])) {
                        $field['required'] = $wiwa_required[$key];
                    }
                }
                return $ovatb_fields;
            }
        }

        // Fallback: Default fields
        return self::get_default_guest_fields();
    }

    /**
     * Retrieve guest fields defined in Ova Tour Booking > Settings > Guest Information
     */
    public static function get_ovatb_guest_fields()
    {
        $fields = get_option('ovatb_guest_fields', []);

        if (empty($fields) || !is_array($fields)) {
            return [];
        }

        $formatted_fields = [];

        foreach ($fields as $key => $field) {
            // Check if field is enabled
            if (empty($field['enable']) || $field['enable'] !== 'on') {
                continue;
            }

            // Parse options for select/radio fields
            $options = [];
            if (isset($field['option_ids']) && isset($field['option_names'])) {
                $ids = is_string($field['option_ids']) ? explode(',', $field['option_ids']) : (array)$field['option_ids'];
                $names = is_array($field['option_names']) ? $field['option_names'] : explode(',', $field['option_names']); // Typically array in recent versions

                // If names are array (from repeater), they might be indexed
                if (count($ids) > 0) {
                    foreach ($ids as $i => $id) {
                        $name = isset($names[$i]) ? $names[$i] : $id;
                        $options[trim($id)] = trim($name);
                    }
                }
            }

            // Map to Wiwa structure
            $formatted_fields[$key] = [
                'key' => $key,
                'label' => isset($field['label']) ? $field['label'] : ucfirst($key),
                'type' => isset($field['type']) ? $field['type'] : 'text',
                'required' => isset($field['required']) && $field['required'] === 'on',
                'enabled' => true,
                'placeholder' => isset($field['placeholder']) ? $field['placeholder'] : '',
                'options' => $options
            ];
        }

        return $formatted_fields;
    }

    /**
     * Default guest fields
     */
    public static function get_default_guest_fields()
    {
        return [
            'guest_first_name' => [
                'key' => 'guest_first_name',
                'label' => __('Name', 'wiwa-checkout'),
                'type' => 'text',
                'required' => true,
                'enabled' => true,
            ],
            'guest_last_name' => [
                'key' => 'guest_last_name',
                'label' => __('Last Name', 'wiwa-checkout'),
                'type' => 'text',
                'required' => true,
                'enabled' => true,
            ],
            'guest_passport' => [
                'key' => 'guest_passport',
                'label' => __('Document', 'wiwa-checkout'),
                'type' => 'text',
                'required' => true,
                'enabled' => true,
            ],
            'guest_nationality' => [
                'key' => 'guest_nationality',
                'label' => __('Nationality', 'wiwa-checkout'),
                'type' => 'select',
                'required' => true,
                'enabled' => true,
            ],
        ];
    }

    /**
     * Check if Tour Booking plugin is active
     */
    public static function is_active()
    {
        return class_exists('OVATB') ||
            class_exists('OVATB_Booking') ||
            defined('OVATB_VERSION');
    }
}
