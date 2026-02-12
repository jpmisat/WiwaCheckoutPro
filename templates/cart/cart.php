<?php
/**
 * Wiwa Tour Checkout - Premium Cart Template (Div-Based)
 * Version: 2.10.3
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
                        
                        <!-- REMOVE BUTTON (Top Right) -->
                        <div class="wiwa-card-remove">
                            <?php
                                echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                                        esc_url(wc_get_cart_remove_url($cart_item_key)),
                                        esc_html__('Remove this item', 'woocommerce'),
                                        esc_attr($product_id),
                                        esc_attr($_product->get_sku())
                                    ),
                                    $cart_item_key
                                );
                            ?>
                        </div>

                        <!-- 1. IMAGE -->
                        <div class="wiwa-card-image">
                            <?php
                            $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                            if (!$product_permalink) {
                                echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            } else {
                                printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                            ?>
                        </div>

                        <!-- 2. DETAILS (Title + Meta) -->
                        <div class="wiwa-card-details">
                            <h3 class="wiwa-card-title" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
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
                                // Meta data.
                                echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                                // Backorder notification.
                                if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                }
                                ?>
                            </div>
                            
                            <!-- PRICE (Unit) - Optional visual guide -->
                            <div class="wiwa-card-unit-price">
                                <?php
                                    echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                            </div>
                        </div>

                        <!-- 3. ACTIONS (Pax + Subtotal) -->
                        <div class="wiwa-card-actions">
                            
                            <!-- QUANTITY/PAX CONTROL -->
                            <div class="wiwa-card-qty" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                                <?php if ($is_tour): ?>
                                    <div class="wiwa-pax-control">
                                        <label class="wiwa-pax-label"><?php esc_html_e('Viajeros', 'wiwa-checkout'); ?></label>
                                        <div class="wiwa-qty-pill">
                                            <button type="button" class="wiwa-qty-btn wiwa-qty-minus">&minus;</button>
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
                                        <span class="wiwa-pax-summary">
                                            <?php printf(esc_html(_n('%d viajero', '%d viajeros', max(1, $pax_total), 'wiwa-checkout')), max(1, $pax_total)); ?>
                                        </span>
                                        <!-- Hidden WC Quantity for Form Submission -->
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

                            <!-- SUBTOTAL DISPLAY -->
                            <div class="wiwa-card-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
                                <?php
                                    echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                ?>
                            </div>

                        </div> <!-- End .wiwa-card-actions -->

                    </div> <!-- End .wiwa-cart-card -->
                    <?php
                }
            }
            ?>
            
            <?php do_action('woocommerce_cart_contents'); ?>
            
            <!-- UPDATE CART BUTTON (Hidden but functional for JS trigger) -->
            <button type="submit" class="button wiwa-bg-hidden" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>">
                <?php esc_html_e('Update cart', 'woocommerce'); ?>
            </button>

            <?php do_action('woocommerce_after_cart_contents'); ?>
        </div>
        
        <?php do_action('woocommerce_after_cart_table'); ?>
    </form>


    <!-- COLLATERALS / TOTALS -->
    <div class="wiwa-cart-collaterals">
        <?php
            /**
             * Cart collaterals hook.
             *
             * @hooked woocommerce_cross_sell_display
             * @hooked woocommerce_cart_totals - 10
             */
            do_action('woocommerce_cart_collaterals');
        ?>
    </div>

</div>

<?php do_action('woocommerce_after_cart'); ?>
