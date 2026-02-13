/**
 * Wiwa Side Cart Customizer (Vanilla JS)
 * Detects Elementor Menu Cart and injects branded empty state
 */
document.addEventListener('DOMContentLoaded', () => {
    
    // Configuration
    const config = window.wiwaSideCart || {
        homeUrl: '/tours', // Direct link to tours page
        emptyText: 'Tu carrito está vacío',
        emptyDesc: 'Parece que aún no has agregado ningún tour.',
        btnText: 'Explorar Tours'
    };

    // The branded empty cart HTML structure
    const emptyCartHTML = `
        <div class="wiwa-empty-cart-content">
            <div class="wiwa-empty-icon-wrapper">
                <span class="material-symbols-rounded" style="font-size: 64px; color: #d1d5db;">shopping_cart</span>
            </div>
            <h3 class="wiwa-empty-title">${config.emptyText}</h3>
            <p class="wiwa-empty-description">${config.emptyDesc}</p>
            <div class="wiwa-empty-cta">
                <a href="${config.homeUrl}" class="wiwa-empty-cart-btn">
                    ${config.btnText}
                </a>
            </div>
            <div class="wiwa-empty-decorations">
                 <!-- Simple decorative icons or keep them as background via CSS if preferred, 
                      but user asked for 'icons below'. We'll add a small row of them. -->
                 <span class="material-symbols-rounded" style="font-size: 24px; color: #e5e7eb;">palm_tree</span>
                 <span class="material-symbols-rounded" style="font-size: 24px; color: #e5e7eb;">backpack</span>
                 <span class="material-symbols-rounded" style="font-size: 24px; color: #e5e7eb;">camera_alt</span>
            </div>
        </div>
    `;

    // Function to check and inject/replace layout
    const checkEmptyState = () => {
        // Find the container - support multiple common selectors
        const widgetContent = document.querySelector('.widget_shopping_cart_content') || document.querySelector('.elementor-menu-cart__container');
        
        if (!widgetContent) {
            // console.warn('[Wiwa] Cart container not found.');
            return;
        }

        // List items
        const listItems = document.querySelectorAll('.woocommerce-mini-cart-item, .elementor-menu-cart__product');
        let hasProducts = listItems.length > 0;

        // Select default empty messages to hide
        const emptyMessages = document.querySelectorAll('.elementor-menu-cart__empty-message, .woocommerce-mini-cart__empty-message, .woocommerce-mini-cart__empty-message');

        // --- VISUAL LOGIC ---
        if (!hasProducts) { 
            // 1. Hide default empty text
            emptyMessages.forEach(el => el.style.display = 'none');
            
            // 2. Check if our branded content is already there
            let ourContent = document.querySelector('.wiwa-empty-cart-content');
            
            if (!ourContent) {
                 // Inject Branded HTML
                 widgetContent.insertAdjacentHTML('beforeend', emptyCartHTML);
                 ourContent = document.querySelector('.wiwa-empty-cart-content');
            }
            
            // 3. Force visibility
            if (ourContent) {
                ourContent.style.display = 'flex';
                // Ensure parent doesn't hide it
                widgetContent.style.display = 'block';
                widgetContent.style.opacity = '1';
                widgetContent.style.height = 'auto';
            }

        } else {
            // --- POPULATED STATE ---
            // Remove branded empty state
            const ourMessage = document.querySelector('.wiwa-empty-cart-content');
            if (ourMessage) ourMessage.remove();
            
            // Ensure container is visible
            widgetContent.style.display = '';
            widgetContent.style.height = '';
        }
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

    // MutationObserver to handle dynamic updates from other scripts (e.g. Elementor)
    const widgetContent = document.querySelector('.widget_shopping_cart_content');
    if (widgetContent) {
        const observer = new MutationObserver((mutations) => {
            // Disconnect to avoid infinite loops if we modify DOM
            observer.disconnect();
            checkEmptyState();
            // Reconnect
            observer.observe(widgetContent, { childList: true, subtree: true });
        });
        
        observer.observe(widgetContent, { childList: true, subtree: true });
    }

    // Native fallback events if needed (though WC mostly uses jQuery events)
    window.addEventListener('load', checkEmptyState);
    window.addEventListener('resize', checkEmptyState);
});
