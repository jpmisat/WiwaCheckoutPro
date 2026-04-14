/**
 * Wiwa Cart Persistence
 * Persists pax and guest info inputs on the cart page via sessionStorage.
 */
jQuery(document).ready(function($) {
    'use strict';

    var STORAGE_PREFIX = 'wiwa_cart_data_';

    function saveInputValue(input) {
        var name = $(input).attr('name');
        if (name) {
            sessionStorage.setItem(STORAGE_PREFIX + name, $(input).val() || '');
        }
    }

    function restoreInputValue(input) {
        var name = $(input).attr('name');
        if (name) {
            var savedVal = sessionStorage.getItem(STORAGE_PREFIX + name);
            if (savedVal !== null) {
                $(input).val(savedVal);
                if ($(input).hasClass('select2-hidden-accessible') || $(input).hasClass('ovatb-select2')) {
                    $(input).trigger('change.select2');
                }
            }
        }
    }

    // Selectors for cart-page inputs to persist
    var selector = '.wiwa-pax-input, input[name*="guest_info"], select[name*="guest_info"], textarea[name*="guest_info"]';

    // Restore on load
    $(selector).each(function() {
        restoreInputValue(this);
    });

    // Save on change/input
    $(document).on('input change', selector, function() {
        saveInputValue(this);
    });

    // Clear storage when cart is empty (e.g. after checkout or manual removal)
    if ($('.cart-empty').length > 0 || $('.woocommerce-info').text().indexOf('vacío') > -1) {
        Object.keys(sessionStorage).forEach(function(key) {
            if (key.startsWith(STORAGE_PREFIX)) {
                sessionStorage.removeItem(key);
            }
        });
    }
});
