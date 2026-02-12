<?php
/**
 * Custom Cart Template for Wiwa Tours.
 *
 * @package WiwaTourCheckout
 * @version 2.10.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
?>

<div class="wiwa-cart-wrapper">
    <header class="wiwa-cart-header">
        <h1 class="wiwa-cart-title"><?php esc_html_e('Tu Carrito de Aventuras', 'wiwa-checkout'); ?></h1>
        <p class="wiwa-cart-subtitle"><?php esc_html_e('Turismo con el alma desde el corazon de la tierra.', 'wiwa-checkout'); ?></p>
    </header>

    <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
        <?php do_action('woocommerce_before_cart_table'); ?>

        <div class="wiwa-cart-table-container">
            <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents wiwa-cart-table" cellspacing="0">
                <thead>
                    <tr>
                        <th class="product-remove"><span class="screen-reader-text"><?php esc_html_e('Remove item', 'woocommerce'); ?></span></th>
                        <th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e('Thumbnail image', 'woocommerce'); ?></span></th>
                        <th class="product-name"><?php esc_html_e('Tour', 'wiwa-checkout'); ?></th>
                        <th class="product-price"><?php esc_html_e('Precio', 'woocommerce'); ?></th>
                        <th class="product-quantity"><?php esc_html_e('Pasajeros', 'wiwa-checkout'); ?></th>
                        <th class="product-subtotal"><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php do_action('woocommerce_before_cart_contents'); ?>

                    <?php
                    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                        $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                        if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                            continue;
                        }

                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                        $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_thumbnail'), $cart_item, $cart_item_key);

                        $guest_keys = [];
                        foreach ($cart_item as $meta_key => $meta_value) {
                            if (strpos($meta_key, 'numberof_') === 0 && $meta_key !== 'numberof_guests' && is_numeric($meta_value)) {
                                $guest_keys[] = $meta_key;
                            }
                        }

                        $primary_guest_key = '';
                        if (!empty($guest_keys)) {
                            if (in_array('numberof_pax', $guest_keys, true)) {
                                $primary_guest_key = 'numberof_pax';
                            } else {
                                foreach ($guest_keys as $guest_key) {
                                    if (intval($cart_item[$guest_key]) > 0) {
                                        $primary_guest_key = $guest_key;
                                        break;
                                    }
                                }
                                if (!$primary_guest_key) {
                                    $primary_guest_key = $guest_keys[0];
                                }
                            }
                        }

                        $is_tour_editable = !empty($primary_guest_key);
                        $pax_total = class_exists('Wiwa_Tour_Booking_Integration') ? Wiwa_Tour_Booking_Integration::get_pax_count($cart_item) : intval($cart_item['quantity']);
                        $guest_breakdown = class_exists('Wiwa_Tour_Booking_Integration') ? Wiwa_Tour_Booking_Integration::get_guest_breakdown($cart_item, $_product) : [];
                        ?>

                        <tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?> wiwa-cart-card" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                            <td class="product-remove">
                                <?php
                                $remove_link = sprintf(
                                    '<a href="%s" class="remove wiwa-remove-item" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span class="screen-reader-text">%s</span><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 3h6l1 2h4v2H4V5h4l1-2zm1 7h2v8h-2v-8zm4 0h2v8h-2v-8zM7 10h2v8H7v-8z"/></svg></a>',
                                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                                    esc_attr__('Remove this item', 'woocommerce'),
                                    esc_attr($product_id),
                                    esc_attr($_product->get_sku()),
                                    esc_html__('Remove this item', 'woocommerce')
                                );

                                echo apply_filters('woocommerce_cart_item_remove_link', $remove_link, $cart_item_key);
                                ?>
                            </td>

                            <td class="product-thumbnail">
                                <?php
                                if (!$product_permalink) {
                                    echo $thumbnail;
                                } else {
                                    printf('<a href="%s" class="wiwa-cart-thumb-link">%s</a>', esc_url($product_permalink), $thumbnail);
                                }
                                ?>
                            </td>

                            <td class="product-name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                                <?php
                                if (!$product_permalink) {
                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key));
                                } else {
                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s" class="wiwa-cart-product-title">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
                                }
                                ?>

                                <div class="wiwa-cart-meta">
                                    <?php
                                    echo wc_get_formatted_cart_item_data($cart_item);
                                    do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

                                    if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                        echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                    }
                                    ?>
                                </div>
                            </td>

                            <td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                                <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key)); ?>
                            </td>

                            <td class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                                <?php if ($is_tour_editable): ?>
                                    <div class="wiwa-pax-panel">
                                        <span class="wiwa-pax-label"><?php esc_html_e('Cantidad de viajeros', 'wiwa-checkout'); ?></span>

                                        <div class="wiwa-mini-cart-qty wiwa-main-cart-qty">
                                            <button type="button" class="wiwa-qty-btn wiwa-qty-minus" aria-label="<?php esc_attr_e('Reducir pasajeros', 'wiwa-checkout'); ?>">-</button>
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
                                            <button type="button" class="wiwa-qty-btn wiwa-qty-plus" aria-label="<?php esc_attr_e('Aumentar pasajeros', 'wiwa-checkout'); ?>">+</button>
                                        </div>

                                        <span class="wiwa-pax-total-value">
                                            <?php
                                            printf(
                                                esc_html(_n('%d viajero', '%d viajeros', max(1, $pax_total), 'wiwa-checkout')),
                                                max(1, $pax_total)
                                            );
                                            ?>
                                        </span>

                                        <?php if (!empty($guest_breakdown) && count($guest_breakdown) > 1): ?>
                                            <span class="wiwa-pax-breakdown">
                                                <?php
                                                $breakdown_parts = [];
                                                foreach ($guest_breakdown as $type_label => $type_count) {
                                                    $breakdown_parts[] = sprintf('%d %s', $type_count, $type_label);
                                                }
                                                echo esc_html(implode(' - ', $breakdown_parts));
                                                ?>
                                            </span>
                                        <?php endif; ?>

                                        <input type="hidden" name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" value="<?php echo esc_attr($cart_item['quantity']); ?>" />
                                        <?php foreach ($guest_keys as $guest_key): ?>
                                            <?php
                                            $guest_slug = str_replace('numberof_', '', $guest_key);
                                            $guest_count = isset($cart_item[$guest_key]) ? intval($cart_item[$guest_key]) : 0;
                                            ?>
                                            <input
                                                type="hidden"
                                                class="wiwa-hidden-guest-input"
                                                data-guest-slug="<?php echo esc_attr($guest_slug); ?>"
                                                name="cart[<?php echo esc_attr($cart_item_key); ?>][guests][<?php echo esc_attr($guest_slug); ?>]"
                                                value="<?php echo esc_attr($guest_count); ?>"
                                            />
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
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
                                            'input_name' => "cart[{$cart_item_key}][qty]",
                                            'input_value' => $cart_item['quantity'],
                                            'max_value' => $max_quantity,
                                            'min_value' => $min_quantity,
                                            'product_name' => $_product->get_name(),
                                        ],
                                        $_product,
                                        false
                                    );

                                    echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item);
                                    ?>
                                <?php endif; ?>
                            </td>

                            <td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
                                <span class="wiwa-item-subtotal-value">
                                    <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php } ?>

                    <?php do_action('woocommerce_cart_contents'); ?>

                    <tr>
                        <td colspan="6" class="actions">
                            <button type="submit" class="button wiwa-update-cart-btn" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Actualizar Carrito', 'wiwa-checkout'); ?></button>
                            <?php do_action('woocommerce_cart_actions'); ?>
                            <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                        </td>
                    </tr>

                    <?php do_action('woocommerce_after_cart_contents'); ?>
                </tbody>
            </table>
        </div>

        <?php do_action('woocommerce_after_cart_table'); ?>
    </form>

    <?php do_action('woocommerce_before_cart_collaterals'); ?>

    <div class="cart-collaterals wiwa-cart-collaterals">
        <?php do_action('woocommerce_cart_collaterals'); ?>
    </div>
</div>

<?php do_action('woocommerce_after_cart'); ?>
