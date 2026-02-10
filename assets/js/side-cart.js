/**
 * Wiwa Side Cart Customizer
 * Detects Elementor Menu Cart and injects branded empty state
 */
jQuery(document).ready(function($) {
    
    // Configuration
    const config = wiwaSideCart || {
        homeUrl: '/',
        emptyText: 'Tu carrito está vacío',
        emptyDesc: 'Parece que aún no has agregado ningún tour.',
        btnText: 'Explorar Tours'
    };

    // The branded empty cart HTML structure (Compact version)
    const emptyCartHTML = `
        <div class="wiwa-side-cart-empty">
            <div class="wiwa-empty-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5">
                    <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
            </div>
            <h3 class="wiwa-empty-title">${config.emptyText}</h3>
            <p class="wiwa-empty-desc">${config.emptyDesc}</p>
            <a href="${config.homeUrl}" class="wiwa-btn-primary">
                ${config.btnText}
            </a>
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
        const $widgetContent = $('.widget_shopping_cart_content');
        const $productList = $('.elementor-menu-cart__products');
        const $emptyMessage = $('.elementor-menu-cart__empty-message'); // Default Elementor empty msg

        // Check if cart is empty
        // Elementor usually shows a specific class or message when empty
        // Or if the product list is empty/hidden
        
        const hasProducts = $productList.children().length > 0;
        const isHidden = $productList.is(':hidden'); // Sometimes Elementor hides it

        if (!hasProducts || $emptyMessage.length > 0) { // It is empty
            
            // Hide default message if visible
            if ($emptyMessage.length) {
                $emptyMessage.hide();
            }

            // Check if our branded message already exists
            if ($('.wiwa-side-cart-empty').length === 0) {
                $widgetContent.append(emptyCartHTML);
            }
        } else {
            // Use has products, remove our message if present
            $('.wiwa-side-cart-empty').remove();
            if ($emptyMessage.length) {
                $emptyMessage.hide(); // Keep default hidden just in case
            }
        }
    };

    // Start observing
    observeCart();

    // Hook into WooCommerce fragments refresh (when adding to cart via AJAX)
    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
        setTimeout(checkEmptyState, 100);
    });
});
