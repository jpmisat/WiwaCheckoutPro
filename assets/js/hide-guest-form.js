/**
 * Wiwa Tour Frontend Script (Vanilla JS)
 * Hides the guest info form in Tour Booking popups
 * This is needed because Tour Booking uses inline styles that override CSS
 */
(function () {
    'use strict';

    // Function to hide guest info elements (handles inline styles)
    function hideGuestInfo() {
        // Target specific classes used by Tour Booking
        const selectors = [
            '.ovatb-guest-info',
            '.guest-info-heading',
            '.guest-info-accordion',
            '.guest-info-item',
            '.guest-info-body',
            '.guest-info-field'
        ];

        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                // Force hide by setting inline style
                el.style.cssText = 'display: none !important; visibility: hidden !important; height: 0 !important; max-height: 0 !important; overflow: hidden !important; opacity: 0 !important;';
            });
        });
    }

    // Run on document ready
    document.addEventListener('DOMContentLoaded', () => {
        hideGuestInfo();

        // Also run when popup triggers generic clicks (delegation)
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ovatb-btn-booking, .ova-booking-btn, .book-now-btn, [data-toggle="modal"]') || e.target.closest('.ovatb-btn-booking, .ova-booking-btn, .book-now-btn, [data-toggle="modal"]')) {
                setTimeout(hideGuestInfo, 100);
                setTimeout(hideGuestInfo, 300);
                setTimeout(hideGuestInfo, 500);
                setTimeout(hideGuestInfo, 1000);
            }
        });

        // MutationObserver to catch dynamically added elements
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver((mutations) => {
                let shouldHide = false;
                mutations.forEach((mutation) => {
                    if (mutation.addedNodes.length) {
                        shouldHide = true;
                    }
                });
                if (shouldHide) hideGuestInfo();
            });

            // Observe body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        // Listen to jQuery AJAX complete events if jQuery is present (common in WP)
        // Listen to jQuery AJAX complete events if jQuery is present (common in WP)
        // Replaced with native overrides or relying on MutationObserver above.
        // For robustness, we can monkey-patch XHR if needed, but Observer is usually enough for DOM injection.
    });

    // Run immediately (before DOM ready, just in case)
    hideGuestInfo();

})();
