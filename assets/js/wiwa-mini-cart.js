/**
 * Wiwa Tour Checkout - Cart Frontend Logic
 * Version: 2.10.1
 */

jQuery(function ($) {
    var pendingUpdates = {};

    function parseQty(value, fallback) {
        var qty = parseInt(value, 10);
        return isNaN(qty) ? fallback : qty;
    }

    function getItemRow($input) {
        return $input.closest('.elementor-menu-cart__product, .mini_cart_item, tr.cart_item');
    }

    function setLoadingState($row, isLoading) {
        if (!$row.length) {
            return;
        }

        $row.toggleClass('wiwa-loading', isLoading);
        $row.attr('aria-busy', isLoading ? 'true' : 'false');
        $row.find('.wiwa-qty-btn').prop('disabled', isLoading);
    }

    function updateButtonState($input) {
        var $wrapper = $input.closest('.wiwa-mini-cart-qty');
        var qty = parseQty($input.val(), 1);
        var min = parseQty($input.attr('min'), 0);

        $wrapper.find('.wiwa-qty-minus').prop('disabled', qty <= min);
    }

    function refreshFragments() {
        $(document.body).trigger('wc_fragment_refresh');
    }

    function replaceCartTotals(totalsHtml) {
        if (!totalsHtml || !$('.wiwa-cart-collaterals').length) {
            return;
        }

        $('.wiwa-cart-collaterals').html(totalsHtml);
        $(document.body).trigger('updated_cart_totals');
    }

    function syncGuestBreakdown($row, breakdown) {
        if (!$row.length || !breakdown) {
            return;
        }

        Object.keys(breakdown).forEach(function (slug) {
            var count = parseQty(breakdown[slug], 0);
            var selector = '.wiwa-hidden-guest-input[data-guest-slug="' + slug + '"]';
            $row.find(selector).val(count);
        });
    }

    function showRowError($row, message) {
        if (!message || !$row.length || !$row.is('tr.cart_item')) {
            return;
        }

        $row.find('.wiwa-row-error').remove();
        $row.find('.product-quantity').append('<span class="wiwa-row-error">' + message + '</span>');

        setTimeout(function () {
            $row.find('.wiwa-row-error').fadeOut(200, function () {
                $(this).remove();
            });
        }, 2200);
    }

    function applyResponse($input, $row, data) {
        var qtyForInput = data.total_pax || data.new_qty || parseQty($input.val(), 1);
        $input.val(qtyForInput);
        $input.data('previous', qtyForInput);

        if (data.item_removed && $row.length) {
            $row.fadeOut(220, function () {
                $(this).remove();
                if ($('.woocommerce-cart-form tr.cart_item').length === 0 && $('body.woocommerce-cart').length) {
                    window.location.reload();
                }
            });
        }

        if (data.item_subtotal && $row.length) {
            $row.find('.wiwa-item-subtotal-value').html(data.item_subtotal);
        }

        if ($row.length && data.total_pax) {
            var paxText = data.total_pax === 1 ? '1 viajero' : data.total_pax + ' viajeros';
            $row.find('.wiwa-pax-total-value').text(paxText);
        }

        if ($row.length && data.guest_breakdown_text) {
            var $breakdown = $row.find('.wiwa-pax-breakdown');
            if ($breakdown.length) {
                $breakdown.text(data.guest_breakdown_text);
            }
        }

        syncGuestBreakdown($row, data.guest_breakdown);
        replaceCartTotals(data.totals_html);
        refreshFragments();
        updateButtonState($input);
    }

    function requestQtyUpdate($input, nextQty, action) {
        var cartKey = $input.data('cart-key');
        var isTour = String($input.data('is-tour')) === '1';
        var guestKey = $input.data('guest-key') || '';
        var $row = getItemRow($input);
        var previous = parseQty($input.data('previous'), parseQty($input.val(), 1));

        if (!cartKey || pendingUpdates[cartKey]) {
            return;
        }

        pendingUpdates[cartKey] = true;
        $input.val(nextQty);
        setLoadingState($row, true);

        var payload = {
            cart_key: cartKey,
            qty: nextQty,
            security: wiwa_vars.nonce
        };

        if (isTour) {
            payload.action = 'wiwa_update_tour_pax';
            payload.update_action = action || 'update';
            payload.guest_key = guestKey;
        } else {
            payload.action = 'wiwa_update_mini_cart_qty';
        }

        $.ajax({
            type: 'POST',
            url: wiwa_vars.ajax_url,
            data: payload
        }).done(function (response) {
            if (response && response.success) {
                applyResponse($input, $row, response.data || {});
                return;
            }

            var message = response && response.data && response.data.message ? response.data.message : 'No se pudo actualizar la cantidad.';
            $input.val(previous);
            showRowError($row, message);
        }).fail(function () {
            $input.val(previous);
            showRowError($row, 'Error de conexion al actualizar viajeros.');
        }).always(function () {
            pendingUpdates[cartKey] = false;
            setLoadingState($row, false);
            updateButtonState($input);
        });
    }

    $(document.body).on('click', '.wiwa-qty-btn', function (event) {
        event.preventDefault();

        var $button = $(this);
        if ($button.prop('disabled')) {
            return;
        }

        var $wrapper = $button.closest('.wiwa-mini-cart-qty');
        var $input = $wrapper.find('.wiwa-qty-input');
        if (!$input.length) {
            return;
        }

        var current = parseQty($input.val(), 1);
        var min = parseQty($input.attr('min'), 0);
        var max = parseQty($input.attr('max'), 99);
        var next = current;
        var action = 'update';

        if ($button.hasClass('wiwa-qty-minus')) {
            next = Math.max(min, current - 1);
            action = 'decrease';
        }

        if ($button.hasClass('wiwa-qty-plus')) {
            next = Math.min(max, current + 1);
            action = 'increase';
        }

        if (next === current) {
            updateButtonState($input);
            return;
        }

        requestQtyUpdate($input, next, action);
    });

    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded updated_wc_div updated_cart_totals', function () {
        $('.wiwa-qty-input').each(function () {
            var $input = $(this);
            if (!$input.data('previous')) {
                $input.data('previous', parseQty($input.val(), 1));
            }
            updateButtonState($input);
        });
    });

    $('.wiwa-qty-input').each(function () {
        var $input = $(this);
        $input.data('previous', parseQty($input.val(), 1));
        updateButtonState($input);
    });

    function injectAddToCartButton() {
        var $bookingForm = $('.ovatb_booking_form, #booking-form');
        if (!$bookingForm.length || $('#btn-add-to-cart-soft').length) {
            return;
        }

        var $submitBtn = $bookingForm.find('button[type="submit"]');
        if (!$submitBtn.length) {
            return;
        }

        var buttonHtml = '<button type="button" id="btn-add-to-cart-soft"><span class="wiwa-btn-icon">+</span> Agregar al carrito</button>';
        $submitBtn.after(buttonHtml);
    }

    injectAddToCartButton();

    $(window).on('jet-popup/show', function () {
        setTimeout(injectAddToCartButton, 350);
    });

    $(document).ajaxComplete(function () {
        if (!$('#btn-add-to-cart-soft').length) {
            injectAddToCartButton();
        }
    });
});
