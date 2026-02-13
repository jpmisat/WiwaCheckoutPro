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
                <?php
                // --- CUSTOM: Extract Tour Meta ---
                // We restart logic to get date, duration, travelers
                $security_deposit = get_post_meta( $product_id, 'ovabrw_security_deposit_amount', true );
                
                // Get check-in (Date)
                $date_check_in = '';
                if ( isset( $cart_item['ovabrw_checkin'] ) && ! empty( $cart_item['ovabrw_checkin'] ) ) {
                    $date_check_in = date( get_option( 'date_format' ), strtotime( $cart_item['ovabrw_checkin'] ) );
                } elseif ( isset( $cart_item['checkin'] ) && ! empty( $cart_item['checkin'] ) ) {
                    $date_check_in = date( get_option( 'date_format' ), strtotime( $cart_item['checkin'] ) );
                }

                // Get Duration
                $duration = '';
                if ( isset( $cart_item['duration_label'] ) && ! empty( $cart_item['duration_label'] ) ) {
                    $duration = $cart_item['duration_label'];
                } elseif ( isset( $cart_item['ovabrw_duration'] ) ) {
                    $duration = $cart_item['ovabrw_duration'];
                }

                // Get Travelers (Adults + Kids + Babies)
                $travelers_label = '';
                $guest_keys = array(
                    'adults' => esc_html__( 'Adultos', 'wiwa-tour-checkout' ),
                    'kids'   => esc_html__( 'Niños', 'wiwa-tour-checkout' ),
                    'babies' => esc_html__( 'Bebés', 'wiwa-tour-checkout' ),
                );
                $guests_list = array();
                
                // Try to find the keys in cart item
                foreach( $guest_keys as $key => $label ) {
                     if ( isset( $cart_item[$key] ) && intval( $cart_item[$key] ) > 0 ) {
                         $guests_list[] = intval( $cart_item[$key] ) . ' ' . $label;
                     } elseif ( isset( $cart_item['ovabrw_'.$key] ) && intval( $cart_item['ovabrw_'.$key] ) > 0 ) {
                         $guests_list[] = intval( $cart_item['ovabrw_'.$key] ) . ' ' . $label;
                     }
                }
                if ( ! empty( $guests_list ) ) {
                    $travelers_label = implode( ', ', $guests_list );
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
                            <!-- ROW 1: Title + Remove -->
                            <div class="wiwa-mini-cart-header">
                                <a href="<?php echo esc_url( $product_permalink ); ?>">
                                    <?php echo $product_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </a>

                                <?php
                                echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><span class="material-symbols-rounded">close</span></a>',
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

                            <!-- ROW 2: Date | Duration -->
                            <div class="wiwa-mini-cart-meta">
                                <?php if ( $date_check_in ) : ?>
                                    <span class="meta-item">
                                        <!-- SVG Calendar -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <?php echo esc_html( $date_check_in ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( $duration ) : ?>
                                    <span class="meta-separator">|</span>
                                    <span class="meta-item">
                                        <!-- SVG Clock -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <?php echo esc_html( $duration ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- ROW 3: Travelers -->
                            <?php if ( $travelers_label ) : ?>
                                <div class="wiwa-mini-cart-meta-row-2">
                                     <span class="meta-item">
                                        <!-- SVG User -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <?php echo esc_html( $travelers_label ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- ROW 4: Footer (Stepper + Price) -->
                            <div class="wiwa-mini-cart-footer">
                                </div>

                                <!-- Price -->
                                <div class="wiwa-mini-cart-price">
                                    <?php echo WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ); ?>
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
