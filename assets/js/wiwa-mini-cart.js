/**
 * Wiwa Tour Checkout - Cart Frontend Logic
 * Version: 2.9.9 (UX Refactor)
 */

jQuery(function ($) {
    
    console.log('Wiwa Cart: Smart Pax v2.9.9 Loaded');

    /* =========================================
       1. QUANTITY SELECTOR UI (Shared)
       ========================================= */
    $(document.body).on('click', '.wiwa-qty-btn', function (e) {
        e.preventDefault();
        
        var $button = $(this);
        var $wrapper = $button.closest('.wiwa-mini-cart-qty');
        var $input = $wrapper.find('.wiwa-qty-input');
        
        var currentVal = parseFloat($input.val()) || 0;
        var step = 1;
        var min = parseFloat($input.attr('min')) || 0;
        var max = 99;
        
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
            $input.data('last-action', $button.hasClass('wiwa-qty-plus') ? 'increase' : 'decrease');
        }
    });

    /* =========================================
       2. SMART PAX / QUANTITY CONTROLLER
       ========================================= */
    var updateTimer;

    $(document.body).on('change', '.wiwa-qty-input', function () {
        var $input = $(this);
        var cartKey = $input.data('cart-key');
        var isTour  = $input.data('is-tour') == '1';
        var qty     = $input.val();
        var action  = $input.data('last-action') || 'update';

        if (!cartKey) return;

        // Visual loading state
        var $itemRow = $input.closest('.elementor-menu-cart__product, .mini_cart_item, tr.cart_item');
        $itemRow.addClass('wiwa-loading');

        clearTimeout(updateTimer);

        updateTimer = setTimeout(function () {
            if (isTour) {
                updateTourPax(cartKey, qty, action, $input, $itemRow);
            } else {
                updateStandardQty(cartKey, qty, $itemRow);
            }
        }, 400);
    });

    /**
     * Handler for Smart Pax (OvaTourBooking)
     */
    function updateTourPax(cartKey, qty, action, $input, $itemRow) {
        $.ajax({
            type: 'POST',
            url: wiwa_vars.ajax_url,
            data: {
                action: 'wiwa_update_tour_pax',
                cart_key: cartKey,
                qty: qty,
                update_action: action,
                security: wiwa_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Wiwa: Pax Updated', response.data);
                    $(document.body).trigger('wc_fragment_refresh');
                    $(document.body).trigger('updated_cart_totals');
                } else {
                    console.warn('Wiwa: Pax Update Failed', response);
                    $(document.body).trigger('wc_fragment_refresh');
                }
            },
            error: function() {
                console.error('Wiwa: AJAX Error on pax update');
            },
            complete: function() {
                $itemRow.removeClass('wiwa-loading');
            }
        });
    }

    /**
     * Handler for Standard Products
     */
    function updateStandardQty(cartKey, qty, $itemRow) {
        
        // Main Cart: trigger native WC update
        if ($('.woocommerce-cart-form').length) {
            $('[name="update_cart"]').removeAttr('disabled').prop('disabled', false).trigger('click');
            setTimeout(function(){ $itemRow.removeClass('wiwa-loading'); }, 1500);
            return;
        }

        // Mini Cart AJAX
        $.ajax({
            type: 'POST',
            url: wiwa_vars.ajax_url,
            data: {
                action: 'wiwa_update_mini_cart_qty',
                cart_key: cartKey,
                qty: qty,
                security: wiwa_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    $(document.body).trigger('wc_fragment_refresh');
                }
            },
            error: function() {
                console.error('Wiwa: AJAX Error on qty update');
            },
            complete: function() {
                $itemRow.removeClass('wiwa-loading');
            }
        });
    }

    /* =========================================
       3. POPUP "ADD TO CART" INJECTION
       ========================================= */
    function injectAddToCartButton() {
        var $bookingForm = $('.ovatb_booking_form, #booking-form'); 
        
        if ($bookingForm.length && $('#btn-add-to-cart-soft').length === 0) {
            var $submitBtn = $bookingForm.find('button[type="submit"]');
            
            if ($submitBtn.length) {
                var btnHtml = '<button type="button" id="btn-add-to-cart-soft">' + 
                              '<span>🛒</span> Agregar al Carrito' + 
                              '</button>';
                
                $submitBtn.after(btnHtml);
                console.log('Wiwa: Add to Cart button injected.');
            }
        }
    }

    injectAddToCartButton();
    $(window).on('jet-popup/show', function() {
        setTimeout(injectAddToCartButton, 500); 
    });
    $(document).ajaxComplete(function() {
        if ($('#btn-add-to-cart-soft').length === 0) {
            injectAddToCartButton();
        }
    });

});
