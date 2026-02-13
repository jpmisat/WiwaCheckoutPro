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
        // We prefer the standard WC widget content, but Elementor's might be different
        const widgetContent = document.querySelector('.widget_shopping_cart_content') || document.querySelector('.elementor-menu-cart__container');
        
        if (!widgetContent) return;

        // List items - strict check for VISIBLE items
        // Sometimes WC leaves empty LIs or hidden ones
        const listItems = widgetContent.querySelectorAll('li.woocommerce-mini-cart-item, li.mini_cart_item, .elementor-menu-cart__product');
        const hasProducts = listItems.length > 0;

        // Select default empty messages to hide
        const emptyMessages = widgetContent.querySelectorAll('.elementor-menu-cart__empty-message, .woocommerce-mini-cart__empty-message, .total, .woocommerce-mini-cart__buttons');

        // --- VISUAL LOGIC ---
        if (!hasProducts) { 
            // 1. Hide default empty text AND any leftover standard WC elements (buttons/totals)
            emptyMessages.forEach(el => el.style.display = 'none');
            
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
                // Elementor sometimes hides the container if it thinks it's empty
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
            
            // Revert display of standard elements? 
            // Actually, WC fragments will re-render them fresh, so we don't need to un-hide manual hides usually,
            // but if we hid them via JS on a previous loop, we might need to reset.
            // However, since "hasProducts" is true, it means WC probably just reloaded the HTML.
            
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
