/**
 * Wiwa Tour Checkout - Mini Cart Quantity Handler
 * Adds AJAX functionality to the [ - ] [ N ] [ + ] quantity inputs
 */

jQuery(function ($) {
    
    // Log for debugging
    console.log('Wiwa Mini Cart Script Loaded');

    // Delegate click events for dynamic content (mini cart reloads via AJAX)
    $(document.body).on('click', '.wiwa-qty-btn', function (e) {
        e.preventDefault();
        
        var $button = $(this);
        var $input = $button.closest('.wiwa-mini-cart-qty').find('.wiwa-qty-input');
        var currentVal = parseFloat($input.val());
        var step = parseFloat($input.attr('step')) || 1;
        var min = parseFloat($input.attr('min')) || 0;
        var max = parseFloat($input.attr('max')) || 999;
        
        var newVal = currentVal;

        if ($button.hasClass('wiwa-qty-minus')) {
            if (currentVal > min) {
                newVal = currentVal - step;
            }
        } else if ($button.hasClass('wiwa-qty-plus')) {
            if (currentVal < max) {
                newVal = currentVal + step;
            }
        }

        if (newVal !== currentVal) {
            $input.val(newVal).trigger('change');
        }
    });

    // Handle Input Change -> Trigger AJAX Update
    var updateTimer;
    $(document.body).on('change', '.wiwa-qty-input', function () {
        var $input = $(this);
        var cartKey = $input.data('cart-key');
        var qty = $input.val();
        
        if (!cartKey) return;

        // Debounce to avoid flooding server
        clearTimeout(updateTimer);
        
        // Visual indicator (optional opacity change)
        $input.closest('.elementor-menu-cart__product').css('opacity', '0.5');

        updateTimer = setTimeout(function () {
            
            $.ajax({
                type: 'POST',
                url: wiwa_vars.ajax_url, // Expecting localized script
                data: {
                    action: 'wiwa_update_mini_cart_qty',
                    cart_key: cartKey,
                    qty: qty,
                    security: wiwa_vars.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Refresh Mini Cart fragments
                        $(document.body).trigger('wc_fragment_refresh');
                    } else {
                        console.error('Error updating cart:', response);
                        // Revert?
                    }
                },
                complete: function() {
                     $input.closest('.elementor-menu-cart__product').css('opacity', '1');
                }
            });

        }, 500); // 500ms delay
    });

});
