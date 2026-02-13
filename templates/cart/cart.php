<?php
/**
 * Wiwa Tour Checkout - Premium Cart Template (Stitch Sync)
 * Version: 2.10.10
 * 
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); 
?>

<div class="mb-12">
    <h1 class="text-3xl md:text-5xl font-bold text-wiwa-green mb-4 tracking-tight"><?php esc_html_e('Tu Carrito de Aventuras', 'wiwa-checkout'); ?></h1>
    <div class="h-1 w-20 bg-wiwa-green mb-6"></div>
    <p class="text-gray-500 text-lg max-w-2xl font-light"><?php esc_html_e('Revisa tus próximas experiencias en la Sierra Nevada antes de confirmar tu reserva.', 'wiwa-checkout'); ?></p>
</div>

<form class="woocommerce-cart-form flex flex-col lg:flex-row gap-12" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
    
    <!-- LEFT COLUMN: CART ITEMS -->
    <section class="lg:w-2/3 space-y-8" data-purpose="cart-items-list">
        <?php do_action('woocommerce_before_cart_table'); ?>
        <?php do_action('woocommerce_before_cart_contents'); ?>

        <?php
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
            $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

            if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                
                // --- DATA PREP ---
                // Try to find Check-in Date and Duration in item data
                $checkin_date = ''; 
                $duration = '';
                // Common keys used by booking plugins
                $possible_date_keys = ['checkin', 'date', 'start_date', 'fecha', 'tour_date'];
                
                // Helper to search in item data
                $item_data_raw = $cart_item;
                // Add booking data if flattened
                if(isset($cart_item['ovatb_data'])) {
                    $item_data_raw = array_merge($item_data_raw, $cart_item['ovatb_data']);
                }

                // Retrieve formated meta for display
                $formatted_meta = wc_get_formatted_cart_item_data($cart_item);
                
                ?>
                
                <article class="bg-white rounded-2xl card-shadow p-5 md:p-8 flex flex-col md:flex-row gap-8 items-start border border-gray-50 <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                    
                    <!-- IMAGE -->
                    <div class="w-full md:w-56 h-56 flex-shrink-0 relative overflow-hidden rounded-xl">
                        <?php
                        $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_thumbnail', ['class' => 'w-full h-full object-cover transform hover:scale-105 transition duration-700']), $cart_item, $cart_item_key);
                        
                        if (!$product_permalink) {
                            echo $thumbnail; 
                        } else {
                            printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                        }
                        ?>
                        <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full shadow-sm">
                            <span class="text-[10px] font-bold text-wiwa-green uppercase tracking-tighter">Popular</span>
                        </div>
                    </div>

                    <!-- CONTENT -->
                    <div class="flex flex-col md:flex-row flex-grow justify-between w-full h-full min-h-[14rem]">
                        <div class="flex flex-col justify-between md:w-1/2">
                            <div>
                                <h2 class="text-2xl font-bold text-wiwa-green mb-4 leading-tight">
                                    <?php
                                    if (!$product_permalink) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key));
                                    } else {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
                                    }
                                    ?>
                                </h2>

                                <!-- META DATA (Date, Duration, etc) -->
                                <div class="space-y-3 text-[14px] text-gray-500">
                                    <!-- We output the WC formatted data here, wrapped in our style -->
                                    <div class="wiwa-meta-container">
                                        <?php echo $formatted_meta; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- REMOVE LINK -->
                            <div class="mt-6">
                            <?php
                                echo apply_filters(
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="flex items-center gap-2 text-red-500 hover:text-red-700 text-sm font-medium transition-colors" aria-label="%s" data-product_id="%s" data-product_sku="%s">
                                            <span class="material-symbols-outlined text-[18px]">delete</span> %s
                                        </a>',
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

                        <!-- RIGHT SIDE: QTY & PRICE -->
                        <div class="mt-8 md:mt-0 md:w-1/2 flex flex-col items-end justify-between">
                            
                            <!-- QUANTITY STEPPER -->
                            <div class="flex flex-col items-center">
                                <?php
                                if ($_product->is_sold_individually()) {
                                    $product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
                                } else {
                                    /* Custom Stepper HTML */
                                    $min_qty = 0;
                                    $max_qty = $_product->get_max_purchase_quantity();
                                    $quantity_value = $cart_item['quantity'];
                                    
                                    // Use the existing custom quantity function logic but output specific HTML
                                    ob_start();
                                    ?>
                                    <div class="flex items-center bg-wiwa-bg border border-gray-200 rounded-full p-1 w-32 justify-between">
                                        <button type="button" class="stepper-btn w-8 h-8 flex items-center justify-center rounded-full hover:bg-white hover:shadow-sm text-gray-600 transition-all wiwa-qty-minus">-</button>
                                        <input type="number" 
                                               class="font-bold text-wiwa-green text-sm bg-transparent border-none text-center w-10 p-0 focus:ring-0 wiwa-qty-input" 
                                               name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" 
                                               value="<?php echo esc_attr($quantity_value); ?>" 
                                               min="<?php echo esc_attr($min_qty); ?>" 
                                               step="1" 
                                               data-cart-key="<?php echo esc_attr($cart_item_key); ?>" 
                                               readonly />
                                        <button type="button" class="stepper-btn w-8 h-8 flex items-center justify-center rounded-full hover:bg-white hover:shadow-sm text-gray-600 transition-all wiwa-qty-plus">+</button>
                                    </div>
                                    <?php
                                    echo ob_get_clean();
                                }
                                ?>
                                <span class="text-[10px] text-gray-400 mt-2 uppercase tracking-widest font-semibold">Viajeros</span>
                            </div>

                            <!-- PRICE BLOCK -->
                            <div class="text-right mt-6">
                                <p class="text-[13px] text-gray-400 mb-1">
                                    <?php echo WC()->cart->get_product_price($_product); ?> 
                                    <span class="opacity-70">/ persona</span>
                                </p>
                                <p class="text-3xl font-bold text-wiwa-green">
                                    <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                                </p>
                                <div class="mt-4 space-y-2">
                                    <!-- Deposit/Pending Placeholders -->
             
                                    <div class="flex justify-end gap-3 text-sm text-gray-500">
                                        <span>Depósito:</span>
                                        <span class="font-semibold text-wiwa-green text-deposit-amount">
                                            <?php 
                                            // Heuristic: If we had deposit logic, echo it here. 
                                            // For now echo 30% or custom field if exists?
                                            // Just placeholder or calculate generic 30% if user requested simulation
                                            $total_val = $cart_item['line_subtotal'];
                                            $deposit_val = $total_val * 0.30;
                                            echo wc_price($deposit_val);
                                            ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-end gap-3 text-sm">
                                        <span class="text-gray-500">Saldo pendiente:</span>
                                        <span class="font-bold text-red-600 text-remaining-amount">
                                            <?php 
                                            $remaining_val = $total_val - $deposit_val;
                                            echo wc_price($remaining_val);
                                            ?>
                                        </span>
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
        
        <button type="submit" class="button hidden" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>">
            <?php esc_html_e('Update cart', 'woocommerce'); ?>
        </button>

        <?php do_action('woocommerce_after_cart_contents'); ?>
        <?php do_action('woocommerce_after_cart_table'); ?>
    </section> <!-- End Items Section -->


    <!-- SIDEBAR: TOTALS -->
    <aside class="lg:w-1/3 mt-12 lg:mt-0" data-purpose="order-summary">
        <div class="bg-white rounded-2xl card-shadow p-8 sticky top-32 border border-gray-50">
            <h3 class="text-[13px] font-bold text-wiwa-green uppercase tracking-[0.2em] border-b border-gray-100 pb-6 mb-8">
                <?php esc_html_e('Totales del Carrito', 'wiwa-checkout'); ?>
            </h3>
            
            <div class="space-y-6 mb-10">
                <!-- Subtotal -->
                <div class="flex justify-between items-center text-gray-500 text-[15px]">
                    <span><?php esc_html_e('Subtotal experiencias', 'wiwa-checkout'); ?></span>
                    <span class="font-semibold text-gray-800"><?php wc_cart_totals_subtotal_html(); ?></span>
                </div>

                <!-- Shipping (If applicable) -->
                <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                    <div class="flex justify-between items-center text-gray-500 text-[15px]">
                        <span><?php esc_html_e('Envío', 'woocommerce'); ?></span>
                        <span class="font-semibold text-gray-800"><?php wc_cart_totals_shipping_html(); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Fees -->
                <?php foreach (WC()->cart->get_fees() as $fee) : ?>
                    <div class="flex justify-between items-center text-gray-500 text-[15px]">
                        <span><?php echo esc_html($fee->name); ?></span>
                        <span class="font-semibold text-gray-800"><?php wc_cart_totals_fee_html($fee); ?></span>
                    </div>
                <?php endforeach; ?>

                <!-- Coupons -->
                 <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                    <div class="flex justify-between items-center text-green-600 text-[15px]">
                        <span><?php wc_cart_totals_coupon_label($coupon); ?></span>
                        <span class="font-semibold"><?php wc_cart_totals_coupon_html($coupon); ?></span>
                    </div>
                <?php endforeach; ?>

                <!-- Taxes -->
                <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
                    <div class="flex justify-between items-center text-gray-500 text-[15px]">
                        <span><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
                        <span class="font-semibold text-gray-800"><?php wc_cart_totals_taxes_total_html(); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Total Pay Today (Deposit Logic - Standard Total for now) -->
                <div class="flex justify-between items-center text-wiwa-green text-[15px]">
                    <span class="font-medium"><?php esc_html_e('Total a pagar hoy (Depósito)', 'wiwa-checkout'); ?></span>
                    <span class="font-bold"><?php wc_cart_totals_order_total_html(); ?></span>
                </div>

                <!-- Pending Amount Placeholder -->
                <div class="pt-6 border-t border-dashed border-gray-200">
                    <div class="flex justify-between items-center text-red-600 text-[15px]">
                        <span><?php esc_html_e('Pendiente por pagar', 'wiwa-checkout'); ?></span>
                        <span class="font-bold text-pending-total">
                            <?php 
                            // Placeholder logic or standard WC calculation if available
                            echo apply_filters('wiwa_cart_pending_balance_html', '$ --'); 
                            ?>
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2 leading-relaxed italic">
                        <?php esc_html_e('El saldo restante se pagará directamente en nuestras oficinas el día del tour.', 'wiwa-checkout'); ?>
                    </p>
                </div>
            </div>

            <div class="pt-8 border-t border-gray-100">
                <div class="flex flex-col gap-2 mb-8">
                    <span class="text-gray-500 text-sm font-medium"><?php esc_html_e('Total de la reserva', 'wiwa-checkout'); ?></span>
                    <span class="text-5xl font-bold text-wiwa-green tracking-tight">
                        <?php echo wp_kses_post(WC()->cart->get_total()); ?>
                    </span>
                </div>
                
                <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="block text-center w-full bg-wiwa-green hover:bg-wiwa-green-light text-white font-bold py-5 rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-1 duration-300 text-[14px] uppercase tracking-widest">
                     <?php esc_html_e('Proceder al Pago', 'wiwa-checkout'); ?>
                </a>

                <div class="flex items-center justify-center gap-2 mt-6">
                    <span class="material-symbols-outlined text-[16px] text-gray-400">lock</span>
                    <span class="text-[11px] text-gray-400 uppercase font-medium tracking-tighter"><?php esc_html_e('Pago Seguro SSL', 'wiwa-checkout'); ?></span>
                </div>
            </div>
        </div>

        <?php if (wc_coupons_enabled()) { ?>
        <div class="mt-6 bg-wiwa-cream p-6 rounded-2xl border border-wiwa-border/40">
            <p class="text-[13px] font-semibold text-wiwa-green mb-3"><?php esc_html_e('¿Tienes un código de descuento?', 'wiwa-checkout'); ?></p>
            <div class="flex gap-2">
                <input type="text" name="coupon_code" class="flex-grow bg-white border-gray-200 rounded-lg text-sm px-4 py-2 focus:ring-wiwa-green focus:border-wiwa-green" placeholder="<?php esc_attr_e('Ingresa tu código', 'woocommerce'); ?>" />
                <button type="submit" class="bg-wiwa-green text-white px-4 py-2 rounded-lg text-[12px] font-bold uppercase hover:opacity-90 transition" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>">
                    <?php esc_html_e('Aplicar', 'woocommerce'); ?>
                </button>
            </div>
        </div>
        <?php } ?>

    </aside>

</form> <!-- Final Close of Form Flex Container -->

<?php do_action('woocommerce_after_cart'); ?>
