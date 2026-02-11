/**
 * Wiwa Tour Checkout - Cart Frontend Logic
 * Handles:
 * 1. Mini Cart Quantity Selector (AJAX)
 * 2. Main Cart Quantity Selector (Trigger Update)
 * 3. Injection of "Add to Cart" button in Booking Form
 */

jQuery(function ($) {
    
    // Log for debugging
    console.log('Wiwa Cart Frontend Script Loaded');

    /* =========================================
       QUANTITY SELECTOR LOGIC (Shared)
       ========================================= */
    $(document.body).on('click', '.wiwa-qty-btn', function (e) {
        e.preventDefault();
        
        var $button = $(this);
        var $wrapper = $button.closest('.wiwa-mini-cart-qty');
        var $input = $wrapper.find('.wiwa-qty-input');
        var currentVal = parseFloat($input.val()) || 0;
        var step = parseFloat($input.attr('step')) || 1;
        var min = parseFloat($input.attr('min')) || 0;
        var max = parseFloat($input.attr('max')) || 9999;
        
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

    /* =========================================
       MINI CART SPECIFIC (AJAX Update)
       ========================================= */
    var updateTimer;
    $(document.body).on('change', '.wiwa-mini-cart-qty:not(.wiwa-main-cart-qty) .wiwa-qty-input', function () {
        var $input = $(this);
        var cartKey = $input.data('cart-key');
        var qty = $input.val();
        
        if (!cartKey) return;

        clearTimeout(updateTimer);
        
        // Visual indicator
        $input.closest('.elementor-menu-cart__product').css('opacity', '0.5');

        updateTimer = setTimeout(function () {
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
                    } else {
                        console.error('Error updating cart:', response);
                    }
                },
                complete: function() {
                     $input.closest('.elementor-menu-cart__product').css('opacity', '1');
                }
            });
        }, 500); 
    });


    /* =========================================
       MAIN CART SPECIFIC (Trigger Update Button)
       ========================================= */
    $(document.body).on('change', '.wiwa-main-cart-qty .wiwa-qty-input', function () {
        // Enable and click the "Update Cart" button
        var $updateBtn = $('[name="update_cart"]');
        $updateBtn.prop('disabled', false).trigger('click');
    });


    /* =========================================
       POPUP BUTTON INJECTION
       ========================================= */
    // Try to inject the "Add to Cart" button next to "Reservar" if it doesn't exist
    function injectAddToCartButton() {
        var $bookingForm = $('.ovatb_booking_form, #booking-form'); // Try standard selectors
        
        if ($bookingForm.length && $('#btn-add-to-cart-soft').length === 0) {
            var $submitBtn = $bookingForm.find('button[type="submit"]');
            
            if ($submitBtn.length) {
                var btnHtml = '<button type="button" id="btn-add-to-cart-soft" class="btn btn-secondary" style="margin-left:10px; background-color:#fff; color:#333; border:1px solid #ccc;">' + 
                              '<span class="icon-cart"></span> Agregar al Carrito' + 
                              '</button>';
                // Insert after submit button
                $submitBtn.after(btnHtml);
                console.log('Wiwa: Add to Cart button injected.');
            }
        }
    }

    // Run on load and on JetPopup open
    injectAddToCartButton();
    $(window).on('jet-popup/show', function() {
        setTimeout(injectAddToCartButton, 500); // Wait for popup render
    });
    
    // Also run on any AJAX complete just in case content reloads
    $(document).ajaxComplete(function() {
        if ($('#btn-add-to-cart-soft').length === 0) {
            injectAddToCartButton();
        }
    });

});
