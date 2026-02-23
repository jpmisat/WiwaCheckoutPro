jQuery(document).ready(function($) {
    if (typeof wiwaCheckout === 'undefined' || !wiwaCheckout.geoIp) {
        return;
    }

    const { autoComplete, detectCountry, strategy } = wiwaCheckout.geoIp;

    // We only execute if at least one of these settings is on
    if (autoComplete !== '1' && detectCountry !== '1') {
        return;
    }

    const $cityInput = $('#billing_city');
    const $countrySelect = $('#billing_country');
    
    // We only want to populate if fields are empty to avoid overwriting user data
    const shouldFetch = ($cityInput.length && !$cityInput.val() && autoComplete === '1') || 
                        ($countrySelect.length && !$countrySelect.val() && detectCountry === '1');

    if (!shouldFetch) {
        return;
    }

    // Function to populate fields based on found data
    function populateFields(countryCode, cityName) {
        if (detectCountry === '1' && $countrySelect.length && !$countrySelect.val() && countryCode) {
            $countrySelect.val(countryCode);
            // Trigger change for Select2 update
            if ($countrySelect.hasClass('select2-hidden-accessible')) {
                $countrySelect.trigger('change.select2');
            } else {
                $countrySelect.trigger('change');
            }
        }

        if (autoComplete === '1' && $cityInput.length && !$cityInput.val() && cityName) {
            $cityInput.val(cityName).trigger('input').trigger('change').trigger('blur');
        }
    }

    if (strategy === 'yellowtree') {
        // Use YellowTree GeoIP Detect JS API
        // Sometimes the script is deferred, so we'll wait for it up to 10 times (5 seconds max)
        let retries = 0;
        const maxRetries = 10;
        
        function tryYellowTree() {
            if (typeof geoip_detect !== 'undefined' && typeof geoip_detect.get_info === 'function') {
                geoip_detect.get_info().then(function(record) {
                    console.log('Wiwa GeoIP Detect Response:', record);

                    let countryCode = '';
                    let cityName = '';

                    // Record object from GeoIP Detect
                    if (record && typeof record.get === 'function') {
                        countryCode = record.get('country.iso_code') || record.get('country.isoCode') || '';
                        cityName = record.get_with_locales('city.name', ['es', 'en']) || record.get('city.name') || '';
                    } else if (record && record.country) { // Fallback to raw object format just in case
                        countryCode = record.country.iso_code || record.country.isoCode || '';
                        if (record.city) {
                            cityName = (record.city.names && (record.city.names.es || record.city.names.en)) || record.city.name || '';
                        }
                    }

                    populateFields(countryCode, cityName);

                }).catch(function(error) {
                    console.error('Wiwa GeoIP Detect JS API Error:', error);
                });
            } else {
                if (retries < maxRetries) {
                    retries++;
                    setTimeout(tryYellowTree, 500); // Wait 500ms and try again
                } else {
                    console.warn('Wiwa GeoIP: geoip_detect object is not available after retries. Ensure YellowTree GeoIP Detect is active and JS API is enabled.');
                }
            }
        }

        // Start checking for YellowTree API
        tryYellowTree();
        
    } else {
        // Fallback to internal MaxMind via AJAX endpoint
        $.ajax({
            url: wiwaCheckout.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wiwa_get_geoip',
                nonce: wiwaCheckout.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateFields(response.data.country || '', response.data.city || '');
                }
            },
            error: function(xhr, status, error) {
                console.error('Wiwa Internal GeoIP Error:', error);
            }
        });
    }
});
