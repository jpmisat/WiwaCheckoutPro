/**
 * Wiwa Tour Frontend Script
 * Hides the guest info form in Tour Booking popups
 * This is needed because Tour Booking uses inline styles that override CSS
 */
(function ($) {
    'use strict';

    // Function to hide guest info elements (handles inline styles)
    function hideGuestInfo() {
        // Target specific classes used by Tour Booking
        var selectors = [
            '.ovatb-guest-info',
            '.guest-info-heading',
            '.guest-info-accordion',
            '.guest-info-item',
            '.guest-info-body',
            '.guest-info-field'
        ];

        selectors.forEach(function (selector) {
            $(selector).each(function () {
                // Force hide by setting inline style
                $(this).attr('style', 'display: none !important; visibility: hidden !important; height: 0 !important; max-height: 0 !important; overflow: hidden !important; opacity: 0 !important;');
            });
        });
    }

    // Run on document ready
    $(document).ready(function () {
        hideGuestInfo();

        // Also run when popup opens (Tour Booking uses AJAX)
        $(document).on('click', '.ovatb-btn-booking, .ova-booking-btn, .book-now-btn, [data-toggle="modal"]', function () {
            setTimeout(hideGuestInfo, 100);
            setTimeout(hideGuestInfo, 300);
            setTimeout(hideGuestInfo, 500);
            setTimeout(hideGuestInfo, 1000);
        });

        // MutationObserver to catch dynamically added elements
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.addedNodes.length) {
                        hideGuestInfo();
                    }
                });
            });

            // Observe body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        // Also run on any AJAX complete
        $(document).ajaxComplete(function () {
            hideGuestInfo();
        });
    });

    // Run immediately (before DOM ready)
    hideGuestInfo();

})(jQuery);
