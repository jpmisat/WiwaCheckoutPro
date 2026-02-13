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
            // Force inject even if widgetContent looks empty/cleared by WC
            if (!document.querySelector('.wiwa-empty-cart-content')) {
                // Clear out potential residual text nodes (often "No products in cart" raw text)
                // specific to how some widgets render. Be aggressive if it looks like just text.
                 // Note: we append, we don't clear innerHTML completely to avoid breaking hidden inputs if any
                 // BUT if it's acting weird, we might need to check children.
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

    // Initial check
    checkEmptyState();

    // Hook into WooCommerce fragments refresh and other relevant events
    // We remove the MutationObserver here to prevents infinite loops if our DOM changes trigger it.
    // WC events are the standard and safe way to react to cart changes.
    if (typeof jQuery !== 'undefined') {
        jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded updated_wc_div removed_from_cart', function() {
            // Small delay to allow WC to finish its DOM writes
            setTimeout(checkEmptyState, 50);
        });
    }

    // Native fallback events if needed (though WC mostly uses jQuery events)
    window.addEventListener('load', checkEmptyState);
    window.addEventListener('resize', checkEmptyState);
});
