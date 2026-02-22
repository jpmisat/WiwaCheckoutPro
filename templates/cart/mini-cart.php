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

                // --- CUSTOM: Extract Tour Meta (Redundant check for safer variables) ---
                $date_check_in = '';
                if ( ! empty( $tour_meta['checkin'] ) ) {
                    $date_check_in = $tour_meta['checkin'];
                }

                $duration = '';
                if ( ! empty( $tour_meta['duration_label'] ) ) {
                    $duration = $tour_meta['duration_label'];
                }

                // Travelers Label
                $travelers_label = '';
                if ( ! empty( $tour_meta['adults'] ) ) $travelers_label .= $tour_meta['adults'] . ' ' . __( 'Adultos', 'wiwa-tour-checkout' );
                if ( ! empty( $tour_meta['kids'] ) )   $travelers_label .= ', ' . $tour_meta['kids'] . ' ' . __( 'Niños', 'wiwa-tour-checkout' );
                if ( ! empty( $tour_meta['babies'] ) ) $travelers_label .= ', ' . $tour_meta['babies'] . ' ' . __( 'Bebés', 'wiwa-tour-checkout' );

                // Stepper Logic
                // For tours, "quantity" in WC is 1, but we want to show travelers or just the pax count?
                // Actually, for OvaTourBooking, WC quantity IS the number of people usually, OR it's 1 and price is total.
                // wiwa-mini-cart.js documentation says: "WC quantity is always 1. Actual traveler count is stored in metadata."
                // So the input value should be the traveler count.
                // We use $tour_meta['travelers'] (sum of pax) as the visible quantity.
                $qty_display_value = ($is_tour && isset($tour_meta['travelers'])) ? $tour_meta['travelers'] : $cart_item['quantity'];

                // Primary Guest Key for AJAX (needed for wiwa-mini-cart.js)
                $primary_guest_key = '';
                if (!empty($tour_meta['guests_detail'])) {
                     // Just use 'adults' or the first key available if we want to increment main pax
                     // wiwa-mini-cart.js uses 'guest_key' data attribute.
                     $primary_guest_key = 'adults'; 
                }
                ?>
                <li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>">
                    
                    <div class="wiwa-mini-cart-item-grid">
                        <!-- 1. Thumbnail -->
                        <div class="wiwa-mini-cart-thumb">
                            <?php if ( ! empty( $product_permalink ) ) : ?>
                                <a href="<?php echo esc_url( $product_permalink ); ?>">
                                    <?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </a>
                            <?php else : ?>
                                <?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endif; ?>
                        </div>

                        <!-- 2. Content -->
                        <div class="wiwa-mini-cart-content">
                            <!-- ROW 1: Title + Remove -->
                            <div class="wiwa-mini-cart-header">
                                <a href="<?php echo esc_url( $product_permalink ); ?>" class="wiwa-item-title">
                                    <?php echo $product_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </a>

                                <?php
                                echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    'woocommerce_cart_item_remove_link',
                                    sprintf(
                                        '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">
                                            <!-- Trash Icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </a>',
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
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                        <?php echo esc_html( $date_check_in ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( $duration ) : ?>
                                    <span class="meta-separator">|</span>
                                    <span class="meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <?php echo esc_html( $duration ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- ROW 3: Travelers Count + Price Per Person -->
                            <?php if ( $is_tour && $qty_display_value > 0 ) : ?>
                                <div class="wiwa-mini-cart-meta-row-2">
                                     <span class="meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                        <?php echo esc_html( $qty_display_value ); ?> <?php echo ($qty_display_value > 1) ? 'Viajeros' : 'Viajero'; ?>
                                    </span>

                                    <span class="meta-separator">|</span>

                                    <?php
                                        // Calculate Unit/Person Price
                                        // Use line subtotal (which includes everything for this line) divided by quantity
                                        $line_total = $cart_item['line_subtotal'];
                                        if ( wc_prices_include_tax() ) {
                                            $line_total += $cart_item['line_subtotal_tax'];
                                        }
                                        $unit_price = $line_total / max(1, $qty_display_value);
                                        $price_html = wc_price( $unit_price );
                                    ?>
                                    <span class="meta-item wiwa-price-per-person-text">
                                        <?php echo sprintf( '%s', $price_html ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- ROW 4: Footer (Stepper ONLY) -->
                            <div class="wiwa-mini-cart-footer">
                                <!-- Stepper -->
                                <div class="wiwa-qty-stepper">
                                    <div class="wiwa-stepper-pill">
                                        <button type="button" class="wiwa-qty-minus">−</button>
                                        <input type="number" 
                                            class="wiwa-qty-input" 
                                            value="<?php echo esc_attr( $qty_display_value ); ?>" 
                                            min="1" 
                                            step="1"
                                            data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>"
                                            data-is-tour="<?php echo $is_tour ? '1' : '0'; ?>"
                                            data-guest-key="<?php echo esc_attr( $primary_guest_key ); ?>"
                                            readonly 
                                        />
                                        <button type="button" class="wiwa-qty-plus">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php
            }
        }
        
        // Removed 'woocommerce_mini_cart_contents' to prevent duplicate buttons from other plugins/themes
        ?>
    </ul>

    <p class="woocommerce-mini-cart__total total wiwa-mini-cart-subtotal">
        <span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
        <span class="wiwa-subtotal-amount">
            <?php echo WC()->cart->get_cart_subtotal(); ?>
            <span class="wiwa-currency-code-large"><?php echo get_woocommerce_currency(); ?></span>
        </span>
    </p>

    <!-- Removed generic before_buttons action to prevent duplication -->

    <p class="woocommerce-mini-cart__buttons buttons wiwa-mini-cart-buttons">
        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="button wiwa-btn-outline">
            <?php esc_html_e( 'Ver carrito', 'woocommerce' ); ?>
        </a>
        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout wiwa-btn-primary">
            <?php esc_html_e( 'Finalizar compra', 'woocommerce' ); ?>
        </a>
    </p>

<?php else : ?>

    <p class="woocommerce-mini-cart__empty-message"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
