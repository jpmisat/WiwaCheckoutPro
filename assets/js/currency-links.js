/**
 * Wiwa Currency Link Propagator
 *
 * Ensures the ?currency=XXX parameter is appended to ALL internal links
 * when a non-default currency is active. This fixes the issue where
 * FOX Currency Switcher redraws prices via AJAX but does NOT update
 * the href attributes on tour cards, sliders, menus, etc.
 *
 * Without this fix, clicking a tour card after switching to EUR sends
 * the user to /tours/tour-x/ (no currency param), causing Varnish
 * to serve a cached COP version.
 *
 * @since 2.18.5
 */
(function () {
    'use strict';

    var DEFAULT_CURRENCY = (typeof wiwaCurrencyLinks !== 'undefined' && wiwaCurrencyLinks.defaultCurrency)
        ? wiwaCurrencyLinks.defaultCurrency
        : 'COP';

    var SITE_HOST = window.location.hostname;

    /**
     * Get the currently active currency from multiple sources (in priority order).
     * @returns {string|null} Currency code or null if default
     */
    function getActiveCurrency() {
        var currency = null;

        // 1. Check URL query string first
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('currency') && urlParams.get('currency')) {
            currency = urlParams.get('currency');
        }

        // 2. Check FOX JS global (woocs_current_currency)
        if (!currency && typeof woocs_current_currency !== 'undefined' && woocs_current_currency) {
            // woocs_current_currency is an object like {name:"USD", rate:1, ...}
            var foxCurrency = (typeof woocs_current_currency === 'object')
                ? woocs_current_currency.name
                : woocs_current_currency;
            if (foxCurrency) {
                currency = foxCurrency;
            }
        }

        // 3. Check cookie
        if (!currency) {
            var match = document.cookie.match(/(?:^|;\s*)woocs_current_currency=([^;]+)/);
            if (match && match[1]) {
                currency = decodeURIComponent(match[1]);
            }
        }

        // Return null if default currency (no need to append)
        if (!currency || currency === DEFAULT_CURRENCY) {
            return null;
        }

        return currency;
    }

    /**
     * Check if a URL is internal (same domain).
     * @param {string} href
     * @returns {boolean}
     */
    function isInternalLink(href) {
        if (!href) return false;

        // Exclude anchors, javascript:, mailto:, tel:, blob:, data:
        if (/^(#|javascript:|mailto:|tel:|blob:|data:)/.test(href)) return false;

        // Exclude admin, login, and AJAX URLs
        if (/\/(wp-admin|wp-login|admin-ajax\.php|wc-ajax)/.test(href)) return false;

        // Relative URLs are always internal
        if (href.charAt(0) === '/') return true;

        // Check if same domain
        try {
            var url = new URL(href, window.location.origin);
            return url.hostname === SITE_HOST;
        } catch (e) {
            return false;
        }
    }

    /**
     * Add or update the ?currency= parameter on a URL string.
     * @param {string} href - Original URL
     * @param {string} currency - Currency code (e.g., "USD")
     * @returns {string} Updated URL
     */
    function addCurrencyParam(href, currency) {
        try {
            var url = new URL(href, window.location.origin);

            // Don't override if already has the correct currency
            if (url.searchParams.get('currency') === currency) {
                return href;
            }

            url.searchParams.set('currency', currency);
            
            // Return relative path for relative URLs, full URL for absolute
            if (href.charAt(0) === '/') {
                return url.pathname + url.search + url.hash;
            }
            return url.toString();
        } catch (e) {
            // Fallback: simple string append
            var separator = href.indexOf('?') !== -1 ? '&' : '?';
            return href + separator + 'currency=' + currency;
        }
    }

    /**
     * Remove the ?currency= parameter from a URL string.
     * @param {string} href
     * @returns {string}
     */
    function removeCurrencyParam(href) {
        try {
            var url = new URL(href, window.location.origin);
            if (!url.searchParams.has('currency')) return href;
            
            url.searchParams.delete('currency');
            
            if (href.charAt(0) === '/') {
                var search = url.search; // Will be '' if no other params
                return url.pathname + search + url.hash;
            }
            return url.toString();
        } catch (e) {
            return href;
        }
    }

    /**
     * Process all internal links on the page.
     * If non-default currency → add ?currency=XXX
     * If default currency → remove ?currency= if present
     */
    function processAllLinks() {
        var currency = getActiveCurrency();
        var links = document.querySelectorAll('a[href]');

        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            var href = link.getAttribute('href');

            if (!isInternalLink(href)) continue;

            // Skip links that are currency switcher elements
            if (link.closest('.woocs_auto_switcher, .woocs_converter_shortcode, .woocs-switcher, [data-currency]')) {
                continue;
            }

            if (currency) {
                link.setAttribute('href', addCurrencyParam(href, currency));
            } else {
                link.setAttribute('href', removeCurrencyParam(href));
            }
        }
    }

    /**
     * Process a single link or container of links.
     * @param {Element} root
     */
    function processLinksIn(root) {
        if (!root || !root.querySelectorAll) return;

        var currency = getActiveCurrency();
        var links = root.querySelectorAll ? root.querySelectorAll('a[href]') : [];

        // Also process root if it's an <a>
        if (root.tagName === 'A' && root.getAttribute('href')) {
            links = [root];
        }

        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            var href = link.getAttribute('href');

            if (!isInternalLink(href)) continue;
            if (link.closest('.woocs_auto_switcher, .woocs_converter_shortcode, .woocs-switcher')) continue;

            if (currency) {
                link.setAttribute('href', addCurrencyParam(href, currency));
            } else {
                link.setAttribute('href', removeCurrencyParam(href));
            }
        }
    }

    // -----------------------------------------------------------------
    // Initialization
    // -----------------------------------------------------------------

    /**
     * Initialize: process links on DOM ready and set up observers.
     */
    function init() {
        // 1. Process all existing links
        processAllLinks();

        // 2. Watch for dynamically added content (sliders, AJAX-loaded content)
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var mutation = mutations[i];
                    if (mutation.type === 'childList' && mutation.addedNodes.length) {
                        for (var j = 0; j < mutation.addedNodes.length; j++) {
                            var node = mutation.addedNodes[j];
                            if (node.nodeType === 1) { // ELEMENT_NODE
                                processLinksIn(node);
                            }
                        }
                    }
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        // 3. Listen for FOX currency switch events
        // FOX triggers 'woocs_currency_changed' via jQuery
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).on('woocs_currency_changed', function () {
                // FOX updates its global after this event fires
                setTimeout(processAllLinks, 300);
            });

            // Also listen for the FOX AJAX complete redraw
            jQuery(document).ajaxComplete(function (event, xhr, settings) {
                if (settings && settings.data && typeof settings.data === 'string') {
                    if (settings.data.indexOf('woocs_special_ajax_price') !== -1 ||
                        settings.data.indexOf('woocs_') !== -1) {
                        setTimeout(processAllLinks, 500);
                    }
                }
            });
        }

        // 4. Re-process on browser back/forward navigation
        window.addEventListener('popstate', function () {
            setTimeout(processAllLinks, 200);
        });

        // 5. Intercept clicks as final safety net
        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[href]');
            if (!link) return;

            var href = link.getAttribute('href');
            if (!isInternalLink(href)) return;
            if (link.closest('.woocs_auto_switcher, .woocs_converter_shortcode, .woocs-switcher')) return;

            var currency = getActiveCurrency();
            if (currency) {
                var updatedHref = addCurrencyParam(href, currency);
                if (updatedHref !== href) {
                    link.setAttribute('href', updatedHref);
                    // Don't prevent default — let the browser navigate with the updated href
                }
            }
        }, true); // Use capture phase to run before other handlers
    }

    // -----------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
