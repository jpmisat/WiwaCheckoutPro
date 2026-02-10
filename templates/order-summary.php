<?php
/**
 * Order Summary Sidebar
 */
defined('ABSPATH') || exit;
$cart = WC()->cart;
?>
<div class="order-summary-sticky">
    <div class="order-summary-card">
        <h3 class="summary-title">Resumen</h3>
        <div class="summary-products">
            <?php foreach ($cart->get_cart() as $cart_item_key => $cart_item):
    $product = $cart_item['data'];
    $product_id = $cart_item['product_id'];
    $thumbnail = get_the_post_thumbnail($product_id, 'medium');
    $booking_data = isset($cart_item['ovabrw_booking_data']) ? $cart_item['ovabrw_booking_data'] : [];
    $adults = $booking_data['ovabrw_adults'] ?? 1;
    $children = $booking_data['ovabrw_children'] ?? 0;
?>
            <div class="summary-product-item">
                <?php if ($thumbnail): ?><div class="summary-product-image"><?php echo $thumbnail; ?></div><?php
    endif; ?>
                <div class="summary-product-details">
                    <h4 class="summary-product-name"><?php echo $product->get_name(); ?></h4>
                    <div class="summary-meta">
                        <?php if (!empty($booking_data['ovabrw_pickup_date'])): ?>
                        <div class="summary-meta-item">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                            <span><?php echo date_i18n('j M Y', strtotime($booking_data['ovabrw_pickup_date'])); ?></span>
                        </div>
                        <?php
    endif; ?>
                        <div class="summary-meta-item">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                            <span><?php echo $adults + $children; ?> Viajero<?php echo($adults + $children) > 1 ? 's' : ''; ?></span>
                        </div>
                    </div>
                    <div class="summary-product-price"><?php echo wc_price($cart_item['line_total']); ?></div>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
        <hr class="summary-divider">
        <div class="summary-prices">
            <div class="price-row"><span class="price-label">Subtotal (<?php echo $cart->get_cart_contents_count(); ?> tour<?php echo $cart->get_cart_contents_count() > 1 ? 's' : ''; ?>)</span><span class="price-value"><?php echo wc_price($cart->get_subtotal()); ?></span></div>
            <?php if ($cart->get_discount_total() > 0): ?>
            <div class="price-row discount-row"><span class="price-label">Descuento</span><span class="price-value">-<?php echo wc_price($cart->get_discount_total()); ?></span></div>
            <?php
endif; ?>
            <?php if ($cart->get_total_tax() > 0): ?>
            <div class="price-row"><span class="price-label">Impuestos</span><span class="price-value"><?php echo wc_price($cart->get_total_tax()); ?></span></div>
            <?php
endif; ?>
        </div>
        <hr class="summary-divider">
        <div class="summary-total"><span class="total-label">Total</span><span class="total-value"><?php echo wc_price($cart->get_total('edit')); ?></span></div>
        <div class="summary-coupon">
            <input type="text" id="coupon_code" placeholder="Código de descuento" class="coupon-input">
            <button type="button" class="btn-apply-coupon" id="apply_coupon">Aplicar</button>
        </div>
        <div id="coupon-message" class="coupon-message" style="display: none;"></div>
    </div>
</div>
