<?php
/**
 * Wiwa Tour Checkout - Premium Cart Template (Stitch Pixel-Perfect v3)
 * Version: 2.11.2
 * 
 * Matches the code-stich.html design exactly, integrated with
 * WooCommerce + OvaTourBooking + WOOCS data sources.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');

/**
 * Helper: Extract tour meta from OvaTourBooking cart item.
 * Returns ['checkin' => string, 'checkout' => string, 'duration_days' => int, 'travelers' => int, 'guests_detail' => array]
 */
function wiwa_extract_tour_meta($cart_item) {
    $meta = [
        'checkin'        => '',
        'checkout'       => '',
        'duration_days'  => 0,
        'duration_label' => '',
        'travelers'      => $cart_item['quantity'],
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
    if ($meta['duration_days'] === 0) {
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

    return $meta;
}

// --- Get current currency code (WOOCS compatible) ---
$wiwa_currency_code = get_woocommerce_currency(); // e.g. "COP", "USD"
?>

<style>
    /* ===== Stitch Cart Inline Styles ===== */
    .wiwa-cart-page {
        font-family: 'Montserrat', 'Roboto', sans-serif;
        width: 100%;
        max-width: 100%;  /* Full Width Fix */
        margin: 0 auto;
        padding: 0 16px;
    }
    @media (min-width: 1024px) {
        .wiwa-cart-page {
            padding: 0 32px; /* Responsive padding */
        }
    }
    .card-shadow {
        box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.02);
    }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
    }

    /* Fix WC image output to fill container */
    .wiwa-cart-page .wiwa-thumb-wrap img,
    .wiwa-cart-page .wiwa-thumb-wrap a img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        display: block;
        border-radius: 0 !important;
        max-width: none !important;
    }
    .wiwa-cart-page .wiwa-thumb-wrap a {
        display: block;
        width: 100%;
        height: 100%;
    }

    /* Hide default WC variation data */
    .wiwa-cart-page dl.variation,
    .wiwa-cart-page .variation {
        display: none !important;
    }

    /* WC price amount inherits font */
    .wiwa-cart-page .woocommerce-Price-amount {
        font-family: inherit !important;
    }

    /* ===== Stepper pill — clean, no red borders ===== */
    .wiwa-stepper-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 9999px;
        padding: 3px;
        width: 120px;
        height: 40px;
    }
    .wiwa-stepper-pill button {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        color: #4b5563;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        box-shadow: none !important;
        line-height: 1;
    }
    .wiwa-stepper-pill button:hover {
        background: #ffffff !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
        color: #1a3c28;
    }
    .wiwa-stepper-pill button:focus {
        outline: none !important;
        box-shadow: none !important;
    }
    .wiwa-stepper-pill input {
        width: 40px;
        text-align: center;
        font-weight: 700;
        font-size: 14px;
        color: #1a3c28;
        background: transparent !important;
        border: none !important;
        padding: 0;
        margin: 0;
        -moz-appearance: textfield;
        appearance: none;
        box-shadow: none !important;
        outline: none !important;
    }
    .wiwa-stepper-pill input::-webkit-outer-spin-button,
    .wiwa-stepper-pill input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* ===== Price sizes — more harmonious ===== */
    .wiwa-price-per-person {
        font-size: 12px;
        color: #9ca3af;
    }
    .wiwa-price-per-person .woocommerce-Price-amount {
        font-size: 12px !important;
    }
    .wiwa-price-subtotal {
        font-size: 1.5rem;  
        font-weight: 700;
        color: #1a3c28;
    }
    .wiwa-price-subtotal .woocommerce-Price-amount {
        font-size: inherit !important;
    }
    /* Tame any theme-injected woocs price markup */
    .wiwa-price-subtotal .woocs_special_price_code {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        display: inline-block !important;
        line-height: 1.2;
        word-break: break-word;
    }
    @media (min-width: 1024px) {
        .wiwa-price-subtotal .woocs_special_price_code {
            font-size: 1.75rem !important; /* Reduced from 2rem as requested */
        }
    }
    .wiwa-price-per-person .woocs_special_price_code {
        font-size: 0.85rem !important;
        font-weight: inherit !important;
    }

    /* ===== Sticky sidebar ===== */
    .wiwa-sticky-sidebar {
        position: sticky;
        top: 8rem;
    }

    /* ===== "Proceder al Pago" CTA — high-contrast UX ===== */
    .wiwa-cta-pay {
        display: block;
        text-align: center;
        width: 100%;
        background: linear-gradient(135deg, #1a3c28 0%, #2b4c3b 100%);
        color: #ffffff !important;
        font-weight: 700;
        padding: 18px 24px;
        border-radius: 12px;
        text-decoration: none !important;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-size: 13px;
        box-shadow: 0 10px 20px -5px rgba(26, 60, 40, 0.35);
        transition: all 0.3s ease;
    }
    .wiwa-cta-pay:hover {
        background: linear-gradient(135deg, #2b4c3b 0%, #3b6050 100%);
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 14px 28px -6px rgba(26, 60, 40, 0.45);
    }
    .wiwa-cta-pay:visited,
    .wiwa-cta-pay:active {
        color: #ffffff !important;
    }

    /* ===== Currency code subtle ===== */
    .wiwa-currency-code {
        font-size: 0.45em;
        font-weight: 500;
        color: #9ca3af;
        letter-spacing: 0.05em;
        vertical-align: super;
        margin-left: 4px;
    }

    /* Cart card hover */
    .wiwa-cart-page article {
        transition: box-shadow 0.3s ease;
    }
    .wiwa-cart-page article:hover {
        box-shadow: 0 8px 30px -4px rgba(0,0,0,0.08), 0 4px 8px -2px rgba(0,0,0,0.04) !important;
    }

    /* Loading state */
    .wiwa-cart-page article.wiwa-loading {
        opacity: 0.5;
        pointer-events: none;
    }

    /* WC notices */
    .wiwa-cart-page .woocommerce-message,
    .wiwa-cart-page .woocommerce-info,
    .wiwa-cart-page .woocommerce-error {
        border-radius: 12px;
        margin-bottom: 24px;
        font-family: 'Montserrat', sans-serif;
    }
</style>

<div class="wiwa-cart-page bg-[#f9f9f9] text-gray-800 antialiased">

    <!-- HEADER -->
    <div class="mb-12">
        <h1 class="text-3xl md:text-5xl font-bold text-[#1a3c28] mb-4 tracking-tight">
            <?php esc_html_e('Tu Carrito de Aventuras', 'wiwa-checkout'); ?>
        </h1>
        <div class="h-1 w-20 bg-[#1a3c28] mb-6"></div>
        <p class="text-gray-500 text-lg max-w-2xl font-light">
            <?php esc_html_e('Revisa tus próximas experiencias en la Sierra Nevada antes de confirmar tu reserva.', 'wiwa-checkout'); ?>
        </p>
    </div>

    <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

        <div class="flex flex-col lg:flex-row gap-10">

            <!-- =============== LEFT COLUMN: CART ITEMS =============== -->
            <section class="lg:w-[65%] xl:w-[68%] space-y-8" data-purpose="cart-items-list">
                <?php do_action('woocommerce_before_cart_table'); ?>
                <?php do_action('woocommerce_before_cart_contents'); ?>

                <?php
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                        $product_name      = $_product->get_name();
                        $is_tour           = $_product->is_type('ovatb_tour');

                        // Extract tour-specific meta from OvaTourBooking
                        $tour_meta = wiwa_extract_tour_meta($cart_item);
                        
                        // FIX: Use tour traveler count for stepper value if it's a tour
                        // OvaTB forces WC quantity to 1, but we want to show/edit the guest count
                        $qty_valid_value = $is_tour ? $tour_meta['travelers'] : $cart_item['quantity'];

                        // Prices
                        $unit_price    = floatval($_product->get_price());
                        $line_subtotal = floatval($cart_item['line_subtotal']);
                        $deposit_rate  = apply_filters('wiwa_deposit_rate', 0.30);
                        $deposit_val   = $line_subtotal * $deposit_rate;
                        $pending_val   = $line_subtotal - $deposit_val;

                        // Determine primary guest key for AJAX
                        $primary_guest_key = '';
                        if (!empty($tour_meta['guests_detail'])) {
                            $primary_guest_key = $tour_meta['guests_detail'][0]['name'];
                        }
                ?>

                <article class="bg-white rounded-2xl card-shadow p-5 md:p-8 flex flex-col md:flex-row gap-6 md:gap-8 items-start border border-gray-50 <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">

                    <!-- ===== IMAGE ===== -->
                    <div class="w-full md:w-44 lg:w-48 h-44 lg:h-48 flex-shrink-0 relative overflow-hidden rounded-xl wiwa-thumb-wrap">
                        <?php
                        $thumbnail = apply_filters(
                            'woocommerce_cart_item_thumbnail',
                            $_product->get_image('woocommerce_thumbnail'),
                            $cart_item,
                            $cart_item_key
                        );
                        if (!$product_permalink) {
                            echo $thumbnail;
                        } else {
                            printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                        }
                        ?>
                    </div>

                    <!-- ===== CONTENT (details + qty/price) ===== -->
                    <div class="flex flex-col md:flex-row flex-grow justify-between w-full gap-6">

                        <!-- LEFT: Name, Meta, Delete -->
                        <div class="flex flex-col justify-between md:w-[55%]">
                            <div>
                                <h2 class="text-xl md:text-2xl font-bold text-[#1a3c28] mb-4 leading-tight">
                                    <?php
                                    if (!$product_permalink) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $product_name, $cart_item, $cart_item_key));
                                    } else {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s" class="text-[#1a3c28] hover:underline">%s</a>', esc_url($product_permalink), $product_name), $cart_item, $cart_item_key));
                                    }
                                    ?>
                                </h2>

                                <!-- Meta: Calendar / Duration / Travelers -->
                                <div class="space-y-2.5 text-[13px] text-gray-500">
                                    <?php if (!empty($tour_meta['checkin'])) : ?>
                                    <div class="flex items-center gap-2.5">
                                        <span class="material-symbols-outlined text-[16px] text-gray-400">calendar_today</span>
                                        <span class="font-medium text-gray-700"><?php echo esc_html($tour_meta['checkin']); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($tour_meta['duration_label'])) : ?>
                                    <div class="flex items-center gap-2.5">
                                        <span class="material-symbols-outlined text-[16px] text-gray-400">schedule</span>
                                        <span class="font-medium text-gray-700"><?php echo esc_html($tour_meta['duration_label']); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-2.5">
                                        <span class="material-symbols-outlined text-[16px] text-gray-400">group</span>
                                        <span class="font-medium text-gray-700">
                                            <?php echo esc_html($tour_meta['travelers']); ?> <?php esc_html_e('Viajeros', 'wiwa-checkout'); ?>
                                        </span>
                                        <?php if ($product_permalink) : ?>
                                        <a href="<?php echo esc_url($product_permalink); ?>" class="text-[#2b4c3b] underline text-[11px] ml-0.5 hover:text-green-800" style="text-underline-offset:2px">
                                            <?php esc_html_e('Ver info', 'wiwa-checkout'); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- DELETE BUTTON -->
                            <div class="mt-5">
                                <?php
                                echo apply_filters(
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="flex items-center gap-1.5 text-red-500 hover:text-red-700 text-[13px] font-medium transition-colors" style="color: #ef4444 !important;" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span class="material-symbols-outlined text-[16px]" style="color: #ef4444 !important;">delete</span> %s</a>',
                                        esc_url(wc_get_cart_remove_url($cart_item_key)),
                                        esc_html__('Remove this item', 'woocommerce'),
                                        esc_attr($product_id),
                                        esc_attr($_product->get_sku()),
                                        esc_html__('Eliminar', 'wiwa-checkout')
                                    ),
                                    $cart_item_key
                                );
                                ?>
                            </div>
                        </div>

                        <!-- RIGHT: Quantity Stepper + Price Block -->
                        <div class="md:w-[45%] flex flex-col items-end justify-between">

                            <!-- QUANTITY STEPPER (Pill - clean, no red border) -->
                            <div class="flex flex-col items-center">
                                <?php if ($_product->is_sold_individually()) : ?>
                                    <div class="wiwa-stepper-pill" style="width:auto;padding:8px 16px;">
                                        <span class="font-bold text-[#1a3c28] text-sm">1</span>
                                    </div>
                                    <input type="hidden" name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" value="1" />
                                <?php else : ?>
                                    <div class="wiwa-stepper-pill">
                                        <button type="button"
                                                aria-label="<?php esc_attr_e('Decrease quantity', 'wiwa-checkout'); ?>"
                                                class="wiwa-qty-minus"
                                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>">−</button>
                                        <input type="number"
                                               class="wiwa-qty-input"
                                               name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]"
                                               value="<?php echo esc_attr($qty_valid_value); ?>"
                                               min="1"
                                               step="1"
                                               data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                               data-is-tour="<?php echo $is_tour ? '1' : '0'; ?>"
                                               data-guest-key="<?php echo esc_attr($primary_guest_key); ?>"
                                               readonly />
                                        <button type="button"
                                                aria-label="<?php esc_attr_e('Increase quantity', 'wiwa-checkout'); ?>"
                                                class="wiwa-qty-plus"
                                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                    </div>
                                <?php endif; ?>
                                <span class="text-[9px] text-gray-400 mt-1.5 uppercase tracking-[0.15em] font-semibold">
                                    <?php esc_html_e('Viajeros', 'wiwa-checkout'); ?>
                                </span>
                            </div>

                            <!-- PRICE BLOCK — harmonious sizing -->
                            <div class="text-right mt-4">
                                <p class="wiwa-price-per-person mb-0.5">
                                    <?php echo wc_price($unit_price); ?>
                                    <span class="opacity-70">/ persona</span>
                                </p>
                                <p class="wiwa-price-subtotal mb-0">
                                    <?php 
                                    $subtotal_html = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);
                                    // Split deposit/extra text (usually in parenthesis) to smaller line
                                    if (strpos($subtotal_html, '(') !== false) {
                                        // Regex to find first occurrence of ( and wrap till end or matching )
                                        // Simpler: Split at first (
                                        $parts = explode('(', $subtotal_html, 2);
                                        echo $parts[0];
                                        echo '<small class="block text-[11px] text-gray-400 font-normal mt-1 leading-tight">(' . $parts[1] . '</small>';
                                    } else {
                                        echo $subtotal_html;
                                    }
                                    ?>
                                </p>
                                <div class="mt-3 space-y-1.5">
                                    <div class="flex justify-end gap-2 text-[12px] text-gray-500">
                                        <span><?php esc_html_e('Depósito:', 'wiwa-checkout'); ?></span>
                                        <span class="font-semibold text-[#1a3c28]"><?php echo wc_price($deposit_val); ?></span>
                                    </div>
                                    <div class="flex justify-end gap-2 text-[12px]">
                                        <span class="text-gray-500"><?php esc_html_e('Saldo pendiente:', 'wiwa-checkout'); ?></span>
                                        <span class="font-bold text-red-600"><?php echo wc_price($pending_val); ?></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </article>

                <?php
                    }
                }
                ?>

                <?php do_action('woocommerce_cart_contents'); ?>

                <button type="submit" class="hidden" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>">
                    <?php esc_html_e('Update cart', 'woocommerce'); ?>
                </button>

                <?php do_action('woocommerce_after_cart_contents'); ?>
                <?php do_action('woocommerce_after_cart_table'); ?>
            </section>


            <!-- =============== RIGHT COLUMN: ORDER SUMMARY =============== -->
            <aside class="lg:w-[35%] xl:w-[32%]" data-purpose="order-summary">
                <div class="bg-white rounded-2xl card-shadow p-8 wiwa-sticky-sidebar border border-gray-50">

                    <h3 class="text-[12px] font-bold text-[#1a3c28] uppercase tracking-[0.2em] border-b border-gray-100 pb-5 mb-7">
                        <?php esc_html_e('Totales del Carrito', 'wiwa-checkout'); ?>
                    </h3>

                    <div class="space-y-5 mb-8">
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center text-gray-500 text-[14px]">
                            <span><?php esc_html_e('Subtotal experiencias', 'wiwa-checkout'); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_subtotal_html(); ?></span>
                        </div>

                        <?php
                        // --- Deposit logic for sidebar ---
                        $cart_subtotal_raw = WC()->cart->get_subtotal();
                        $sidebar_deposit_rate = apply_filters('wiwa_deposit_rate', 0.30);
                        $sidebar_deposit = $cart_subtotal_raw * $sidebar_deposit_rate;
                        $sidebar_pending = $cart_subtotal_raw - $sidebar_deposit;
                        ?>

                        <!-- Total pay today (Deposit) -->
                        <div class="flex justify-between items-center text-[#1a3c28] text-[14px]">
                            <span class="font-medium"><?php esc_html_e('Total a pagar hoy (Depósito)', 'wiwa-checkout'); ?></span>
                            <span class="font-bold"><?php echo wc_price($sidebar_deposit); ?></span>
                        </div>

                        <!-- Pending -->
                        <div class="pt-5 border-t border-dashed border-gray-200">
                            <div class="flex justify-between items-center text-red-600 text-[14px]">
                                <span><?php esc_html_e('Pendiente por pagar', 'wiwa-checkout'); ?></span>
                                <span class="font-bold"><?php echo wc_price($sidebar_pending); ?></span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5 leading-relaxed italic">
                                <?php esc_html_e('El saldo restante se pagará directamente en nuestras oficinas el día del tour.', 'wiwa-checkout'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- SHIPPING / FEES / COUPONS / TAXES (WC Standard) -->
                    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                        <div class="flex justify-between items-center text-gray-500 text-[14px] mb-3">
                            <span><?php esc_html_e('Envío', 'woocommerce'); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_shipping_html(); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php foreach (WC()->cart->get_fees() as $fee) : ?>
                        <div class="flex justify-between items-center text-gray-500 text-[14px] mb-3">
                            <span><?php echo esc_html($fee->name); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_fee_html($fee); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                        <div class="flex justify-between items-center text-green-600 text-[14px] mb-3">
                            <span><?php wc_cart_totals_coupon_label($coupon); ?></span>
                            <span class="font-semibold"><?php wc_cart_totals_coupon_html($coupon); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
                        <div class="flex justify-between items-center text-gray-500 text-[14px] mb-5">
                            <span><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_taxes_total_html(); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- BIG TOTAL + CTA -->
                    <div class="pt-7 border-t border-gray-100">
                        <div class="flex flex-col gap-1 mb-7">
                            <span class="text-gray-500 text-[13px] font-medium"><?php esc_html_e('Total de la reserva', 'wiwa-checkout'); ?></span>
                            <span class="text-3xl md:text-4xl font-bold text-[#1a3c28] tracking-tight">
                                <?php echo wp_kses_post(WC()->cart->get_total()); ?><span class="wiwa-currency-code"><?php echo esc_html($wiwa_currency_code); ?></span>
                            </span>
                        </div>

                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="wiwa-cta-pay">
                            <?php esc_html_e('Proceder al Pago', 'wiwa-checkout'); ?>
                        </a>

                        <div class="flex items-center justify-center gap-2 mt-5">
                            <span class="material-symbols-outlined text-[14px] text-gray-400">lock</span>
                            <span class="text-[10px] text-gray-400 uppercase font-medium tracking-wider"><?php esc_html_e('Pago Seguro SSL', 'wiwa-checkout'); ?></span>
                        </div>
                    </div>

                </div>

                <!-- COUPON CODE Box -->
                <?php if (wc_coupons_enabled()) : ?>
                <div class="mt-5 bg-[#fdfbf7] p-5 rounded-2xl border border-[#e5e7eb]/40">
                    <p class="text-[12px] font-semibold text-[#1a3c28] mb-2.5"><?php esc_html_e('¿Tienes un código de descuento?', 'wiwa-checkout'); ?></p>
                    <div class="flex gap-2">
                        <input type="text"
                               name="coupon_code"
                               class="flex-grow bg-white border-gray-200 rounded-lg text-sm px-4 py-2 focus:ring-[#1a3c28] focus:border-[#1a3c28]"
                               placeholder="<?php esc_attr_e('Ingresa tu código', 'woocommerce'); ?>" />
                        <button type="submit"
                                class="bg-[#1a3c28] text-white px-4 py-2 rounded-lg text-[11px] font-bold uppercase hover:opacity-90 transition"
                                name="apply_coupon"
                                value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>">
                            <?php esc_html_e('Aplicar', 'woocommerce'); ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

            </aside>

        </div>

    </form>

</div>

<?php do_action('woocommerce_after_cart'); ?>
