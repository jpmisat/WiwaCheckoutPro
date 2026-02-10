jQuery(document).ready(function ($) {
    /**
     * Sincronizar cambio de moneda con FOX
     */
    $(document).on('click', '.currency-btn', function () {
        var currency = $(this).data('currency');
        changeCurrency(currency);
    });

    $('#wiwa-currency-select').on('change', function () {
        var currency = $(this).val();
        changeCurrency(currency);
    });

    function changeCurrency(currency) {
        // Usar función nativa de FOX
        if (typeof woocs_redirect_to_currency === 'function') {
            woocs_redirect_to_currency(currency);
        } else {
            // Fallback: AJAX
            $.ajax({
                url: wiwaCheckout.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wiwa_change_currency',
                    currency: currency,
                    nonce: wiwaCheckout.nonce
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    }
});
