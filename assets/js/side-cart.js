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
        // Select specific Elementor empty message class and generic WooCommerce one
        const $emptyMessage = $('.elementor-menu-cart__empty-message, .woocommerce-mini-cart__empty-message'); 

        // Check if cart is empty
        const hasProducts = $productList.children().length > 0 && !$productList.is(':empty');
        const isHidden = $productList.is(':hidden');

        if (!hasProducts) { // It is empty
            
            // Aggressively hide default messages
            $emptyMessage.css('display', 'none !important').hide();
            
            // Check if our branded message already exists
            if ($('.wiwa-side-cart-empty').length === 0) {
                // Ensure we append to a visible container
                $widgetContent.append(emptyCartHTML);
            }
        } else {
            // Has products, remove our message
            $('.wiwa-side-cart-empty').remove();
            
            // Don't necessarily show $emptyMessage, logic handles itself usually
            $emptyMessage.css('display', 'none !important').hide(); 
        }
    };

    // Update config to use explicit URL
    config.homeUrl = '/tours/'; 

    // Start observing
    observeCart();

    // Hook into WooCommerce fragments refresh
    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
        setTimeout(checkEmptyState, 100);
    });
});
