jQuery(document).ready(function($) {
    // Prefix for storage keys to avoid conflicts
    const STORAGE_PREFIX = 'wiwa_cart_data_';
    
    // Function to save input value
    function saveInputValue(input) {
        const name = $(input).attr('name');
        const val = $(input).val();
        if (name) {
            sessionStorage.setItem(STORAGE_PREFIX + name, val);
        }
    }

    // Function to restore input value
    function restoreInputValue(input) {
        const name = $(input).attr('name');
        if (name) {
            const savedVal = sessionStorage.getItem(STORAGE_PREFIX + name);
            if (savedVal !== null) {
                $(input).val(savedVal);
            }
        }
    }

    // Identify inputs to persist
    // 1. Pax inputs
    // 2. Any guest info inputs (OvaTourBooking often uses name="ovatb_guest_info[...]")
    const selector = '.wiwa-pax-input, input[name*="guest_info"], select[name*="guest_info"], textarea[name*="guest_info"]';

    // Restore on load
    $(selector).each(function() {
        restoreInputValue(this);
    });

    // Save on change/input
    $(document).on('input change', selector, function() {
        saveInputValue(this);
    });

    // Optional: Clear storage on successful checkout (hook into form submit?)
    // For now, we prefer keeping it just in case. 
    // Maybe clear if cart is empty? 
    if ($('.cart-empty').length > 0) {
       // Ideally clear relevant keys, but simple approach is fine for now
    }
});
