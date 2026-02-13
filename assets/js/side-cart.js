/**
 * Wiwa Side Cart Customizer (Vanilla JS)
 * Detects Elementor Menu Cart and injects branded empty state
 */
document.addEventListener('DOMContentLoaded', () => {
    
    // Configuration
    const config = window.wiwaSideCart || {
        homeUrl: '/',
        emptyText: 'Tu carrito está vacío',
        emptyDesc: 'Parece que aún no has agregado ningún tour.',
        btnText: 'Explorar Tours'
    };

    // The branded empty cart HTML structure
    const emptyCartHTML = `
        <div class="wiwa-empty-cart-content">
            <div class="wiwa-empty-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5">
                    <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
            </div>
            <h3 class="wiwa-empty-title">${config.emptyText}</h3>
            <p class="wiwa-empty-description">${config.emptyDesc}</p>
            <div class="wiwa-empty-cta">
                <a href="${config.homeUrl}" class="wiwa-empty-cart-btn">
                    ${config.btnText}
                </a>
            </div>
        </div>
    `;

    // Observer to detect when the cart is opened or updated
    const observeCart = () => {
        const cartContainer = document.querySelector('.elementor-menu-cart__container');
        
        if (!cartContainer) {
            // Retry if not loaded yet (Elementor sometimes loads late)
            setTimeout(observeCart, 1000);
            return;
        }

        const observer = new MutationObserver((mutations) => {
            checkEmptyState();
        });

        observer.observe(cartContainer, {
            childList: true,
            subtree: true,
            attributes: true // Watch for class changes (showing/hiding)
        });
        
        // Initial check
        checkEmptyState();
    };

    // Function to check and inject/replace layout
    const checkEmptyState = () => {
        const widgetContent = document.querySelector('.widget_shopping_cart_content');
        const productList = document.querySelector('.elementor-menu-cart__products');
        
        // Select specific Elementor empty message class and generic WooCommerce one
        const emptyMessages = document.querySelectorAll('.elementor-menu-cart__empty-message, .woocommerce-mini-cart__empty-message');

        if (!productList || !widgetContent) return;

        // Check if cart is empty (no children or empty text)
        const hasProducts = productList.children.length > 0 && productList.innerText.trim() !== '';

        if (!hasProducts) { // It is empty
            
            // Aggressively hide default messages
            emptyMessages.forEach(el => {
                el.style.display = 'none';
                el.style.setProperty('display', 'none', 'important');
            });
            
            // Check if our branded message already exists
            if (!document.querySelector('.wiwa-empty-cart-content')) {
                // Ensure we append to a visible container
                widgetContent.insertAdjacentHTML('beforeend', emptyCartHTML);
            }
        } else {
            // Has products, remove our message
            const ourMessage = document.querySelector('.wiwa-empty-cart-content');
            if (ourMessage) {
                ourMessage.remove();
            }
            
            // Hide default messages (logic usually handles this, but we ensure it)
            emptyMessages.forEach(el => {
                el.style.display = 'none';
                el.style.setProperty('display', 'none', 'important');
            });
        }
    };

    // Start observing
    observeCart();

    // Hook into WooCommerce fragments refresh
    // Note: WC triggers events on document.body using jQuery. 
    // We can listen to it via jQuery if available, or try native custom event integration.
    // For safety with WC, we'll use a lightweight jQuery listener if available, otherwise fallback.
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
            setTimeout(checkEmptyState, 100);
        });
    }
});
