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
        // Use YellowTree GeoIP Detect JS API
        if (typeof geoip2 !== 'undefined' && typeof geoip2.city === 'function') {
            geoip2.city(function(response) {
                // Determine Country
                if (detectCountry === '1' && $countrySelect.length && !$countrySelect.val() && response.country && response.country.iso_code) {
                    $countrySelect.val(response.country.iso_code);
                    
                    // Trigger change for Select2 update
                    if ($countrySelect.hasClass('select2-hidden-accessible')) {
                        $countrySelect.trigger('change.select2');
                    } else {
                        $countrySelect.trigger('change');
                    }
                }

                // Determine City (prefer Spanish, fallback to English)
                if (autoComplete === '1' && $cityInput.length && !$cityInput.val() && response.city && response.city.names) {
                    const cityName = response.city.names.es || response.city.names.en || '';
                    if (cityName) {
                        $cityInput.val(cityName).trigger('input').trigger('blur');
                    }
                }
            }, function(error) {
                console.error('Wiwa GeoIP Detect JS API Error:', error);
            });
        } else {
            console.warn('Wiwa GeoIP: geoip2 object is not available. Ensure YellowTree GeoIP Detect is active and JS API is enabled.');
        }
    }
});
