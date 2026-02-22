jQuery(document).ready(function($) {
    if (typeof wiwaCheckout === 'undefined' || !wiwaCheckout.geoIp) {
        return;
    }

    const { autoComplete, detectCountry } = wiwaCheckout.geoIp;

    // We only execute if at least one of these settings is on
    if (autoComplete !== '1' && detectCountry !== '1') {
        return;
    }

    const $cityInput = $('#billing_city');
    const $countrySelect = $('#billing_country');
    
    // We only want to populate if fields are empty to avoid overwriting user data
    const shouldFetch = ($cityInput.length && !$cityInput.val() && autoComplete === '1') || 
                        ($countrySelect.length && !$countrySelect.val() && detectCountry === '1');

    if (shouldFetch) {
        $.ajax({
            url: wiwaCheckout.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wiwa_get_geoip',
                nonce: wiwaCheckout.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    
                    if (detectCountry === '1' && data.country && $countrySelect.length && !$countrySelect.val()) {
                        $countrySelect.val(data.country);
                        // Trigger change for Select2 update
                        if ($countrySelect.hasClass('select2-hidden-accessible')) {
                            $countrySelect.trigger('change.select2');
                        } else {
                            $countrySelect.trigger('change');
                        }
                    }

                    if (autoComplete === '1' && data.city && $cityInput.length && !$cityInput.val()) {
                        $cityInput.val(data.city).trigger('input').trigger('blur');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Wiwa GeoIP Error:', error);
            }
        });
    }
});
