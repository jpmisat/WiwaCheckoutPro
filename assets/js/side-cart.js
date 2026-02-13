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
        
        // Elementor often renders a list (ul) for items.
        // It might be empty, OR it might simply not exist if the cart is fully empty in some themes.
        const productList = document.querySelector('.elementor-menu-cart__products') 
                         || document.querySelector('.woocommerce-mini-cart');
        
        // Select message elements to hide
        const emptyMessages = document.querySelectorAll('.elementor-menu-cart__empty-message, .woocommerce-mini-cart__empty-message');

        if (!widgetContent) return;

        // Determine if cart has items.
        // Logic:
        // 1. If productList exists and has children (LI items), it's NOT empty.
        // 2. If productList exists but has no children, it IS empty.
        // 3. If productList does NOT exist, use WC cookie or other signal? 
        //    Actually, Mini-Cart template usually outputs the <p>empty</p> if empty, so list might be missing.
        
        let hasProducts = false;
        
        // Check for actual list items
        // Check for actual list items
        const listItems = document.querySelectorAll('.woocommerce-mini-cart-item, .elementor-menu-cart__product');
        
        // Also check if the generic list container is effectively empty (contains only text nodes or whitespace)
        // because sometimes WC outputs <ul class="... mini-cart"></ul> without items.
        // If listItems found, definitely not empty.
        if (listItems.length > 0) {
            hasProducts = true;
        } else if (productList && productList.children.length > 0) {
             // Fallback: if there are children but not matched by our selector?
             // Usually implies not empty, but let's be strict.
             // If children are just <p>empty</p>, then it IS empty.
             const emptyP = productList.querySelector('.woocommerce-mini-cart__empty-message, .elementor-menu-cart__empty-message');
             if (!emptyP) {
                 hasProducts = true;
             }
        }

        // --- EMPTY STATE ---
        if (!hasProducts) { 
            
            // Hide default text
            emptyMessages.forEach(el => {
                el.style.display = 'none';
                el.style.setProperty('display', 'none', 'important');
            });
            
            // Inject our branded content if missing
            if (!document.querySelector('.wiwa-empty-cart-content')) {
                // Clear out potential residual text nodes (often "No products in cart" raw text)
                // specific to how some widgets render
                 // Note: we append, we don't clear innerHTML to avoid breaking hidden inputs if any
                widgetContent.insertAdjacentHTML('beforeend', emptyCartHTML);
            }
            
            // Double check visibility of our content
            const ours = document.querySelector('.wiwa-empty-cart-content');
            if (ours) ours.style.display = 'flex';

        } else {
            // --- POPULATED STATE ---
            
            // Remove branded empty state
            const ourMessage = document.querySelector('.wiwa-empty-cart-content');
            if (ourMessage) {
                ourMessage.remove();
            }
            
            // Ensure default empty messages stay hidden (just in case)
            emptyMessages.forEach(el => {
                el.style.display = 'none';
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
        jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded updated_wc_div', function() {
            // Small delay to allow DOM to settle
            setTimeout(checkEmptyState, 50);
        });
    }

    // Also run on window resize/load to be safe
    window.addEventListener('load', checkEmptyState);
});
