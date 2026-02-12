<?php
/**
 * Wiwa Tour Checkout - Premium Cart Template (Stitch Sync)
 * Version: 2.10.5
 * 
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); 
?>

<div class="wiwa-cart-wrapper">

    <!-- CART HEADER -->
    <header class="wiwa-cart-header">
        <h1 class="wiwa-cart-title"><?php esc_html_e('Tu Carrito de Aventuras', 'wiwa-checkout'); ?></h1>
        <p class="wiwa-cart-subtitle"><?php esc_html_e('Revisa tus experiencias antes de continuar.', 'wiwa-checkout'); ?></p>
    </header>

    <!-- FORM START -->
    <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
        <?php do_action('woocommerce_before_cart_table'); ?>

        <div class="wiwa-cart-grid-container">
            <?php do_action('woocommerce_before_cart_contents'); ?>

            <?php
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                    
                    // --- DATA PREP ---
                    $is_tour = false;
                    $pax_total = 1;
                    $primary_guest_key = '';

                    // Detect pax logic (simplified inline)
                    foreach ($cart_item as $key => $val) {
                        if (strpos($key, 'numberof_') === 0 && is_numeric($val) && $val > 0) {
                            $is_tour = true;
                            $pax_total = intval($val);
                            $primary_guest_key = $key;
                            break; 
                        }
                    }
                    ?>
                    
                    <!-- CART CARD ITEM -->
                    <div class="wiwa-cart-card <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                        
                            <!-- 1. IMAGE (Left Column) -->
                            <div class="wiwa-card-image">
                                <?php
                                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                                // Optional Badge logic could go here if data available
                                if (!$product_permalink) {
                                    echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                } else {
                                    printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                }
                                ?>
                            </div>

                            <!-- 2. DETAILS (Middle Column: Title, Meta, Remove) -->
                            <div class="wiwa-card-details">
                                <h3 class="wiwa-card-title">
                                    <?php
                                    if (!$product_permalink) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
                                    } else {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
                                    }
                                    do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
                                    ?>
                                </h3>

                                <div class="wiwa-card-meta">
                                    <?php
                                    echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                                    if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                    }
                                    ?>
                                </div>
                                
                                <!-- REMOVE LINK (Bottom Left of Details) -->
                                <div class="wiwa-card-remove-link">
                                    <?php
                                        echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            'woocommerce_cart_item_remove_link',
                                            sprintf(
                                                '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s" title="Eliminar">&times;</a>',
                                                esc_url(wc_get_cart_remove_url($cart_item_key)),
                                                esc_html__('Remove this item', 'woocommerce'),
                                                esc_attr($product_id),
                                                esc_attr($_product->get_sku())
                                            ),
                                            $cart_item_key
                                        );
                                    ?>
                                </div>
                            </div>

                            <!-- 3. ACTIONS (Right Column: Pax Top, Price Bottom) -->
                            <div class="wiwa-card-actions">
                                
                                <!-- PAX CONTROL (Top Right) -->
                                <div class="wiwa-card-qty">
                                    <?php if ($is_tour): ?>
                                        <div class="wiwa-pax-control">
                                            <div class="wiwa-qty-pill">
                                                <button type="button" class="wiwa-qty-btn wiwa-qty-minus">-</button>
                                                <input 
                                                    type="number" 
                                                    class="wiwa-qty-input" 
                                                    value="<?php echo esc_attr(max(1, $pax_total)); ?>"
                                                    min="1"
                                                    step="1"
                                                    data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                                    data-is-tour="1"
                                                    data-guest-key="<?php echo esc_attr($primary_guest_key); ?>"
                                                    readonly
                                                />
                                                <button type="button" class="wiwa-qty-btn wiwa-qty-plus">+</button>
                                            </div>
                                            <label class="wiwa-pax-label"><?php esc_html_e('VIAJEROS', 'wiwa-checkout'); ?></label>
                                            
                                            <input type="hidden" name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" value="<?php echo esc_attr($cart_item['quantity']); ?>" />
                                        </div>
                                    <?php else: ?>
                                        <!-- Standard Product Qty -->
                                        <?php
                                        if ($_product->is_sold_individually()) {
                                            $min_quantity = 1;
                                            $max_quantity = 1;
                                        } else {
                                            $min_quantity = 0;
                                            $max_quantity = $_product->get_max_purchase_quantity();
                                        }

                                        $product_quantity = woocommerce_quantity_input(
                                            [
                                                'input_name'   => "cart[{$cart_item_key}][qty]",
                                                'input_value'  => $cart_item['quantity'],
                                                'max_value'    => $max_quantity,
                                                'min_value'    => $min_quantity,
                                                'product_name' => $_product->get_name(),
                                            ],
                                            $_product,
                                            false
                                        );

                                        echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
                                        ?>
                                    <?php endif; ?>
                                </div>

                                <!-- PRICE BLOCK (Bottom Right) -->
                                <div class="wiwa-card-price-block">
                                    <!-- Unit Price (Optional) -->
                                    <div class="wiwa-unit-price">
                                        <?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); ?>
                                        <span class="wiwa-per-person"> / persona</span>
                                    </div>
                                    
                                    <!-- Total Price (Big Green) -->
                                    <div class="wiwa-card-subtotal">
                                        <?php
                                            echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);
                                        ?>
                                    </div>
                                    
                                    <!-- Deposit/Due (Placeholder/Hook if available) -->
                                    <div class="wiwa-deposit-info">
                                        <!-- This would be populated by the booking plugin or custom fields -->
                                        <!-- <div class="row"><span>Depósito:</span> <strong>$ 1.167.000</strong></div> -->
                                        <!-- <div class="row red"><span>Saldo pendiente:</span> <strong>$ 2.553.000</strong></div> -->
                                    </div>
                                </div>

                            </div> <!-- End .wiwa-card-actions -->
                            
                        </div> <!-- End .wiwa-cart-card -->
                        <?php
                    }
                }
                ?>
            
                <?php do_action('woocommerce_cart_contents'); ?>
                
                <button type="submit" class="button wiwa-bg-hidden" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>">
                    <?php esc_html_e('Update cart', 'woocommerce'); ?>
                </button>
    
                <?php do_action('woocommerce_after_cart_contents'); ?>
        </div>
        
        <?php do_action('woocommerce_after_cart_table'); ?>
    </form>


<!-- COLLATERALS / TOTALS -->
    <div class="wiwa-cart-collaterals">
        <aside class="wiwa-order-summary">
            <div class="wiwa-summary-card">
                <h3 class="wiwa-summary-title"><?php esc_html_e('Totales del Carrito', 'wiwa-checkout'); ?></h3>
                
                <div class="wiwa-summary-rows">
                    <!-- Subtotal -->
                    <div class="wiwa-summary-row subtotal">
                        <span><?php esc_html_e('Subtotal experiencias', 'wiwa-checkout'); ?></span>
                        <span class="amount"><?php wc_cart_totals_subtotal_html(); ?></span>
                    </div>

                    <!-- Discount (Optional) -->
                    <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                        <div class="wiwa-summary-row discount cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
                            <span><?php wc_cart_totals_coupon_label($coupon); ?></span>
                            <span class="amount"><?php wc_cart_totals_coupon_html($coupon); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <!-- Shipping (If applicable, usually hidden for tours but good to have) -->
                    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                        <div class="wiwa-summary-row shipping">
                            <span><?php esc_html_e('Envío', 'woocommerce'); ?></span>
                            <span class="amount"><?php wc_cart_totals_shipping_html(); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Fees -->
                    <?php foreach (WC()->cart->get_fees() as $fee) : ?>
                        <div class="wiwa-summary-row fee">
                            <span><?php echo esc_html($fee->name); ?></span>
                            <span class="amount"><?php wc_cart_totals_fee_html($fee); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <!-- Tax -->
                    <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
                        <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                            <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : ?>
                                <div class="wiwa-summary-row tax-rate">
                                    <span><?php echo esc_html($tax->label); ?></span>
                                    <span class="amount"><?php echo wp_kses_post($tax->formatted_amount); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="wiwa-summary-row tax-total">
                                <span><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
                                <span class="amount"><?php wc_cart_totals_taxes_total_html(); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Total / Deposit -->
                    <div class="wiwa-summary-row deposit">
                        <span class="label"><?php esc_html_e('Total a pagar hoy', 'wiwa-checkout'); ?></span>
                        <span class="amount"><?php wc_cart_totals_order_total_html(); ?></span>
                    </div>
                    
                    <!-- Placeholder for Pending Amount (Logic pending) -->
                    <!-- 
                    <div class="wiwa-summary-pending-group">
                        <div class="wiwa-summary-row pending">
                            <span>Pendiente por pagar</span>
                            <span class="amount">$ 0</span>
                        </div>
                        <p class="wiwa-pending-note">El saldo restante se pagará directamente en nuestras oficinas el día del tour.</p>
                    </div>
                    -->
                </div>

                <!-- Big Reservation Total (For now same as Total, update if partial payment logic exists) -->
                <div class="wiwa-summary-total-block">
                    <span class="wiwa-summary-total-label"><?php esc_html_e('Total de la reserva', 'wiwa-checkout'); ?></span>
                    <span class="wiwa-summary-total-amount"><?php echo wp_kses_post(WC()->cart->get_total()); ?></span>
                </div>

                <!-- Proceed Button -->
                <div class="wiwa-checkout-btn-container">
                    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="wiwa-checkout-btn">
                        <?php esc_html_e('Proceder al Pago', 'wiwa-checkout'); ?>
                    </a>
                </div>
                
                <!-- SSL -->
                <div class="wiwa-secure-badge">
                    <svg style="width:16px;height:16px;fill:#9ca3af;" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                    <span class="wiwa-secure-text"><?php esc_html_e('Pago Seguro SSL', 'wiwa-checkout'); ?></span>
                </div>
            </div>

            <!-- Coupon -->
            <div class="wiwa-coupon-card">
                 <?php if (wc_coupons_enabled()) { ?>
                    <div class="wiwa-coupon-label"><?php esc_html_e('¿Tienes un código de descuento?', 'wiwa-checkout'); ?></div>
                    <form class="wiwa-coupon-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                        <input type="text" name="coupon_code" class="wiwa-coupon-input" placeholder="<?php esc_attr_e('Ingresa tu código', 'woocommerce'); ?>" />
                        <button type="submit" class="wiwa-coupon-btn" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Aplicar', 'woocommerce'); ?></button>
                    </form>
                 <?php } ?>
            </div>
        </aside>
    </div>

</div>

<?php do_action('woocommerce_after_cart'); ?>
