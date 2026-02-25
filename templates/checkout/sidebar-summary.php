<?php
/**
 * Order Summary Sidebar
 * Shows tour details with icons, dynamic currency, and price breakdown
 * Uses Tour Booking cart keys directly (no prefix)
 * Coupon field only shows if WooCommerce coupons are enabled
 * Currency selector integrated via WOOCS
 */
defined('ABSPATH') || exit;

$cart = WC()->cart;
$currency = get_woocommerce_currency();

// Check if WooCommerce coupons are enabled
$coupons_enabled = wc_coupons_enabled();

// Get the configured checkout page URL
$checkout_page_id = get_option('wiwa_checkout_page_id');
$checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : wc_get_checkout_url();

// Currency Settings (WOOCS integration)
$show_currency = get_option('wiwa_show_currency_selector', false);
$currency_style = get_option('wiwa_currency_selector_style', 'dropdown');

// Check for WOOCS plugin
$woocs_active = class_exists('WOOCS');
$woocs_currencies = [];
$woocs_current = $currency;

if ($woocs_active && isset($GLOBALS['WOOCS'])) {
    $woocs = $GLOBALS['WOOCS'];
    $woocs_currencies = $woocs->get_currencies();
    $woocs_current = $woocs->current_currency;
}

// Currency flags mapping
$currency_flags = [
    'USD' => '🇺🇸',
    'EUR' => '🇪🇺',
    'GBP' => '🇬🇧',
    'COP' => '🇨🇴',
    'MXN' => '🇲🇽',
    'BRL' => '🇧🇷',
    'ARS' => '🇦🇷',
    'CLP' => '🇨🇱',
    'PEN' => '🇵🇪',
    'CAD' => '🇨🇦',
    'AUD' => '🇦🇺',
    'JPY' => '🇯🇵',
    'CNY' => '🇨🇳',
];
?>
<div class="order-summary-sticky">
    <div class="order-summary-card">
        <?php if ($show_currency && $woocs_active && count($woocs_currencies) > 1): ?>
        <!-- Currency Section -->
        <div class="currency-section">
            <span class="currency-section-title"><?php _e('Currency', 'wiwa-checkout'); ?></span>
            <div class="wiwa-currency-switcher style-<?php echo esc_attr($currency_style); ?>">
                <?php if ($currency_style === 'buttons'): ?>
                    <div class="currency-buttons">
                        <?php foreach ($woocs_currencies as $code => $data):
            $flag = isset($currency_flags[$code]) ? $currency_flags[$code] : '🏧';
?>
                            <button type="button" 
                                    class="currency-btn <?php echo $code === $woocs_current ? 'active' : ''; ?>"
                                    data-currency="<?php echo esc_attr($code); ?>">
                                <span class="currency-flag"><?php echo $flag; ?></span>
                                <span class="currency-code"><?php echo esc_html($code); ?></span>
                            </button>
                        <?php
        endforeach; ?>
                    </div>
                <?php
    else: ?>
                    <select class="currency-select" id="wiwa-currency-select">
                        <?php foreach ($woocs_currencies as $code => $data):
            $flag = isset($currency_flags[$code]) ? $currency_flags[$code] : '🏧';
?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $woocs_current); ?>>
                                <?php echo esc_html($flag . ' ' . $code); ?>
                            </option>
                        <?php
        endforeach; ?>
                    </select>
                <?php
    endif; ?>
            </div>
        </div>
        <?php
endif; ?>
        
        <h3 class="summary-title"><?php _e('Summary', 'wiwa-checkout'); ?></h3>
        
        <div class="summary-products">
            <?php foreach ($cart->get_cart() as $cart_item_key => $cart_item):
    $product = $cart_item['data'];
    $product_id = $cart_item['product_id'];
    $thumbnail = get_the_post_thumbnail($product_id, 'thumbnail');

    // Get tour data directly from cart item (Tour Booking stores it here)
    $total_pax = Wiwa_Tour_Booking_Integration::get_pax_count($cart_item);
    $tour_date = Wiwa_Tour_Booking_Integration::get_tour_date($cart_item);
    $tour_time = Wiwa_Tour_Booking_Integration::get_tour_time($cart_item);
    $guest_breakdown = Wiwa_Tour_Booking_Integration::get_guest_breakdown($cart_item, $product);
?>
            <div class="summary-product-item">
                <?php if ($thumbnail): ?>
                <div class="summary-product-image"><?php echo $thumbnail; ?></div>
                <?php
    endif; ?>
                <div class="summary-product-details">
                    <h4 class="summary-product-name">
                        <?php echo esc_html($product->get_name()); ?>
                    </h4>
                    <div class="summary-meta">
                        <?php if (!empty($tour_date)): ?>
                        <div class="summary-meta-item">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                            <span><?php echo esc_html($tour_date); ?></span>
                        </div>
                        <?php
    endif; ?>
                        
                        <?php if (!empty($tour_time)): ?>
                        <div class="summary-meta-item">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                            </svg>
                            <span><?php echo esc_html($tour_time); ?></span>
                        </div>
                        <?php
    endif; ?>
                        
                        <div class="summary-meta-item summary-meta-pax">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                            </svg>
                            <span>
                                <?php
    printf(_n('%d Viajero', '%d Viajeros', $total_pax, 'wiwa-checkout'), $total_pax);
?>
                            </span>
                        </div>
                        
                        <?php if (!empty($guest_breakdown) && count($guest_breakdown) > 1): ?>
                        <div class="summary-meta-breakdown">
                            <?php foreach ($guest_breakdown as $type => $count): ?>
                            <span class="breakdown-item"><?php echo esc_html($count . ' ' . $type); ?></span>
                            <?php
        endforeach; ?>
                        </div>
                        <?php
    endif; ?>
                    </div>
                    <div class="summary-product-price">
                        <?php echo wc_price($cart_item['line_total']); ?>
                    </div>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
        
        <hr class="summary-divider">
        
        <div class="summary-prices">
            <div class="price-row">
                <span class="price-label">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1z"/>
                    </svg>
                    <?php
$count = $cart->get_cart_contents_count();
printf(_n('Subtotal (%d tour)', 'Subtotal (%d tours)', $count, 'wiwa-checkout'), $count);
?>
                </span>
                <span class="price-value"><?php echo wc_price($cart->get_subtotal()); ?></span>
            </div>
            
            <?php if ($cart->get_discount_total() > 0): ?>
            <div class="price-row discount-row">
                <span class="price-label">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm6 1a1 1 0 0 1 1 1v5h3a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
                    </svg>
                    <?php _e('Discount', 'wiwa-checkout'); ?>
                </span>
                <span class="price-value discount-value">-<?php echo wc_price($cart->get_discount_total()); ?></span>
            </div>
            <?php
endif; ?>
            
            <?php if ($cart->get_total_tax() > 0): ?>
            <div class="price-row">
                <span class="price-label"><?php _e('Taxes', 'wiwa-checkout'); ?></span>
                <span class="price-value"><?php echo wc_price($cart->get_total_tax()); ?></span>
            </div>
            <?php
endif; ?>
        </div>
        
        <hr class="summary-divider">
        
        <div class="summary-total">
            <div class="flex flex-col">
                <div class="flex items-center justify-between w-full">
                    <span class="total-label">
                        <?php 
                        $have_deposit = false;
                        $sidebar_pending = 0;
                        if (isset(WC()->cart->deposit_data) && !empty(WC()->cart->deposit_data) && function_exists('ovatb_get_meta_data')) {
                            $have_deposit = (bool) ovatb_get_meta_data('have_deposit', WC()->cart->deposit_data);
                            if ($have_deposit) {
                                $sidebar_pending = (float) ovatb_get_meta_data('remaining_total', WC()->cart->deposit_data);
                            }
                        }
                        
                        if ($have_deposit) {
                            _e('Total to pay today', 'wiwa-checkout');
                        } else {
                            _e('Total', 'wiwa-checkout');
                        }
                        ?>
                    </span>
                    <span class="total-value">
                        <?php echo wc_price($cart->get_total('edit')); ?>
                        <?php if (class_exists('Wiwa_FOX_Integration') && Wiwa_FOX_Integration::is_active()): ?>
                            <span class="currency-code"><?php echo Wiwa_FOX_Integration::get_current_currency(); ?></span>
                        <?php else: ?>
                            <span class="currency-code"><?php echo esc_html($currency); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($have_deposit && $sidebar_pending > 0): ?>
                <div class="flex items-center justify-between w-full mt-2 pt-2 border-t border-dashed border-gray-200">
                    <span class="text-red-500 text-[13px] font-medium"><?php _e('Pending payment', 'wiwa-checkout'); ?></span>
                    <span class="text-red-600 text-[14px] font-bold">
                        <?php 
                        if (class_exists('Wiwa_FOX_Integration')) {
                            echo wp_kses_post(Wiwa_FOX_Integration::format_price($sidebar_pending));
                        } else {
                            echo wc_price($sidebar_pending);
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($coupons_enabled): ?>
        <div class="summary-coupon">
            <div class="coupon-input-wrapper">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M3.5 9.5a.5.5 0 0 1 .5.5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .5-.5h2zm0-3a.5.5 0 0 1 .5.5v1.5a.5.5 0 0 1-.5.5h-2A.5.5 0 0 1 1 8V6.5a.5.5 0 0 1 .5-.5h2zm0-3a.5.5 0 0 1 .5.5v1.5a.5.5 0 0 1-.5.5h-2A.5.5 0 0 1 1 5V3.5a.5.5 0 0 1 .5-.5h2z"/>
                    <path d="M3 0a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H3zm0 1h10v14H3V1z"/>
                </svg>
                <input type="text" id="coupon_code" placeholder="<?php esc_attr_e('Discount code', 'wiwa-checkout'); ?>" class="coupon-input">
            </div>
            <button type="button" class="btn-apply-coupon" id="apply_coupon"><?php _e('Apply', 'wiwa-checkout'); ?></button>
        </div>
        <div id="coupon-message" class="coupon-message" style="display: none;"></div>
        <?php
endif; ?>
    </div>
</div>
