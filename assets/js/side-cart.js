/**
 * Wiwa Side Cart Customizer (Vanilla JS)
 * Detects Elementor Menu Cart and injects branded empty state
 */
document.addEventListener('DOMContentLoaded', () => {
    
    // Configuration
    const config = window.wiwaSideCart || {
        homeUrl: '/tours/', // Default fallback just in case
        emptyText: '',
        emptyDesc: '',
        btnText: ''
    };

    // The branded empty cart HTML structure
    const emptyCartHTML = `
        <div class="wiwa-empty-cart-content">
            <div class="wiwa-empty-icon-wrapper">
                <!-- SVG Empty Shopping Cart -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 80px; height: 80px;">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            </div>
            <h3 class="wiwa-empty-title">${config.emptyText}</h3>
            <p class="wiwa-empty-description">${config.emptyDesc}</p>
            <div class="wiwa-empty-cta">
                <a href="${config.homeUrl}" class="wiwa-empty-cart-btn button wiwa-btn-primary">
                    ${config.btnText}
                </a>
            </div>
        </div>
    `;

    // Function to check and inject/replace layout
    const checkEmptyState = () => {
        // Find ALL containers - prevent elementor duplication by prioritizing standard WC container
        let widgetContents = document.querySelectorAll('.widget_shopping_cart_content');
        
        // If theme completely removes the .widget_shopping_cart_content, fallback to Elementor's container
        if (widgetContents.length === 0) {
            widgetContents = document.querySelectorAll('.elementor-menu-cart__container');
        }
        
        if (!widgetContents.length) return;

        widgetContents.forEach(widgetContent => {
            // List items - strict check for VISIBLE items
            const listItems = widgetContent.querySelectorAll('li.woocommerce-mini-cart-item, li.mini_cart_item, .elementor-menu-cart__product');
            const hasProducts = listItems.length > 0;

            // Select default empty messages to hide
            const emptyMessages = widgetContent.querySelectorAll('.elementor-menu-cart__empty-message, .woocommerce-mini-cart__empty-message, .total, .woocommerce-mini-cart__buttons');

            // --- VISUAL LOGIC ---
            if (!hasProducts) { 
                // 1. Hide default empty text AND any leftover standard WC elements (buttons/totals)
                emptyMessages.forEach(el => {
                    el.style.display = 'none';
                    el.classList.add('wiwa-hidden'); // Add class to force hide via CSS if needed
                });
                
                // 2. Check if our branded content is already there
                let ourContent = widgetContent.querySelector('.wiwa-empty-cart-content');
                
                if (!ourContent) {
                     // Inject Branded HTML
                     widgetContent.insertAdjacentHTML('beforeend', emptyCartHTML);
                     ourContent = widgetContent.querySelector('.wiwa-empty-cart-content');
                }
                
                // 3. Force visibility
                if (ourContent) {
                    ourContent.style.display = 'flex';
                    // Ensure parent doesn't hide it
                    widgetContent.style.display = 'block';
                    widgetContent.style.height = 'auto';
                    widgetContent.style.opacity = '1';
                    // Force parent wrapper visibility if needed
                    const wrapper = widgetContent.closest('.elementor-menu-cart__wrapper');
                    if (wrapper) wrapper.style.display = 'block';
                }

            } else {
                // --- POPULATED STATE ---
                // Remove branded empty state
                const ourMessage = widgetContent.querySelector('.wiwa-empty-cart-content');
                if (ourMessage) ourMessage.remove();
                
                // Ensure container is visible
                widgetContent.style.display = '';
                widgetContent.style.height = '';
            }
        });
    };
    
    // Polling fallback to ensure empty state persists (fixes rare race conditions)
    setInterval(checkEmptyState, 2000);

    // Initial check
    checkEmptyState();

    // Hook into WooCommerce fragments refresh and other relevant events
    if (typeof jQuery !== 'undefined') {
        // FIX: Force side-cart sync if page loaded after a cart removal
        if (window.location.search.indexOf('removed_item=') !== -1 || window.location.search.indexOf('remove_item=') !== -1) {
            try {
                var keysToRemove = [];
                for (var i = 0; i < sessionStorage.length; i++) {
                    var key = sessionStorage.key(i);
                    if (key && key.indexOf('wc_fragments') !== -1) {
                        keysToRemove.push(key);
                    }
                }
                keysToRemove.forEach(function(k) { sessionStorage.removeItem(k); });
                sessionStorage.removeItem('wc_cart_hash_' + (typeof wc_cart_fragments_params !== 'undefined' ? wc_cart_fragments_params.ajax_url : ''));
            } catch(e) {}
            
            // Wait for WC to init, then force refresh
            setTimeout(function() {
                jQuery(document.body).trigger('wc_fragment_refresh');
            }, 100);
        }

        jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded updated_wc_div removed_from_cart elementor/menu-cart/product-removed elementor/menu-cart/fragments-updated', function(e) {
            // Small delay to allow WC to finish its DOM writes
            setTimeout(checkEmptyState, 50);

            var isCart = document.body.classList.contains('woocommerce-cart');
            var isCheckout = document.body.classList.contains('woocommerce-checkout');

            // --- CROSS-SYNC LOGIC ---
            // 1. If an item was removed from the side-cart (WC native or Elementor), reload main page
            if (e.type === 'removed_from_cart' || e.type === 'elementor/menu-cart/product-removed') {
                if (isCart || isCheckout) {
                    var url = new URL(window.location.href);
                    // Add timestamp to bypass Varnish cache
                    url.searchParams.set('t', new Date().getTime());
                    window.location.href = url.toString();
                }
            }

            // 2. If the main cart page was updated (updated_wc_div), force the side-cart to refresh its fragments
            // This happens when deleting an item from the main cart table
            if (e.type === 'updated_wc_div') {
                // Remove stale sessionStorage
                try {
                    var keysToRemove = [];
                    for (var i = 0; i < sessionStorage.length; i++) {
                        var key = sessionStorage.key(i);
                        if (key && key.indexOf('wc_fragments') !== -1) {
                            keysToRemove.push(key);
                        }
                    }
                    keysToRemove.forEach(function(k) { sessionStorage.removeItem(k); });
                } catch(err) {}
                
                // Do not cause an infinite loop. We trigger fragment refresh, which shouldn't re-trigger updated_wc_div
                jQuery(document.body).trigger('wc_fragment_refresh');
            }
        });
    }

    // MutationObservers to handle dynamic updates from other scripts (e.g. Elementor)
    const widgetContents = document.querySelectorAll('.widget_shopping_cart_content');
    widgetContents.forEach(wc => {
        const observer = new MutationObserver((mutations) => {
            // Disconnect to avoid infinite loops if we modify DOM
            observer.disconnect();
            checkEmptyState();
            // Reconnect
            observer.observe(wc, { childList: true, subtree: true });
        });
        observer.observe(wc, { childList: true, subtree: true });
    });

    // Native fallback events if needed
    window.addEventListener('load', checkEmptyState);
    window.addEventListener('resize', checkEmptyState);
});
