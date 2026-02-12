/**
 * Wiwa Tour Checkout - Cart Frontend Logic
 * Version: 2.10.2
 * Refactored for Robust AJAX Updates & Card UI Support
 */

jQuery(function ($) {
    'use strict';

    // Debounce function to limit AJAX calls
    function debounce(func, wait) {
        var timeout;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Update Quantity Logic
     */
    function updateQuantity($input, action) {
        var current = parseInt($input.val(), 10) || 1;
        var min = parseInt($input.attr('min'), 10) || 1;
        var max = parseInt($input.attr('max'), 10) || 99;
        var next = current;

        if (action === 'increase') {
            next = Math.min(max, current + 1);
        } else if (action === 'decrease') {
            next = Math.max(min, current - 1);
        }

        if (next !== current) {
            $input.val(next).trigger('change');
        }
    }

    /**
     * Handle Click on +/- Buttons
     */
    $(document.body).on('click', '.wiwa-qty-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $input = $btn.siblings('.wiwa-qty-input');
        var action = $btn.hasClass('wiwa-qty-plus') ? 'increase' : 'decrease';

        updateQuantity($input, action);
    });

    /**
     * Handle Input Change - Trigger Cart Update
     */
    var triggerCartUpdate = debounce(function ($input) {
        var isMainCart = $('.woocommerce-cart-form').length > 0;
        var $row = $input.closest('tr.cart_item, .elementor-menu-cart__product');

        // Visual Feedback
        $row.addClass('wiwa-loading');

        if (isMainCart) {
            // STRATEGY: Click the hidden "Update Cart" button
            // This forces WooCommerce to handle the recalculation logic natively
            var $updateBtn = $('[name="update_cart"]');
            
            // Enable button just in case it was disabled
            $updateBtn.prop('disabled', false).trigger('click');
        } else {
            // For Sidebar/Mini-Cart: Use custom AJAX or existing WC fragments
            // Here we fallback to the custom AJAX handler for tour pax if needed
            // But if it's a standard mini-cart, we might need a different approach.
            // For now, let's try to refresh fragments if possible.
            
            var cartKey = $input.data('cart-key');
            var qty = $input.val();
            
            // If it is a tour item in sidebar, we use our custom handler
             if ($input.data('is-tour')) {
                updateTourPaxSidebar(cartKey, qty, $input.data('guest-key'), $row);
             }
        }
    }, 500);

    $(document.body).on('change', '.wiwa-qty-input', function () {
        triggerCartUpdate($(this));
    });

    /**
     * Custom AJAX for Sidebar Tour Pax Update
     */
    function updateTourPaxSidebar(cartKey, qty, guestKey, $row) {
        $.ajax({
            type: 'POST',
            url: wiwa_vars.ajax_url,
            data: {
                action: 'wiwa_update_tour_pax',
                cart_key: cartKey,
                qty: qty,
                guest_key: guestKey,
                security: wiwa_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    $(document.body).trigger('wc_fragment_refresh');
                } else {
                    // Revert on error
                    console.error(response.data.message);
                }
            },
            complete: function() {
                $row.removeClass('wiwa-loading');
            }
        });
    }

    /**
     * Remove Loading State after Fragments Refresh
     */
    $(document.body).on('wc_fragments_refreshed updated_cart_totals', function () {
        $('.wiwa-loading').removeClass('wiwa-loading');
    });

    /**
     * Inject "Add to Cart" into Booking Form (if missing)
     */
    function injectAddToCart() {
        if ($('#btn-add-to-cart-soft').length === 0) {
            var $form = $('.ovatb_booking_form, #booking-form');
            if ($form.length) {
                var btnHtml = '<button type="button" id="btn-add-to-cart-soft" class="button alt">Agregar al carrito</button>';
                $form.find('button[type="submit"]').after(btnHtml);
            }
        }
    }
    
    // Run injection on load and popup open
    injectAddToCart();
    $(window).on('jet-popup/show', injectAddToCart);

});
