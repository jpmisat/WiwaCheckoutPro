/**
 * Wiwa Tour Checkout - Cart Frontend Logic
 * Version: 2.9.6 (Creative Update)
 */

jQuery(function ($) {
    
    console.log('Wiwa Cart: Smart Pax & Creative UI Loaded');

    /* =========================================
       1. QUANTITY SELECTOR UI (Shared)
       ========================================= */
    $(document.body).on('click', '.wiwa-qty-btn', function (e) {
        e.preventDefault();
        
        var $button = $(this);
        var $wrapper = $button.closest('.wiwa-mini-cart-qty');
        var $input = $wrapper.find('.wiwa-qty-input');
        
        // Safety check, though inputs should be read-only
        var currentVal = parseFloat($input.val()) || 0;
        var step = 1;
        var min = parseFloat($input.attr('min')) || 0;
        var max = 99; // Hard limit
        
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

        // Trigger change if value differs
        if (newVal !== currentVal) {
            $input.val(newVal).trigger('change');
            
            // Helpful: Store action for AJAX
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
        var isTour  = $input.data('is-tour') == '1'; // Coerce to boolean
        var qty     = $input.val();
        var action  = $input.data('last-action') || 'update'; // 'increase', 'decrease', or 'update'

        if (!cartKey) return;

        // Visual feedback
        var $itemRow = $input.closest('.elementor-menu-cart__product, tr.cart_item');
        $itemRow.css('opacity', '0.5');

        clearTimeout(updateTimer);

        updateTimer = setTimeout(function () {
            
            // Case A: Smart Pax Update (Tour Metadata)
            if (isTour) {
                updateTourPax(cartKey, qty, action, $input, $itemRow);
            } 
            // Case B: Standard WooCommerce Quantity
            else {
                updateStandardQty(cartKey, qty, $itemRow);
            }

        }, 500); // 500ms Debounce
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
                qty: qty,       // Sending raw qty just in case
                update_action: action, // Sending direction (increase/decrease)
                security: wiwa_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Wiwa: Pax Updated', response.data);
                    // Refresh Cart Fragments (Standard WC Trigger)
                    $(document.body).trigger('wc_fragment_refresh');
                    $(document.body).trigger('updated_cart_totals'); // Main cart refresh
                } else {
                    console.warn('Wiwa: Pax Update Failed', response);
                    alert(response.data.message || 'Cannot update passengers. Please check details page.');
                    // Revert input? Reloading fragments handles this usually.
                    $(document.body).trigger('wc_fragment_refresh');
                }
            },
            complete: function() {
                $itemRow.css('opacity', '1');
            }
        });
    }

    /**
     * Handler for Standard Products (Mini Cart & Main Cart)
     */
    function updateStandardQty(cartKey, qty, $itemRow) {
        
        // If we are in Main Cart page, triggering the native "Update Cart" button is safer/better
        if ($('.woocommerce-cart-form').length) {
            $('[name="update_cart"]').prop('disabled', false).trigger('click');
            // Opacity handled by WC scripts usually, but let's reset ours just in case
            setTimeout(function(){ $itemRow.css('opacity', '1'); }, 1000);
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
            complete: function() {
                $itemRow.css('opacity', '1');
            }
        });
    }


    /* =========================================
       3. POPUP "ADD TO CART" INJECTION
       ========================================= */
    function injectAddToCartButton() {
        var $bookingForm = $('.ovatb_booking_form, #booking-form'); 
        
        // Only inject if it doesn't exist and we have a form
        if ($bookingForm.length && $('#btn-add-to-cart-soft').length === 0) {
            var $submitBtn = $bookingForm.find('button[type="submit"]');
            
            if ($submitBtn.length) {
                // Style: Clean outline/secondary button next to primary
                var btnHtml = '<button type="button" id="btn-add-to-cart-soft" class="btn btn-secondary" style="margin-left:10px; background-color:#fff; color:#333; border:1px solid #ccc; padding: 10px 20px; border-radius: 5px; cursor: pointer;">' + 
                              '<span class="icon-cart"></span> Agregar al Carrito' + 
                              '</button>';
                
                $submitBtn.after(btnHtml);
                console.log('Wiwa: Add to Cart button injected.');
            }
        }
    }

    // Timers to ensure injection happens after popup load
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
