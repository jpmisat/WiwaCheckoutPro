<?php
/**
 * Wiwa Tour Checkout - Premium Cart Template (Stitch Pixel-Perfect)
 * Version: 2.11.0
 * 
 * Matches the code-stich.html design exactly, integrated with
 * WooCommerce + OvaTourBooking data sources.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');

/**
 * Helper: Extract tour meta from cart item (OvaTourBooking compatible).
 * Returns ['checkin' => string, 'duration' => string, 'travelers' => int]
 */
function wiwa_extract_tour_meta($cart_item) {
    $meta = [
        'checkin'   => '',
        'duration'  => '',
        'travelers' => $cart_item['quantity'],
    ];

    // OvaTourBooking stores data in various keys
    $item = $cart_item;
    if (isset($cart_item['ovatb_data']) && is_array($cart_item['ovatb_data'])) {
        $item = array_merge($item, $cart_item['ovatb_data']);
    }

    // --- Check-in Date ---
    $date_keys = ['checkin', 'start_date', 'date', 'tour_date', 'fecha', 'ovatb_checkin', 'ovatb_start_date'];
    foreach ($date_keys as $dk) {
        if (!empty($item[$dk])) {
            $ts = strtotime($item[$dk]);
            $meta['checkin'] = $ts ? date_i18n('d-m-Y', $ts) : sanitize_text_field($item[$dk]);
            break;
        }
    }

    // --- Duration ---
    $dur_keys = ['duration', 'tour_duration', 'ovatb_duration'];
    foreach ($dur_keys as $duk) {
        if (!empty($item[$duk])) {
            $meta['duration'] = sanitize_text_field($item[$duk]);
            break;
        }
    }

    // --- Travelers / Passengers total ---
    $pax_keys = ['numberof_adult', 'numberof_adults', 'numberof_pax', 'numberof_guests'];
    $total_pax = 0;
    foreach ($pax_keys as $pk) {
        if (isset($item[$pk]) && intval($item[$pk]) > 0) {
            $total_pax += intval($item[$pk]);
        }
    }
    // Also count children / infants
    $child_keys = ['numberof_child', 'numberof_childs', 'numberof_children'];
    foreach ($child_keys as $ck) {
        if (isset($item[$ck]) && intval($item[$ck]) > 0) {
            $total_pax += intval($item[$ck]);
        }
    }
    if ($total_pax > 0) {
        $meta['travelers'] = $total_pax;
    }

    return $meta;
}
?>

<style>
    /* ===== Stitch Cart Inline Styles ===== */
    .wiwa-cart-page {
        font-family: 'Montserrat', 'Roboto', sans-serif;
    }
    .card-shadow {
        box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.02);
    }
    .stepper-btn {
        transition: all 0.2s ease;
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
    }
    .wiwa-cart-page .wiwa-thumb-wrap a {
        display: block;
        width: 100%;
        height: 100%;
    }

    /* Hide default WC elements that conflict */
    .wiwa-cart-page .product-remove,
    .wiwa-cart-page .product-thumbnail,
    .wiwa-cart-page .product-name dl,
    .wiwa-cart-page .woocommerce-cart-form__cart-item {
        /* We rebuild everything */
    }

    /* Override WC price wrapping */
    .wiwa-cart-page .woocommerce-Price-amount {
        font-family: inherit;
    }

    /* Ensure sticky sidebar stays in view */
    .wiwa-sticky-sidebar {
        position: sticky;
        top: 8rem;
    }

    /* Smooth hover on cards */
    .wiwa-cart-page article:hover {
        box-shadow: 0 8px 30px -4px rgba(0,0,0,0.08), 0 4px 8px -2px rgba(0,0,0,0.04);
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

        <div class="flex flex-col lg:flex-row gap-12">

            <!-- =============== LEFT COLUMN: CART ITEMS =============== -->
            <section class="lg:w-2/3 space-y-8" data-purpose="cart-items-list">
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

                        // Extract tour-specific meta
                        $tour_meta = wiwa_extract_tour_meta($cart_item);
                        $qty_value = $cart_item['quantity'];

                        // Prices
                        $unit_price    = floatval($_product->get_price());
                        $line_subtotal = floatval($cart_item['line_subtotal']);
                        $deposit_rate  = apply_filters('wiwa_deposit_rate', 0.30);
                        $deposit_val   = $line_subtotal * $deposit_rate;
                        $pending_val   = $line_subtotal - $deposit_val;
                ?>

                <article class="bg-white rounded-2xl card-shadow p-5 md:p-8 flex flex-col md:flex-row gap-8 items-start border border-gray-50 transition-shadow duration-300 <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">

                    <!-- ===== IMAGE ===== -->
                    <div class="w-full md:w-56 h-56 flex-shrink-0 relative overflow-hidden rounded-xl wiwa-thumb-wrap">
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
                        <?php if ($is_tour) : ?>
                        <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full shadow-sm">
                            <span class="text-[10px] font-bold text-[#1a3c28] uppercase tracking-tighter">Popular</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ===== CONTENT (details + qty/price) ===== -->
                    <div class="flex flex-col md:flex-row flex-grow justify-between w-full h-full min-h-[14rem]">

                        <!-- LEFT: Name, Meta, Delete -->
                        <div class="flex flex-col justify-between md:w-1/2">
                            <div>
                                <h2 class="text-2xl font-bold text-[#1a3c28] mb-4 leading-tight">
                                    <?php
                                    if (!$product_permalink) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $product_name, $cart_item, $cart_item_key));
                                    } else {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s" class="text-[#1a3c28] hover:underline">%s</a>', esc_url($product_permalink), $product_name), $cart_item, $cart_item_key));
                                    }
                                    ?>
                                </h2>

                                <!-- Meta: Calendar / Duration / Travelers -->
                                <div class="space-y-3 text-[14px] text-gray-500">
                                    <?php if (!empty($tour_meta['checkin'])) : ?>
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                                        <span class="font-medium text-gray-700"><?php echo esc_html($tour_meta['checkin']); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($tour_meta['duration'])) : ?>
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-[18px]">schedule</span>
                                        <span class="font-medium text-gray-700"><?php echo esc_html($tour_meta['duration']); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-[18px]">group</span>
                                        <div class="flex items-center gap-1">
                                            <span class="font-medium text-gray-700">
                                                <?php echo esc_html($tour_meta['travelers']); ?> <?php esc_html_e('Viajeros', 'wiwa-checkout'); ?>
                                            </span>
                                            <?php if ($product_permalink) : ?>
                                            <a href="<?php echo esc_url($product_permalink); ?>" class="text-[#2b4c3b] underline text-[11px] ml-1 hover:text-green-800">
                                                <?php esc_html_e('Ver info', 'wiwa-checkout'); ?>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DELETE BUTTON -->
                            <div class="mt-6">
                                <?php
                                echo apply_filters(
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="flex items-center gap-2 text-red-500 hover:text-red-700 text-sm font-medium transition-colors" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span class="material-symbols-outlined text-[18px]">delete</span> %s</a>',
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
                        <div class="mt-8 md:mt-0 md:w-1/2 flex flex-col items-end justify-between">

                            <!-- QUANTITY STEPPER (Pill) -->
                            <div class="flex flex-col items-center">
                                <?php if ($_product->is_sold_individually()) : ?>
                                    <span class="font-bold text-[#1a3c28] text-sm">1</span>
                                    <input type="hidden" name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" value="1" />
                                <?php else : ?>
                                    <div class="flex items-center bg-[#f9f9f9] border border-gray-200 rounded-full p-1 w-32 justify-between">
                                        <button type="button"
                                                aria-label="<?php esc_attr_e('Decrease quantity', 'wiwa-checkout'); ?>"
                                                class="stepper-btn w-8 h-8 flex items-center justify-center rounded-full hover:bg-white hover:shadow-sm text-gray-600 transition-all wiwa-qty-minus"
                                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>">-</button>
                                        <input type="number"
                                               class="font-bold text-[#1a3c28] text-sm bg-transparent border-none text-center w-10 p-0 focus:ring-0 wiwa-qty-input"
                                               name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]"
                                               value="<?php echo esc_attr($qty_value); ?>"
                                               min="0"
                                               step="1"
                                               data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                               data-is-tour="<?php echo $is_tour ? '1' : '0'; ?>"
                                               readonly />
                                        <button type="button"
                                                aria-label="<?php esc_attr_e('Increase quantity', 'wiwa-checkout'); ?>"
                                                class="stepper-btn w-8 h-8 flex items-center justify-center rounded-full hover:bg-white hover:shadow-sm text-gray-600 transition-all wiwa-qty-plus"
                                                data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                    </div>
                                <?php endif; ?>
                                <span class="text-[10px] text-gray-400 mt-2 uppercase tracking-widest font-semibold">
                                    <?php esc_html_e('Viajeros', 'wiwa-checkout'); ?>
                                </span>
                            </div>

                            <!-- PRICE BLOCK -->
                            <div class="text-right mt-6">
                                <p class="text-[13px] text-gray-400 mb-1">
                                    <?php echo wc_price($unit_price); ?>
                                    <span class="opacity-70">/ persona</span>
                                </p>
                                <p class="text-3xl font-bold text-[#1a3c28]">
                                    <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                                </p>
                                <div class="mt-4 space-y-2">
                                    <div class="flex justify-end gap-3 text-sm text-gray-500">
                                        <span><?php esc_html_e('Depósito:', 'wiwa-checkout'); ?></span>
                                        <span class="font-semibold text-[#1a3c28]"><?php echo wc_price($deposit_val); ?></span>
                                    </div>
                                    <div class="flex justify-end gap-3 text-sm">
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
            <aside class="lg:w-1/3" data-purpose="order-summary">
                <div class="bg-white rounded-2xl card-shadow p-8 wiwa-sticky-sidebar border border-gray-50">

                    <h3 class="text-[13px] font-bold text-[#1a3c28] uppercase tracking-[0.2em] border-b border-gray-100 pb-6 mb-8">
                        <?php esc_html_e('Totales del Carrito', 'wiwa-checkout'); ?>
                    </h3>

                    <div class="space-y-6 mb-10">
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center text-gray-500 text-[15px]">
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
                        <div class="flex justify-between items-center text-[#1a3c28] text-[15px]">
                            <span class="font-medium"><?php esc_html_e('Total a pagar hoy (Depósito)', 'wiwa-checkout'); ?></span>
                            <span class="font-bold"><?php echo wc_price($sidebar_deposit); ?></span>
                        </div>

                        <!-- Pending -->
                        <div class="pt-6 border-t border-dashed border-gray-200">
                            <div class="flex justify-between items-center text-red-600 text-[15px]">
                                <span><?php esc_html_e('Pendiente por pagar', 'wiwa-checkout'); ?></span>
                                <span class="font-bold"><?php echo wc_price($sidebar_pending); ?></span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-2 leading-relaxed italic">
                                <?php esc_html_e('El saldo restante se pagará directamente en nuestras oficinas el día del tour.', 'wiwa-checkout'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- SHIPPING / FEES / COUPONS / TAXES (WC Standard) -->
                    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                        <div class="flex justify-between items-center text-gray-500 text-[15px] mb-4">
                            <span><?php esc_html_e('Envío', 'woocommerce'); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_shipping_html(); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php foreach (WC()->cart->get_fees() as $fee) : ?>
                        <div class="flex justify-between items-center text-gray-500 text-[15px] mb-4">
                            <span><?php echo esc_html($fee->name); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_fee_html($fee); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                        <div class="flex justify-between items-center text-green-600 text-[15px] mb-4">
                            <span><?php wc_cart_totals_coupon_label($coupon); ?></span>
                            <span class="font-semibold"><?php wc_cart_totals_coupon_html($coupon); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
                        <div class="flex justify-between items-center text-gray-500 text-[15px] mb-6">
                            <span><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_taxes_total_html(); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- BIG TOTAL + CTA -->
                    <div class="pt-8 border-t border-gray-100">
                        <div class="flex flex-col gap-2 mb-8">
                            <span class="text-gray-500 text-sm font-medium"><?php esc_html_e('Total de la reserva', 'wiwa-checkout'); ?></span>
                            <span class="text-5xl font-bold text-[#1a3c28] tracking-tight">
                                <?php echo wp_kses_post(WC()->cart->get_total()); ?>
                            </span>
                        </div>

                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>"
                           class="block text-center w-full bg-[#1a3c28] hover:bg-[#2b4c3b] text-white font-bold py-5 rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 duration-300 text-[14px] uppercase tracking-widest">
                            <?php esc_html_e('Proceder al Pago', 'wiwa-checkout'); ?>
                        </a>

                        <div class="flex items-center justify-center gap-2 mt-6">
                            <span class="material-symbols-outlined text-[16px] text-gray-400">lock</span>
                            <span class="text-[11px] text-gray-400 uppercase font-medium tracking-tighter"><?php esc_html_e('Pago Seguro SSL', 'wiwa-checkout'); ?></span>
                        </div>
                    </div>

                </div>

                <!-- COUPON CODE Box -->
                <?php if (wc_coupons_enabled()) : ?>
                <div class="mt-6 bg-[#fdfbf7] p-6 rounded-2xl border border-[#e5e7eb]/40">
                    <p class="text-[13px] font-semibold text-[#1a3c28] mb-3"><?php esc_html_e('¿Tienes un código de descuento?', 'wiwa-checkout'); ?></p>
                    <div class="flex gap-2">
                        <input type="text"
                               name="coupon_code"
                               class="flex-grow bg-white border-gray-200 rounded-lg text-sm px-4 py-2 focus:ring-[#1a3c28] focus:border-[#1a3c28]"
                               placeholder="<?php esc_attr_e('Ingresa tu código', 'woocommerce'); ?>" />
                        <button type="submit"
                                class="bg-[#1a3c28] text-white px-4 py-2 rounded-lg text-[12px] font-bold uppercase hover:opacity-90 transition"
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
