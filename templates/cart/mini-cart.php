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

                            <!-- Meta Data (Date, Pax, etc.) -->
                            <div class="wiwa-mini-cart-meta">
                                <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
                            </div>

                            <!-- Footer: Stepper + Price + Remove -->
                            <div class="wiwa-mini-cart-footer">
                                <div class="wiwa-mini-cart-stepper">
                                    <?php
                                    // This hook handles our custom stepper HTML via 'custom_mini_cart_item_quantity' filter
                                    echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<span class="quantity">' . sprintf( '%s &times; %s', $cart_item['quantity'], $product_price ) . '</span>', $cart_item, $cart_item_key ); 
                                    ?>
                                </div>
                                
                                <div class="wiwa-mini-cart-price">
                                    <?php echo $product_price; ?>
                                </div>

                                <?php
                                echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
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
                <?php esc_html_e( 'View cart', 'woocommerce' ); ?>
            </a>
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout wiwa-btn-primary">
                <?php esc_html_e( 'Checkout', 'woocommerce' ); ?>
            </a>
        </div>

        <?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
    </div>

<?php else : ?>

    <p class="woocommerce-mini-cart__empty-message"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
