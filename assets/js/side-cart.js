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
        jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded updated_wc_div removed_from_cart', function() {
            // Small delay to allow WC to finish its DOM writes
            setTimeout(checkEmptyState, 50);
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
