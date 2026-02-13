/**
 * Wiwa Tour Checkout - Cart Frontend Logic
 * Version: 2.11.1
 * Handles quantity stepper + AJAX cart updates for WooCommerce & OvaTourBooking
 *
 * For OvaTourBooking tours, WC quantity is always 1.
 * The actual traveler count is stored in cart_item['numberof_{guest}'] metadata.
 * We must use our custom AJAX handler (wiwa_update_tour_pax) to update it.
 */

jQuery(function ($) {
    'use strict';

    // Debounce function
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
     * Update Quantity on click
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
    $(document.body).on('click', '.wiwa-qty-minus, .wiwa-qty-plus', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);

        // Find input: either inside same stepper pill parent, or as sibling
        var $pill = $btn.closest('.wiwa-stepper-pill');
        var $input;
        if ($pill.length) {
            $input = $pill.find('.wiwa-qty-input');
        } else {
            $input = $btn.siblings('.wiwa-qty-input');
        }

        if (!$input.length) return;

        var action = $btn.hasClass('wiwa-qty-plus') ? 'increase' : 'decrease';
        updateQuantity($input, action);
    });

    /**
     * Handle Input Change - Trigger Cart Update
     */
    var triggerCartUpdate = debounce(function ($input) {
        var cartKey  = $input.data('cart-key');
        var isTour   = $input.data('is-tour');
        var guestKey = $input.data('guest-key');
        var qty      = parseInt($input.val(), 10);
        var $article = $input.closest('article');

        // Visual Feedback
        if ($article.length) {
            $article.addClass('wiwa-loading');
        }

        if (isTour && cartKey) {
            // OvaTourBooking: Use custom AJAX handler to update numberof_{guest} metadata
            // (WC quantity stays at 1 for tours — we update pax via their metadata keys)
            updateTourPaxAjax(cartKey, qty, guestKey || 'adult', $article);
        } else if ($('form.woocommerce-cart-form').length > 0) {
            // Standard WC product on main cart page: Click hidden "Update Cart" button
            var $updateBtn = $('button[name="update_cart"]');
            if ($updateBtn.length) {
                $updateBtn.prop('disabled', false).trigger('click');
            }
        }
    }, 500);

    $(document.body).on('change', '.wiwa-qty-input', function () {
        triggerCartUpdate($(this));
    });

    /**
     * Custom AJAX for Tour Pax Update
     * Updates the OvaTourBooking numberof_{guest} metadata in the cart session.
     * On success, reloads the page so that all totals, prices, and line items refresh.
     */
    function updateTourPaxAjax(cartKey, qty, guestKey, $el) {
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
                    // Trigger standard WC cart fragment refresh instead of hard reload
                    // This keeps the sidebar open if possible
                    $(document.body).trigger('wc_fragment_refresh');
                    $(document.body).trigger('wc_fragments_refreshed'); // Force trigger for side-cart.js
                    $(document.body).trigger('wc_update_cart');
                } else {
                    console.error('[Wiwa] Tour pax update error:', response.data ? response.data.message : 'Unknown');
                    if ($el && $el.length) {
                        $el.removeClass('wiwa-loading');
                    }
                }
            },
            error: function (xhr, status, err) {
                console.error('[Wiwa] AJAX error:', err);
                if ($el && $el.length) {
                    $el.removeClass('wiwa-loading');
                }
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
