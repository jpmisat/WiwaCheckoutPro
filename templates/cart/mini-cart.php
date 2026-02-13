<?php
/**
 * Wiwa Custom Mini-Cart Template
 *
 * @version 2.11.12
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' ); ?>

<?php if ( ! WC()->cart->is_empty() ) : ?>

    <ul class="woocommerce-mini-cart cart_list product_list_widget <?php echo esc_attr( $args['list_class'] ); ?>">
        <?php
        do_action( 'woocommerce_before_mini_cart_contents' );

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                $product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
                $product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

                // Extract Tour Meta
                $is_tour   = $_product->is_type('ovatb_tour');
                $tour_meta = function_exists('wiwa_extract_tour_meta') ? wiwa_extract_tour_meta($cart_item) : [];
                
                // Stepper Value Logic
                $qty_valid_value = ($is_tour && isset($tour_meta['travelers'])) ? $tour_meta['travelers'] : $cart_item['quantity'];

                // Calculate Unit Price
                $line_subtotal_raw = floatval($cart_item['line_subtotal']);
                if (wc_tax_enabled() && WC()->cart->display_prices_including_tax()) {
                    $line_subtotal_raw += floatval($cart_item['line_subtotal_tax']);
                }
                $pax_count  = (isset($tour_meta['travelers']) && $tour_meta['travelers'] > 0) ? $tour_meta['travelers'] : 1;
                $unit_price = $line_subtotal_raw / $pax_count;

                // Primary Guest Key for AJAX
                $primary_guest_key = '';
                if (!empty($tour_meta['guests_detail'])) {
                    $primary_guest_key = $tour_meta['guests_detail'][0]['name'];
                }
                ?>
                <li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
                    
                    <div class="wiwa-mini-cart-item-grid">
                        <!-- 1. Thumbnail -->
                        <div class="wiwa-mini-cart-thumb">
                            <?php if ( ! empty( $product_permalink ) ) : ?>
                                <a href="<?php echo esc_url( $product_permalink ); ?>">
                                    <?php echo $thumbnail; ?>
                                </a>
                            <?php else : ?>
                                <?php echo $thumbnail; ?>
                            <?php endif; ?>
                        </div>

                        <!-- 2. Content -->
                        <div class="wiwa-mini-cart-content">
                            <h4 class="wiwa-mini-cart-title">
                                <?php if ( ! empty( $product_permalink ) ) : ?>
                                    <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_name ); ?></a>
                                <?php else : ?>
                                    <?php echo wp_kses_post( $product_name ); ?>
                                <?php endif; ?>
                            </h4>

                            <!-- Detailed Meta Data -->
                            <div class="wiwa-mini-cart-meta">
                                <?php if (!empty($tour_meta['checkin'])) : ?>
                                <div class="meta-row">
                                    <span class="material-symbols-outlined">calendar_today</span>
                                    <span><?php echo esc_html($tour_meta['checkin']); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($tour_meta['duration_label'])) : ?>
                                <div class="meta-row">
                                    <span class="material-symbols-outlined">schedule</span>
                                    <span><?php echo esc_html($tour_meta['duration_label']); ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="meta-row">
                                    <span class="material-symbols-outlined">group</span>
                                    <span><?php echo esc_html($pax_count); ?> <?php esc_html_e('Viajeros', 'wiwa-checkout'); ?></span>
                                </div>
                            </div>

                            <!-- Footer: Quantity | Price | Remove -->
                            <div class="wiwa-mini-cart-footer">
                                
                                <!-- Group: Stepper + Price -->
                                <div class="wiwa-footer-left">
                                    <!-- Quantity Stepper -->
                                    <div class="wiwa-mini-cart-stepper">
                                        <?php if ($_product->is_sold_individually()) : ?>
                                            <div class="wiwa-stepper-pill disabled">
                                                <span class="wiwa-qty-static">1</span>
                                            </div>
                                        <?php else : ?>
                                            <div class="wiwa-stepper-pill">
                                                <button type="button" class="wiwa-qty-minus" aria-label="<?php esc_attr_e('Decrease quantity', 'wiwa-checkout'); ?>">−</button>
                                                <input type="number" 
                                                    class="wiwa-qty-input" 
                                                    value="<?php echo esc_attr($qty_valid_value); ?>" 
                                                    min="1" 
                                                    step="1"
                                                    data-cart-key="<?php echo esc_attr($cart_item_key); ?>"
                                                    data-is-tour="<?php echo $is_tour ? '1' : '0'; ?>"
                                                    data-guest-key="<?php echo esc_attr($primary_guest_key); ?>"
                                                    readonly />
                                                <button type="button" class="wiwa-qty-plus" aria-label="<?php esc_attr_e('Increase quantity', 'wiwa-checkout'); ?>">+</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Price -->
                                    <div class="wiwa-mini-cart-price">
                                        <?php echo WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ); ?>
                                    </div>
                                </div>

                                <!-- Remove Actions -->
                                <div class="wiwa-footer-right">
                                    <?php
                                    echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        'woocommerce_cart_item_remove_link',
                                        sprintf(
                                            '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><span class="material-symbols-outlined">close</span></a>',
                                            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                            esc_attr__( 'Remove this item', 'woocommerce' ),
                                            esc_attr( $product_id ),
                                            esc_attr( $cart_item_key ),
                                            esc_attr( $_product->get_sku() )
                                        ),
                                        $cart_item_key
                                    );
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php
            }
        }

        do_action( 'woocommerce_mini_cart_contents' );
        ?>
    </ul>

    <div class="wiwa-mini-cart-bottom">
        <div class="wiwa-mini-cart-subtotal">
            <span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
            <span class="wiwa-subtotal-amount"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
        </div>

        <?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

        <div class="wiwa-mini-cart-buttons">
            <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="button wiwa-btn-outline">
                <?php esc_html_e( 'Ver carrito', 'woocommerce' ); ?>
            </a>
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout wiwa-btn-primary">
                <?php esc_html_e( 'Finalizar compra', 'woocommerce' ); ?>
            </a>
        </div>

        <?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
    </div>

<?php else : ?>

    <p class="woocommerce-mini-cart__empty-message"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
