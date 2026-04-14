<?php
/**
 * Global helper functions for Wiwa Tour Checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper: Extract tour meta from OvaTourBooking cart item.
 * Returns ['checkin' => string, 'checkout' => string, 'duration_days' => int, 'travelers' => int, 'guests_detail' => array]
 */
function wiwa_extract_tour_meta($cart_item) {
    // Check if Woo dates function exists, if not use fallback
    $date_format = get_option('date_format');

    $meta = [
        'checkin'        => '',
        'checkout'       => '',
        'duration_days'  => 0,
        'duration_label' => '',
        'travelers'      => isset($cart_item['quantity']) ? $cart_item['quantity'] : 1,
        'guests_detail'  => [],
    ];

    // --- Check-in / Check-out dates (OvaTourBooking keys) ---
    if (!empty($cart_item['checkin_date'])) {
        $ts = strtotime($cart_item['checkin_date']);
        $meta['checkin'] = $ts ? date_i18n('d-m-Y', $ts) : sanitize_text_field($cart_item['checkin_date']);
    }
    if (!empty($cart_item['checkout_date'])) {
        $ts_out = strtotime($cart_item['checkout_date']);
        $meta['checkout'] = $ts_out ? date_i18n('d-m-Y', $ts_out) : sanitize_text_field($cart_item['checkout_date']);
    }

    // --- Duration (diff between checkin and checkout) ---
    if (!empty($cart_item['checkin_date']) && !empty($cart_item['checkout_date'])) {
        $ts_in  = strtotime($cart_item['checkin_date']);
        $ts_out = strtotime($cart_item['checkout_date']);
        if ($ts_in && $ts_out && $ts_out >= $ts_in) {
            // Difference in nights + 1 = total inclusive days (standard tourism logic)
            $diff_nights = (int) ceil(($ts_out - $ts_in) / 86400);
            $meta['duration_days'] = $diff_nights + 1; 

            if ($meta['duration_days'] === 1) {
                $meta['duration_label'] = '1 día full';
            } else {
                $meta['duration_label'] = $meta['duration_days'] . ' días';
            }
        }
    }

    // If still no duration, try the product's duration metadata
    if ($meta['duration_days'] === 0 && isset($cart_item['data'])) {
        $_product = $cart_item['data'];
        if (method_exists($_product, 'get_meta_value')) {
            $dur_type = $_product->get_meta_value('duration_type');
            if ($dur_type === 'fixed') {
                $dur_number = (int) $_product->get_meta_value('duration_number');
                $dur_unit   = $_product->get_meta_value('duration_unit'); // 'day', 'hour', 'night'
                if ($dur_number > 0) {
                    $meta['duration_days'] = $dur_number;
                    if ($dur_unit === 'hour') {
                        $meta['duration_label'] = $dur_number . ' hora' . ($dur_number > 1 ? 's' : '');
                    } elseif ($dur_unit === 'night') {
                        $meta['duration_label'] = $dur_number . ' noche' . ($dur_number > 1 ? 's' : '');
                    } else {
                        $meta['duration_label'] = $dur_number . ' día' . ($dur_number > 1 ? 's' : '');
                    }
                }
            }
        }
    }

    // --- Travelers / Passengers total ---
    if (isset($cart_item['numberof_guests']) && intval($cart_item['numberof_guests']) > 0) {
        $meta['travelers'] = intval($cart_item['numberof_guests']);
    } else {
        // Sum all numberof_ keys as fallback
        $total_pax = 0;
        foreach ($cart_item as $key => $val) {
            if (strpos($key, 'numberof_') === 0 && $key !== 'numberof_guests') {
                $total_pax += intval($val);
            }
        }
        if ($total_pax > 0) {
            $meta['travelers'] = $total_pax;
        }
    }

    // Guest detail breakdown for the AJAX
    if (isset($cart_item['data'])) {
        $_product = $cart_item['data'];
        if (method_exists($_product, 'get_guests')) {
            $guest_options = $_product->get_guests();
            if (is_array($guest_options)) {
                foreach ($guest_options as $guest) {
                    $gname = $guest['name'];
                    $glabel = isset($guest['label']) ? $guest['label'] : ucfirst($gname);
                    $gcount = isset($cart_item['numberof_' . $gname]) ? intval($cart_item['numberof_' . $gname]) : 0;
                    $meta['guests_detail'][] = [
                        'name'  => $gname,
                        'label' => $glabel,
                        'count' => $gcount,
                    ];
                }
            }
        }
    }

    return $meta;
}
