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




// --- Get current currency code (WOOCS compatible) ---
$wiwa_currency_code = get_woocommerce_currency(); // e.g. "COP", "USD"
?>



<div class="wiwa-cart-page bg-[#f9f9f9] text-gray-800 antialiased">

    <!-- HEADER -->
    <div class="mb-12">
        <h1 class="text-3xl md:text-5xl font-bold text-[#1a3c28] mb-4 tracking-tight">
            <?php esc_html_e('Your Adventure Cart', 'wiwa-checkout'); ?>
        </h1>
        <div class="h-1 w-20 bg-[#1a3c28] mb-6"></div>
        <p class="text-gray-500 text-lg max-w-2xl font-light">
            <?php esc_html_e('Review your upcoming experiences before confirming your booking.', 'wiwa-checkout'); ?>
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
                        // Function is defined in includes/wiwa-helpers.php or similar
                        $tour_meta = function_exists('wiwa_extract_tour_meta') ? wiwa_extract_tour_meta($cart_item) : [];
                        
                        // FIX: Use tour traveler count for stepper value if it's a tour
                        // OvaTB forces WC quantity to 1, but we want to show/edit the guest count
                        $qty_valid_value = $is_tour ? $tour_meta['travelers'] : $cart_item['quantity'];

                        // Prices
                        $line_subtotal_raw = floatval($cart_item['line_subtotal']);
                        
                        // Handle tax for display if needed
                        if (wc_tax_enabled() && WC()->cart->display_prices_including_tax()) {
                            $line_subtotal_raw += floatval($cart_item['line_subtotal_tax']);
                        }

                        // Fix: OvaTourBooking sets the product price to the Total Line Price (qty=1).
                        // We must recalculate the unit price (Price Per Person) by dividing Total by Travelers.
                        $pax_count  = $tour_meta['travelers'] > 0 ? $tour_meta['travelers'] : 1;
                        $unit_price = $line_subtotal_raw / $pax_count;

                        // Calculations for deposit
                        $has_deposit = false;
                        $deposit_val = $line_subtotal_raw;
                        $pending_val = 0;

                        if ($is_tour && $_product->get_meta('pay_deposit')) {
                            $has_deposit   = true;
                            $total_payable = floatval($_product->get_meta('total_payable'));
                            
                            // Tax enabled handling (base currency)
                            if (wc_tax_enabled() && $_product->is_taxable()) {
                                if (wc_prices_include_tax()) {
                                    if (!WC()->cart->display_prices_including_tax()) {
                                        $total_payable = wc_get_price_excluding_tax($_product, ['price' => $total_payable]);
                                    }
                                } else {
                                    if (WC()->cart->display_prices_including_tax()) {
                                        $total_payable = wc_get_price_including_tax($_product, ['price' => $total_payable]);
                                    }
                                }
                            }
                            
                            // Convert to active currency
                            if (class_exists('Wiwa_FOX_Integration') && Wiwa_FOX_Integration::is_active()) {
                                $total_payable = Wiwa_FOX_Integration::convert_price($total_payable);
                            } else {
                                $total_payable = apply_filters('woocs_exchange_value', $total_payable);
                            }
                            
                            $pending_val = max(0, $total_payable - $line_subtotal_raw);
                        }

                        // Determine primary guest key for AJAX
                        $primary_guest_key = '';
                        if (!empty($tour_meta['guests_detail'])) {
                            $primary_guest_key = $tour_meta['guests_detail'][0]['name'];
                        }
                ?>

                <article class="bg-white rounded-2xl card-shadow p-4 md:p-8 flex flex-col md:flex-row md:gap-8 items-start border border-gray-50 <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">

                    <!-- ===== TOP ROW FOR MOBILE: IMAGE + BASIC INFO ===== -->
                    <div class="flex flex-row w-full gap-4 md:contents">
                        <!-- ===== IMAGE ===== -->
                        <div class="w-24 h-24 sm:w-28 sm:h-28 md:w-44 lg:w-48 md:h-44 lg:h-48 flex-shrink-0 relative overflow-hidden rounded-xl wiwa-thumb-wrap">
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

                        <!-- ===== INFO COLUMN (Name & Meta) ===== -->
                        <div class="flex flex-col justify-between flex-grow md:w-[55%]">
                            <div>
                                <h2 class="text-base sm:text-lg md:text-2xl font-bold text-[#1a3c28] mb-2 md:mb-4 leading-tight">
                                    <?php
                                    if (!$product_permalink) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $product_name, $cart_item, $cart_item_key));
                                    } else {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s" class="text-[#1a3c28] hover:text-[#2b4c3b] no-underline hover:no-underline">%s</a>', esc_url($product_permalink), $product_name), $cart_item, $cart_item_key));
                                    }
                                    ?>
                                </h2>

                                <!-- Meta: Calendar / Duration / Travelers -->
                                <div class="space-y-1 md:space-y-2.5 text-[12px] md:text-[13px] text-gray-500">
                                    <?php if (!empty($tour_meta['checkin'])) : ?>
                                    <div class="flex items-center gap-1.5 md:gap-2.5">
                                        <span class="material-symbols-outlined text-[14px] md:text-[16px] text-gray-400">calendar_today</span>
                                        <span class="font-medium text-gray-700"><?php echo esc_html($tour_meta['checkin']); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($tour_meta['duration_label'])) : ?>
                                    <div class="flex items-center gap-1.5 md:gap-2.5">
                                        <span class="material-symbols-outlined text-[14px] md:text-[16px] text-gray-400">schedule</span>
                                        <span class="font-medium text-gray-700"><?php echo esc_html($tour_meta['duration_label']); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="flex items-center gap-1.5 md:gap-2.5">
                                        <span class="material-symbols-outlined text-[14px] md:text-[16px] text-gray-400">group</span>
                                        <span class="font-medium text-gray-700">
                                            <?php echo esc_html($tour_meta['travelers']); ?> <?php esc_html_e('Travelers', 'wiwa-checkout'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DESKTOP DELETE BUTTON (Hidden on mobile) -->
                            <div class="hidden md:block mt-5">
                                <?php
                                echo apply_filters(
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="flex items-center gap-1.5 text-red-500 hover:text-red-700 text-[13px] font-medium transition-colors no-underline hover:no-underline" style="color: #ef4444 !important;" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span class="material-symbols-outlined text-[16px]" style="color: #ef4444 !important;">delete</span> %s</a>',
                                        esc_url(wc_get_cart_remove_url($cart_item_key)),
                                        esc_html__('Remove this item', 'woocommerce'),
                                        esc_attr($product_id),
                                        esc_attr($_product->get_sku()),
                                        esc_html__('Delete', 'wiwa-checkout')
                                    ),
                                    $cart_item_key
                                );
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- ===== BOTTOM DIVIDER FOR MOBILE ===== -->
                    <div class="w-full block md:hidden border-b border-gray-100 my-4"></div>

                    <!-- ===== CONTENT COLUMN (Qty, Price, Actions) ===== -->
                    <div class="w-full md:w-[45%] flex flex-col items-center md:items-end justify-between">
                        
                        <!-- Mobile Flex Row: Delete on left, Stepper on right / Desktop: Stepper top right -->
                        <div class="flex flex-row md:flex-col justify-between items-center md:items-end w-full">
                            <!-- MOBILE DELETE BUTTON -->
                            <div class="block md:hidden">
                                <?php
                                echo apply_filters(
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="flex items-center justify-center p-2 rounded-lg bg-red-50 text-red-500 hover:text-red-700 text-[12px] font-medium transition-colors border border-red-100 no-underline hover:no-underline" style="color: #ef4444 !important;" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span class="material-symbols-outlined text-[18px]" style="color: #ef4444 !important;">delete</span></a>',
                                        esc_url(wc_get_cart_remove_url($cart_item_key)),
                                        esc_html__('Remove this item', 'woocommerce'),
                                        esc_attr($product_id),
                                        esc_attr($_product->get_sku())
                                    ),
                                    $cart_item_key
                                );
                                ?>
                            </div>

                            <!-- QUANTITY STEPPER -->
                            <div class="flex flex-col items-center">
                                <?php if ($_product->is_sold_individually()) : ?>
                                    <div class="wiwa-stepper-pill" style="width:auto;padding:6px 16px;">
                                        <span class="font-bold text-[#1a3c28] text-sm lg:text-base">1</span>
                                    </div>
                                    <input type="hidden" name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" value="1" />
                                <?php else : ?>
                                    <!-- Use scale to make it slightly smaller on mobile without changing the CSS component logic -->
                                    <div class="wiwa-stepper-pill transform scale-[0.85] md:scale-100 origin-center md:origin-right">
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
                                <span class="text-[8px] md:text-[9px] text-gray-400 mt-1 uppercase tracking-[0.15em] font-semibold">
                                    <?php esc_html_e('Travelers', 'wiwa-checkout'); ?>
                                </span>
                            </div>
                        </div>

                        <!-- PRICE BLOCK -->
                        <div class="text-right mt-4 w-full md:w-auto">
                            <div class="flex flex-row md:flex-col items-end justify-between md:justify-end border-t border-gray-100 pt-3 md:border-0 md:pt-0">
                                <p class="wiwa-price-per-person mb-0.5 text-[12px] md:text-sm text-gray-500 font-medium whitespace-nowrap hidden md:block">
                                    <?php echo wc_price($unit_price); ?>
                                    <span class="opacity-70">/ persona</span>
                                </p>
                                <p class="wiwa-price-subtotal leading-none mb-0 text-xl font-bold flex flex-col md:block text-[#1a3c28]">
                                    <span class="block md:hidden text-[10px] text-gray-400 font-normal uppercase tracking-wide mb-1"><?php esc_html_e('Total Travelers:', 'wiwa-checkout'); ?></span>
                                    <?php 
                                    $subtotal_html = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);
                                    if (strpos($subtotal_html, '(') !== false) {
                                        $parts = explode('(', $subtotal_html, 2);
                                        echo $parts[0];
                                        echo '<small class="block text-[10px] md:text-[11px] text-gray-400 font-normal mt-1 leading-tight">(' . $parts[1] . '</small>';
                                    } else {
                                        echo $subtotal_html;
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <!-- Mobile-only Unit Price (below total if needed, or hidden) -->
                            <p class="wiwa-price-per-person block md:hidden text-[11px] text-gray-500 font-medium text-right mt-1">
                                <?php echo wc_price($unit_price); ?> <span class="opacity-70">/ persona</span>
                            </p>

                            <?php if ($has_deposit && $pending_val > 0): ?>
                            <div class="mt-3 md:mt-4 space-y-1 md:space-y-1.5 p-3 md:p-0 bg-gray-50 md:bg-transparent rounded-lg md:rounded-none">
                                <div class="flex justify-between md:justify-end gap-2 text-[11px] md:text-[12px] text-gray-500">
                                    <span><?php esc_html_e('Deposit:', 'wiwa-checkout'); ?></span>
                                    <span class="font-semibold text-[#1a3c28] md:text-[13px]"><?php echo wc_price($deposit_val); ?></span>
                                </div>
                                <div class="flex justify-between md:justify-end gap-2 text-[11px] md:text-[12px]">
                                    <span class="text-gray-500"><?php esc_html_e('Pending balance:', 'wiwa-checkout'); ?></span>
                                    <span class="font-bold text-red-600 md:text-[13px]"><?php echo wc_price($pending_val); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
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
                        <?php esc_html_e('Cart Totals', 'wiwa-checkout'); ?>
                    </h3>

                    <div class="space-y-5 mb-8">
                        <!-- Subtotal -->
                        <div class="flex justify-between items-center text-gray-500 text-[14px]">
                            <span><?php esc_html_e('Experiences subtotal', 'wiwa-checkout'); ?></span>
                            <span class="font-semibold text-gray-800"><?php wc_cart_totals_subtotal_html(); ?></span>
                        </div>

                        <?php
                        // --- Deposit logic for sidebar ---
                        $sidebar_deposit = (float) WC()->cart->total;
                        $sidebar_pending = 0;
                        $have_deposit = false;

                        if (isset(WC()->cart->deposit_data) && !empty(WC()->cart->deposit_data)) {
                            if (function_exists('ovatb_get_meta_data')) {
                                $have_deposit = (bool) ovatb_get_meta_data('have_deposit', WC()->cart->deposit_data);
                                if ($have_deposit) {
                                    $sidebar_pending = (float) ovatb_get_meta_data('remaining_total', WC()->cart->deposit_data);
                                }
                            }
                        }

                        // Convert pending to active currency
                        if ($sidebar_pending > 0 && class_exists('Wiwa_FOX_Integration') && Wiwa_FOX_Integration::is_active()) {
                            $converted_pending = Wiwa_FOX_Integration::convert_price($sidebar_pending);
                            $pending_price_html = Wiwa_FOX_Integration::format_price($sidebar_pending);
                        } else {
                            $converted_pending = (float) apply_filters('woocs_exchange_value', $sidebar_pending);
                            $pending_price_html = wc_price($sidebar_pending);
                        }

                        $grand_total_active = $sidebar_deposit + $converted_pending;

                        // Currency code
                        $active_currency_code = '';
                        if (class_exists('Wiwa_FOX_Integration') && Wiwa_FOX_Integration::is_active()) {
                            $active_currency_code = Wiwa_FOX_Integration::get_current_currency();
                        } else {
                            $active_currency_code = isset($wiwa_currency_code) ? $wiwa_currency_code : get_woocommerce_currency();
                        }
                        ?>
                    </div>

                    <?php if ($have_deposit && $sidebar_pending > 0) : ?>
                    <!-- ══════ DEPOSIT BREAKDOWN ══════ -->
                    <div class="wiwa-deposit-breakdown">

                        <!-- 1. TODAY'S PAYMENT (highest hierarchy) -->
                        <div class="wiwa-deposit-today">
                            <div class="wiwa-deposit-today__header">
                                <span class="wiwa-deposit-today__icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </span>
                                <span class="wiwa-deposit-today__label"><?php esc_html_e("You pay today", 'wiwa-checkout'); ?></span>
                                <span class="wiwa-deposit-today__badge"><?php esc_html_e('Deposit', 'wiwa-checkout'); ?></span>
                            </div>
                            <div class="wiwa-deposit-today__amount">
                                <?php echo wc_price($sidebar_deposit); ?>
                                <span class="wiwa-deposit-today__currency"><?php echo esc_html($active_currency_code); ?></span>
                            </div>
                        </div>

                        <!-- 2. PENDING BALANCE (secondary, warning-like) -->
                        <div class="wiwa-deposit-pending">
                            <div class="wiwa-deposit-pending__row">
                                <span class="wiwa-deposit-pending__label">
                                    <svg class="wiwa-deposit-pending__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <?php esc_html_e('Remaining balance', 'wiwa-checkout'); ?>
                                </span>
                                <span class="wiwa-deposit-pending__amount"><?php echo wp_kses_post($pending_price_html); ?></span>
                            </div>
                            <p class="wiwa-deposit-pending__note">
                                <?php esc_html_e('Paid on the day of the tour at our offices.', 'wiwa-checkout'); ?>
                            </p>
                        </div>

                        <!-- 3. GRAND TOTAL (tertiary, informational) -->
                        <div class="wiwa-deposit-grand">
                            <span class="wiwa-deposit-grand__label"><?php esc_html_e('Total booking value', 'wiwa-checkout'); ?></span>
                            <span class="wiwa-deposit-grand__amount"><?php echo wp_kses_post(wc_price($grand_total_active)); ?></span>
                        </div>
                    </div>

                    <?php else : ?>
                    <!-- ══════ NO DEPOSIT (simple total) ══════ -->
                    <div class="pt-7 border-t border-gray-100">
                        <div class="flex flex-col gap-1 mb-7">
                            <span class="text-gray-500 text-[13px] font-medium"><?php esc_html_e('Total', 'wiwa-checkout'); ?></span>
                            <span class="text-3xl md:text-4xl font-bold text-[#1a3c28] tracking-tight">
                                <?php echo wp_kses_post(wc_price($sidebar_deposit)); ?>
                                <span class="wiwa-currency-code"><?php echo esc_html($active_currency_code); ?></span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- SHIPPING / FEES / COUPONS / TAXES (WC Standard) -->
                    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                        <div class="flex justify-between items-center text-gray-500 text-[14px] mb-3">
                            <span><?php esc_html_e('Shipping', 'wiwa-checkout'); ?></span>
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

                    <!-- CTA Button -->
                    <div class="<?php echo ($have_deposit && $sidebar_pending > 0) ? 'pt-4' : ''; ?>">
                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="wiwa-cta-pay">
                            <?php esc_html_e('Proceed to Payment', 'wiwa-checkout'); ?>
                        </a>

                        <div class="flex items-center justify-center gap-2 mt-5">
                            <span class="material-symbols-outlined text-[14px] text-gray-400">lock</span>
                            <span class="text-[10px] text-gray-400 uppercase font-medium tracking-wider"><?php esc_html_e('Secure SSL Payment', 'wiwa-checkout'); ?></span>
                        </div>
                    </div>

                </div>

                <!-- COUPON CODE Box -->
                <?php if (wc_coupons_enabled()) : ?>
                <div class="mt-5 bg-[#fdfbf7] p-5 rounded-2xl border border-[#e5e7eb]/40">
                    <p class="text-[12px] font-semibold text-[#1a3c28] mb-2.5"><?php esc_html_e('Have a discount code?', 'wiwa-checkout'); ?></p>
                    <div class="flex gap-2">
                        <input type="text"
                               name="coupon_code"
                               class="flex-grow bg-white border-gray-200 rounded-lg text-sm px-4 py-2 focus:ring-[#1a3c28] focus:border-[#1a3c28]"
                               placeholder="<?php esc_attr_e('Enter your code', 'wiwa-checkout'); ?>" />
                        <button type="submit"
                                class="bg-[#1a3c28] text-white px-4 py-2 rounded-lg text-[11px] font-bold uppercase hover:opacity-90 transition"
                                name="apply_coupon"
                                value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>">
                            <?php esc_html_e('Apply', 'wiwa-checkout'); ?>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

            </aside>

        </div>

    </form>

</div>

<?php do_action('woocommerce_after_cart'); ?>
