<?php
/**
 * Checkout Step 2: Pago
 * Integrated with WOOCS Currency Switcher for live currency switching
 */
defined('ABSPATH') || exit;

// Add body class for step 2 (for CSS targeting)
add_filter('body_class', function ($classes) {
    $classes[] = 'checkout-step-2';
    return $classes;
});

// Get the configured checkout page URL
$checkout_page_id = get_option('wiwa_checkout_page_id');
$checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : wc_get_checkout_url();

if (!isset($_POST['billing_first_name']) && !WC()->session->get('wiwa_step_1_data')) {
    wp_redirect(add_query_arg('step', '1', $checkout_url));
    exit;
}

if (isset($_POST['billing_first_name'])) {
    WC()->session->set('wiwa_step_1_data', $_POST);
}

$step_1_data = WC()->session->get('wiwa_step_1_data');

// Get currencies from WOOCS if available
$currencies = [];
$current_currency = get_woocommerce_currency();
$woocs_active = false;

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
];

if (class_exists('WOOCS') && isset($GLOBALS['WOOCS'])) {
    $woocs = $GLOBALS['WOOCS'];
    $woocs_currencies = $woocs->get_currencies();
    $current_currency = $woocs->current_currency;
    $woocs_active = true;

    // Convert WOOCS format to our format
    foreach ($woocs_currencies as $code => $data) {
        $flag = isset($currency_flags[$code]) ? $currency_flags[$code] : '🏧';
        $currencies[$code] = [
            'name' => isset($data['name']) ? $data['name'] : $code,
            'flag' => $flag
        ];
    }
}
else {
    // Fallback to default currencies (limited functionality)
    $currencies = [
        'COP' => ['name' => 'Peso Colombiano', 'flag' => '🇨🇴'],
        'USD' => ['name' => 'US Dollar', 'flag' => '🇺🇸'],
        'EUR' => ['name' => 'Euro', 'flag' => '🇪🇺'],
    ];
}
?>
<form id="wiwa-checkout-step-2" class="wiwa-checkout-form checkout woocommerce-checkout" method="post" enctype="multipart/form-data">
    <h2><?php _e('Select your payment method', 'wiwa-checkout'); ?></h2>
    
    <div class="currency-selector-section">
        <h3><?php _e('Payment currency', 'wiwa-checkout'); ?></h3>
        <div class="currency-dropdown-wrapper">
        <select name="order_currency" id="wiwa-currency-select" class="wiwa-select currency-select" data-woocs-active="<?php echo $woocs_active ? '1' : '0'; ?>">
                <?php foreach ($currencies as $code => $data):
    $flag = is_array($data) ? ($data['flag'] ?? '') : '';
    $name = is_array($data) ? ($data['name'] ?? $code) : $data;
?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($current_currency, $code); ?>>
                        <?php echo $flag . ' ' . esc_html($code) . ' - ' . esc_html($name); ?>
                    </option>
                <?php
endforeach; ?>
            </select>
            <span class="currency-loading" style="display: none;">
                <svg class="spinner" width="20" height="20" viewBox="0 0 50 50">
                    <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="80" stroke-linecap="round"/>
                </svg>
                <?php _e('Updating...', 'wiwa-checkout'); ?>
            </span>
        </div>
        <p class="description"><?php _e('The price will update automatically based on the selected currency.', 'wiwa-checkout'); ?></p>
    </div>
    
    <hr class="section-divider">
    
    <div class="payment-methods-section">
        <h3><?php _e('Payment method', 'wiwa-checkout'); ?></h3>
        <div class="payment-methods-wrapper">
            <?php
$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
if (!empty($available_gateways)):
    $first = true;
    foreach ($available_gateways as $gateway):
?>
            <label class="payment-method-option">
                <input type="radio" name="payment_method" value="<?php echo esc_attr($gateway->id); ?>" <?php checked($first, true); ?>>
                <div class="payment-method-content">
                    <div class="payment-method-header">
                        <span class="payment-method-icon"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></span>
                        <span class="payment-method-title"><?php echo $gateway->get_title(); ?></span>
                    </div>
                    <?php if ($gateway->has_fields() || $gateway->get_description()): ?>
                        <div class="payment-method-description" style="display: <?php echo $first ? 'block' : 'none'; ?>;">
                            <?php $gateway->payment_fields(); ?>
                        </div>
                    <?php
        endif; ?>
                </div>
            </label>
            <?php $first = false;
    endforeach;
else: ?>
                <div class="woocommerce-info"><?php _e('No payment methods available.', 'wiwa-checkout'); ?></div>
            <?php
endif; ?>
        </div>
    </div>
    
    <div class="trust-badge">
        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/><path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/></svg>
        <span><?php printf(__('Payments in %s are encrypted and protected.', 'wiwa-checkout'), '<strong>wiwatour.com</strong>'); ?></span>
    </div>
    
    <div class="form-footer form-footer-step2">
        <button type="submit" class="btn-primary btn-submit" id="place_order">
            <?php _e('Confirm and pay', 'wiwa-checkout'); ?>
        </button>
        <a href="<?php echo esc_url(add_query_arg('step', '1', $checkout_url)); ?>" class="btn-secondary">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <?php _e('Back', 'wiwa-checkout'); ?>
        </a>
    </div>
    
    <?php foreach ($step_1_data as $key => $value): ?>
        <?php if (is_array($value)): ?>
            <?php foreach ($value as $subkey => $subvalue): ?>
                <?php if (is_array($subvalue)): ?>
                    <?php foreach ($subvalue as $sk => $sv): ?>
                        <input type="hidden" name="<?php echo esc_attr($key . '[' . $subkey . '][' . $sk . ']'); ?>" value="<?php echo esc_attr($sv); ?>">
                    <?php
                endforeach; ?>
                <?php
            else: ?>
                    <input type="hidden" name="<?php echo esc_attr($key . '[' . $subkey . ']'); ?>" value="<?php echo esc_attr($subvalue); ?>">
                <?php
            endif; ?>
            <?php
        endforeach; ?>
        <?php
    else: ?>
            <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
        <?php
    endif; ?>
    <?php
endforeach; ?>
    
    <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
    <input type="hidden" name="wiwa_checkout_step" value="2">
</form>

<script>
jQuery(document).ready(function($) {
    var $currencySelect = $('#wiwa-currency-select');
    var $loading = $('.currency-loading');
    
    $currencySelect.on('change', function() {
        var currency = $(this).val();
        var foxActive = $(this).data('fox-active');
        
        if (!foxActive) {
            console.log('FOX Currency Switcher not active');
            return;
        }
        
        // Show loading state
        $loading.show();
        $currencySelect.prop('disabled', true);
        
        // Call FOX directly using their global event
        if (typeof woocs_redirect !== 'undefined') {
            // FOX legacy method
            woocs_redirect(currency);
        } else if (typeof window.woocs_current_currency !== 'undefined' && typeof window.woocs_ajax_url !== 'undefined') {
            // FOX AJAX method
            <?php
            // Construct context-aware AJAX URL
            $ajax_url = admin_url('admin-ajax.php');
            $lang = apply_filters('wpml_current_language', null);
            if (!$lang && function_exists('pll_current_language')) {
                $lang = pll_current_language();
            }
            if ($lang) {
                $ajax_url = add_query_arg('lang', $lang, $ajax_url);
            }
            ?>
            $.ajax({
                url: window.woocs_ajax_url || '<?php echo esc_url($ajax_url); ?>',
                type: 'POST',
                data: {
                    action: 'woocs_set_currency',
                    currency: currency
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    $loading.hide();
                    $currencySelect.prop('disabled', false);
                    alert('<?php _e('Error changing currency. Please reload the page.', 'wiwa-checkout'); ?>');
                }
            });
        } else {
            // Fallback: use our AJAX handler
            $.ajax({
                url: wiwaCheckout.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wiwa_change_currency',
                    currency: currency,
                    nonce: wiwaCheckout.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php _e('Error changing currency', 'wiwa-checkout'); ?>');
                        $loading.hide();
                        $currencySelect.prop('disabled', false);
                    }
                },
                error: function() {
                    $loading.hide();
                    $currencySelect.prop('disabled', false);
                    alert('<?php _e('Request error', 'wiwa-checkout'); ?>');
                }
            });
        }
    });
    
    // Show/hide payment method descriptions
    $('input[name="payment_method"]').on('change', function() {
        $('.payment-method-description').hide();
        $(this).closest('.payment-method-option').find('.payment-method-description').show();
    });
});
</script>

<style>
.currency-loading {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 10px;
    color: #666;
    font-size: 14px;
}
.currency-loading .spinner {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.currency-dropdown-wrapper {
    display: flex;
    align-items: center;
}
</style>
