<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Cart_Handler
{

    public function __construct()
    {
        // Override empty cart and main cart templates with highest possible priority
        add_filter('wc_get_template', [$this, 'override_cart_templates'], PHP_INT_MAX, 5);
        add_filter('woocommerce_locate_template', [$this, 'override_cart_templates'], PHP_INT_MAX, 3);
        
        // Enqueue side cart script
        add_action('wp_enqueue_scripts', [$this, 'enqueue_side_cart_script']);
        
        // Handle custom cart updates (Pax & Guest Info)
        add_action('woocommerce_update_cart_action_cart_updated', [$this, 'handle_custom_cart_updates']);
        
        // Critial CSS injection
        add_action('wp_head', [$this, 'print_critical_css'], 999);

        // EMERGENCY FIX: Sanitize Cart Session on Load to remove bad data causing warnings
        add_action('wp_loaded', [$this, 'sanitize_cart_session_data']);

        // EMERGENCY FIX: Force cart items to be visible across translations (Multi-language Fix)
        // Solves the empty cart on translated pages bug.
        add_filter('woocommerce_cart_item_visible', '__return_true', 9999);
        add_filter('woocommerce_widget_cart_item_visible', '__return_true', 9999);

        // Custom Quantity Inputs
        add_filter('woocommerce_widget_cart_item_quantity', [$this, 'custom_mini_cart_item_quantity'], 10, 3);
        add_filter('woocommerce_cart_item_quantity', [$this, 'custom_cart_item_quantity'], 10, 3);

        // Metadata Cleanup (Display)
        add_filter('woocommerce_get_item_data', [$this, 'clean_cart_item_data'], 10, 2);

        // Varnish / CloudPanel: Prevent caching of WC cart fragments
        add_action('wc_ajax_get_refreshed_fragments', [$this, 'set_nocache_headers'], 1);
        add_action('wp_ajax_woocommerce_get_refreshed_fragments', [$this, 'set_nocache_headers'], 1);
        add_action('wp_ajax_nopriv_woocommerce_get_refreshed_fragments', [$this, 'set_nocache_headers'], 1);

        // Metadata Persistence (Add to Cart)
        add_filter('woocommerce_add_cart_item_data', [$this, 'aggregate_guest_info_for_cart'], 10, 3);

        // Varnish / CloudPanel: Prevent cache issues on language switch
        add_action('wp_footer', [$this, 'prevent_varnish_cache_on_lang_switch'], 99);
    }


    /**
     * Prevent Varnish / CloudPanel from caching WC cart fragment AJAX responses.
     * Ensures the side cart always shows fresh data.
     */
    public function set_nocache_headers() {
        nocache_headers();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Vary: Cookie');
    }

    /**
     * Emergency Sanitize: Fix malformed ovatb_guest_info in session
     * Refactored to be granular: Clean specific corrupt entries instead of nuking everything.
     */
    public function sanitize_cart_session_data() {
        if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        // Access raw cart contents to modify in place
        $cart_contents = WC()->cart->get_cart_contents();
        $changes = false;

        foreach ( $cart_contents as $key => $item ) {
            if ( ! isset( $item['ovatb_guest_info'] ) || ! is_array( $item['ovatb_guest_info'] ) ) {
                continue;
            }

            $guest_info = $item['ovatb_guest_info'];
            $item_changed = false;

            foreach ( $guest_info as $guest_type => $guest_data ) {
                // Level 1: Guest Types (adults, children) - should be array
                if ( ! is_array( $guest_data ) ) {
                    // FIX: If it's not array, it's definitely broken. Remove it.
                    unset( $guest_info[$guest_type] );
                    $item_changed = true;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "Wiwa Checkout: Removed corrupt guest_type '$guest_type' (not array) from cart item $key" );
                    }
                    continue;
                }

                // Level 2: List of guests - should be array of arrays
                foreach ( $guest_data as $index => $info_fields ) {
                    if ( ! is_array( $info_fields ) ) {
                         // This is the error: foreach() on string
                         // Found a string where an array of fields should be
                         unset( $guest_info[$guest_type][$index] );
                         $item_changed = true;
                         if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( "Wiwa Checkout: Removed corrupt guest index '$index' in '$guest_type' (not array) from cart item $key" );
                         }
                    }
                }
            }

            if ( $item_changed ) {
                WC()->cart->cart_contents[$key]['ovatb_guest_info'] = $guest_info;
                $changes = true;
            }
        }

        if ( $changes ) {
            WC()->cart->set_session();
        }
    }

    /**
     * Process custom updates (Pax counts + Guest Info) from the cart page
     */
    public function handle_custom_cart_updates($cart_updated)
    {
        if (empty($_POST['cart']) || !is_array($_POST['cart'])) {
            return;
        }

        $cart = WC()->cart->get_cart();
        $changes_made = false;

        foreach ($_POST['cart'] as $cart_item_key => $values) {
            if (!isset($cart[$cart_item_key])) continue;

            $item_changed = false;

            // 1. Handle Pax Counts (numberof_*)
            if (isset($values['guests']) && is_array($values['guests'])) {
                $total_guests = 0;
                
                foreach ($values['guests'] as $guest_name => $qty) {
                    $qty = max(0, intval($qty)); // Sanitize
                    
                    // Update specific guest count
                    if (!isset($cart[$cart_item_key]['numberof_' . $guest_name]) || $cart[$cart_item_key]['numberof_' . $guest_name] != $qty) {
                         $cart[$cart_item_key]['numberof_' . $guest_name] = $qty;
                         $item_changed = true;
                    }
                    
                    $total_guests += $qty;
                }

                if ($item_changed) {
                    $cart[$cart_item_key]['numberof_guests'] = $total_guests;
                }
            }

            // 2. Handle Guest Info (ovatb_guest_info)
            if (isset($values['ovatb_guest_info'])) {
                $guest_info = $values['ovatb_guest_info'];
                
                // STRICT VALIDATION: Only save if structure is correct
                // Expect: [ type => [ index => [ field => value ] ] ]
                if (is_array($guest_info)) {
                    $valid = true;
                    foreach ($guest_info as $g_data) {
                        if (!is_array($g_data)) { $valid = false; break; }
                        foreach ($g_data as $fields) {
                            if (!is_array($fields)) { $valid = false; break; }
                        }
                    }

                    if ($valid) {
                        $cart[$cart_item_key]['ovatb_guest_info'] = $guest_info;
                        $item_changed = true;
                    }
                }
            }

            if ($item_changed) {
                WC()->cart->cart_contents[$cart_item_key] = $cart[$cart_item_key];
                $changes_made = true;
            }
        }
        
        if ($changes_made) {
            WC()->cart->set_session();
        }
    }

    /**
     * Override default WooCommerce cart templates
     */
    public function override_cart_templates($template, $template_name, $template_path)
    {
        // Custom template path
        $custom_cart = WIWA_CHECKOUT_PATH . 'templates/cart/cart.php';
        $custom_empty = WIWA_CHECKOUT_PATH . 'templates/cart/empty-cart.php';

        if ($template_name === 'cart/cart.php' && file_exists($custom_cart)) {
            return $custom_cart;
        }

        if ($template_name === 'cart/cart-empty.php' && file_exists($custom_empty)) {
            return $custom_empty;
        }

        // New Mini Cart Override
        $custom_mini = WIWA_CHECKOUT_PATH . 'templates/cart/mini-cart.php';
        if ($template_name === 'cart/mini-cart.php' && file_exists($custom_mini)) {
            return $custom_mini;
        }

        return $template;
    }

    /**
     * Enqueue script for Elementor Side Cart customization
     */
    public function enqueue_side_cart_script()
    {
        if (is_admin()) return;

        wp_enqueue_script(
            'wiwa-side-cart',
            WIWA_CHECKOUT_URL . 'assets/js/side-cart.js',
            ['jquery'],
            WIWA_CHECKOUT_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('wiwa-side-cart', 'wiwaSideCart', [
            'homeUrl' => esc_url(home_url('/tours/')),
            'emptyText' => __('Tu carrito está vacío', 'wiwa-checkout'),
            'emptyDesc' => __('Parece que aún no has agregado ningún tour. ¡Explora nuestros destinos!', 'wiwa-checkout'),
            'btnText' => __('Explorar Tours', 'wiwa-checkout'),
            'iconUrl' => WIWA_CHECKOUT_URL . 'assets/images/empty-cart.svg'
        ]);
        
        // Enqueue Persistence Script (for Main Cart)
        if (is_cart()) {
            wp_enqueue_script(
                'wiwa-cart-persistence',
                WIWA_CHECKOUT_URL . 'assets/js/cart-persistence.js',
                ['jquery'],
                WIWA_CHECKOUT_VERSION,
                true
            );
        }
        
        // Enqueue styles for side cart specific tweaks
        wp_enqueue_style(
            'wiwa-side-cart-css', 
            WIWA_CHECKOUT_URL . 'assets/css/checkout.css', 
            [], 
            WIWA_CHECKOUT_VERSION
        );

        // FIX: Start - Move cart container AND Create CUSTOM OVERLAY to fix stacking context & click issues
        wp_add_inline_script('wiwa-side-cart', "
            jQuery(document).ready(function($) {
                var cartContainer = $('.elementor-menu-cart__container');
                var body = $('body');
                var customOverlay = $('<div class=\"wiwa-custom-cart-overlay\"></div>');
                
                // 1. Move Container to body
                if (cartContainer.length && cartContainer.parent()[0] !== document.body) {
                    cartContainer.appendTo('body');
                }

                // 2. Add Custom Overlay to Body (if not exists)
                if ($('.wiwa-custom-cart-overlay').length === 0) {
                    body.append(customOverlay);
                } else {
                    customOverlay = $('.wiwa-custom-cart-overlay');
                }

                function openCart() {
                    body.addClass('elementor-menu-cart--shown');
                    cartContainer.addClass('wiwa-cart-open');
                    customOverlay.addClass('wiwa-cart-open');
                }

                function closeCart() {
                    body.removeClass('elementor-menu-cart--shown');
                    cartContainer.removeClass('wiwa-cart-open');
                    customOverlay.removeClass('wiwa-cart-open');
                }

                // 3. Toggle Logic
                // Open
                $(document).on('click', '.elementor-menu-cart__toggle_button, .elementor-menu-cart__toggle_wrapper, .e-menu-cart-toggle-button', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openCart();
                });

                // Close (Button)
                $(document).on('click', '.elementor-menu-cart__close-button, .elementor-menu-cart__close-button-custom', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeCart();
                });

                // 4. ROBUST CLOSING: Click Outside
                // Listen for any click on the document
                $(document).on('click', function(e) {
                    // Only proceed if cart is actually open
                    if (!body.hasClass('elementor-menu-cart--shown')) return;

                    var target = $(e.target);

                    // Check if click is inside the main cart panel (the white part)
                    var isInsidePanel = target.closest('.elementor-menu-cart__main').length > 0;
                    
                    // Check if click is on a toggle button (to prevent conflict)
                    var isToggle = target.closest('.elementor-menu-cart__toggle_button').length > 0 
                                || target.closest('.elementor-menu-cart__toggle_wrapper').length > 0
                                || target.closest('.e-menu-cart-toggle-button').length > 0;

                    // If click is NOT inside panel and NOT on a toggle -> Close it
                    if (!isInsidePanel && !isToggle) {
                        // e.preventDefault(); // Don't prevent default, might be clicking a link behind (though usually overlay blocks it)
                        closeCart();
                    }
                });
                
                // ESC key
                $(document).keyup(function(e) {
                    if (e.key === 'Escape') {
                        closeCart();
                    }
                });

                // Ensure closed on load
                closeCart();
            });
        ");
        // FIX: End

    }

    /**
     * Force critical CSS into head to avoid caching/enqueue issues
     */
    public function print_critical_css() {
        if (is_admin()) return;
        ?>
        <style id="wiwa-critical-css">
            /* CRITICAL: Side Cart Z-Index */
            /* CRITICAL: Side Cart Z-Index & Visibility */
            
            /* 1. CONTAINER (The Panel) */
            body .elementor-menu-cart__container {
                z-index: 2147483647 !important;
                position: fixed !important;
                right: 0 !important;
                top: 0 !important;
                height: 100vh !important;
                transform: translateX(100%) !important; /* Hidden by default */
                transition: transform 0.3s ease-in-out !important;
                display: block !important;
            }

            /* Show Panel */
            body.elementor-menu-cart--shown .elementor-menu-cart__container,
            body .elementor-menu-cart__container.wiwa-cart-open {
                transform: translateX(0) !important;
            }

            /* 2. OVERLAY (The Custom Dark Background) */
            .wiwa-custom-cart-overlay {
                z-index: 2147483646 !important; /* Below panel */
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                background-color: rgba(0,0,0,0.5) !important; /* Ensure dark dimming */
                display: none !important; /* Hidden by default */
                opacity: 0 !important;
                transition: opacity 0.2s ease-in-out !important;
                cursor: pointer !important; /* Show pointer to indicate clickable */
            }

            /* Show Overlay */
            body.elementor-menu-cart--shown .wiwa-custom-cart-overlay,
            body .wiwa-custom-cart-overlay.wiwa-cart-open {
                display: block !important;
                opacity: 1 !important;
            }
            
            /* Wrapper (Button) - Not fixed */
            body .elementor-menu-cart__wrapper {
                 z-index: 2147483640 !important; 
            }

            /* CRITICAL: WooCommerce Notices */
            .woocommerce-notices-wrapper {
                margin-top: 20px;
                margin-bottom: 20px;
            }
            
            .woocommerce-notices-wrapper .woocommerce-message, 
            .woocommerce-notices-wrapper .woocommerce-info, 
            .woocommerce-notices-wrapper .woocommerce-error {
                background-color: #ffffff !important;
                color: #374151 !important;
                border: 0 !important;
                border-left: 4px solid #1E3A2B !important; /* Wiwa Brand */
                border-radius: 8px !important;
                padding: 16px 24px !important;
                margin-bottom: 20px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
                font-family: 'Poppins', sans-serif !important;
                font-size: 14px !important;
                line-height: 1.5 !important;
                position: relative !important;
            }

            /* Error State */
            .woocommerce-notices-wrapper .woocommerce-error {
                border-left-color: #DC2626 !important; /* Red 600 */
                background-color: #FEF2F2 !important; /* Red 50 */
                color: #991B1B !important; /* Red 800 */
            }

            /* Info State */
            .woocommerce-notices-wrapper .woocommerce-info {
                border-left-color: #3B82F6 !important; /* Blue 500 */
                background-color: #EFF6FF !important; /* Blue 50 */
                color: #1E40AF !important; /* Blue 800 */
            }

            /* Message State (Success/Status) */
            .woocommerce-notices-wrapper .woocommerce-message {
                border-left-color: #059669 !important; /* Emerald 600 */
                background-color: #ECFDF5 !important; /* Emerald 50 */
                color: #065F46 !important; /* Emerald 800 */
            }

            /* Buttons/Links inside notices */
            .woocommerce-notices-wrapper .woocommerce-message .button,
            .woocommerce-notices-wrapper .woocommerce-message a.restore-item {
                float: none !important;
                margin-left: auto !important;
                background-color: transparent !important;
                color: currentColor !important;
                text-decoration: underline !important;
                font-weight: 600 !important;
                padding: 0 !important;
                border: none !important;
                transition: opacity 0.2s !important;
            }
            
            .woocommerce-notices-wrapper .woocommerce-message a.restore-item:hover,
            .woocommerce-notices-wrapper .woocommerce-message .button:hover {
                background-color: transparent !important;
                opacity: 0.8 !important;
            }

            /* Fix for icons if they exist (pseudo-elements) */
            .woocommerce-notices-wrapper .woocommerce-message::before,
            .woocommerce-notices-wrapper .woocommerce-info::before,
            .woocommerce-notices-wrapper .woocommerce-error::before {
                display: none !important; /* Hide default WC icons to control clean look */
            }
        </style>
        <?php
    }

    /**
     * Custom Mini Cart Quantity Input
     * Replaces standard "1 x $100" with [ - ] [ 1 ] [ + ]
     */
    public function custom_mini_cart_item_quantity($html, $cart_item, $cart_item_key)
    {
        $_product = $cart_item['data'];
        if ($_product->is_sold_individually()) {
            return $html;
        }

        $current_qty = $cart_item['quantity'];
        $is_tour = $_product->is_type('ovatb_tour');
        $min_qty = $is_tour ? 1 : 0;

        // Build Custom Quantity Selector
        ob_start();
        ?>
        <div class="wiwa-mini-cart-qty">
            <button type="button" class="wiwa-qty-btn wiwa-qty-minus">&minus;</button>
            <input type="number" 
                   class="wiwa-qty-input" 
                   value="<?php echo esc_attr($current_qty); ?>" 
                   min="<?php echo esc_attr($min_qty); ?>" 
                   step="1" 
                   data-cart-key="<?php echo esc_attr($cart_item_key); ?>" 
                   data-is-tour="<?php echo $is_tour ? '1' : '0'; ?>"
                   readonly />
            <button type="button" class="wiwa-qty-btn wiwa-qty-plus">&plus;</button>
        </div>
        <span class="quantity" style="display:none !important"><?php echo $html; ?></span>
        <?php
        return ob_get_clean();
    }

    /**
     * Custom Main Cart Quantity Input
     */
    public function custom_cart_item_quantity($product_quantity, $cart_item_key, $cart_item)
    {
        $_product = $cart_item['data'];
        if ($_product->is_sold_individually()) {
            return $product_quantity;
        }

        $current_qty = $cart_item['quantity'];
        $is_tour = $_product->is_type('ovatb_tour');
        $min_qty = $is_tour ? 1 : 0;
        
        // Main cart often wraps input in .quantity div. We will replace it or inject ours.
        // Standard WC output is <div class="quantity"><input ...></div>
        
        ob_start();
        ?>
        <div class="wiwa-mini-cart-qty wiwa-main-cart-qty">
            <button type="button" class="wiwa-qty-btn wiwa-qty-minus">&minus;</button>
            <input type="number" 
                   class="wiwa-qty-input" 
                   name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]" 
                   value="<?php echo esc_attr($current_qty); ?>" 
                   min="<?php echo esc_attr($min_qty); ?>" 
                   step="1" 
                   data-cart-key="<?php echo esc_attr($cart_item_key); ?>" 
                   data-is-tour="<?php echo $is_tour ? '1' : '0'; ?>"
                   readonly />
            <button type="button" class="wiwa-qty-btn wiwa-qty-plus">&plus;</button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Clean Cart Item Data (Metadata)
     * Removes "numberof_adult", "numberof_child", etc from the line item description
     * because we show it in the quantity box or it's redundant.
     */
    public function clean_cart_item_data($item_data, $cart_item)
    {
        // Keys/labels to hide from cart item metadata display
        // These are redundant because our custom quantity pill handles pax count
        $hidden_keys = [
            'numberof_adult', 'numberof_adults', 'numberof_child', 'numberof_children',
            'numberof_pax', 'numberof_guests', 'numberof_infant', 'numberof_infants',
            'Cantidad de viajeros', 'cantidad de viajeros',
            'adults', 'children', 'enfants', 'niños', 'infants',
            'Adultos', 'Niños', 'Infantes',
        ];

        foreach ($item_data as $key => $data) {
            $data_key = isset($data['key']) ? strtolower(trim($data['key'])) : '';
            $data_name = isset($data['name']) ? strtolower(trim($data['name'])) : '';

            // Exact match against hidden keys (case-insensitive)
            if (in_array($data_key, array_map('strtolower', $hidden_keys)) || 
                in_array($data_name, array_map('strtolower', $hidden_keys))) {
                unset($item_data[$key]);
                continue;
            }
            
            // Loose check for any "numberof_" prefix
            if (strpos($data_key, 'numberof_') === 0) {
                unset($item_data[$key]);
            }
        }

        return $item_data;
    }

    /**
     * Agrega la información de los pasajeros al item del carrito.
     * Busca campos 'ovatb_{tipo}_info' en $_POST y los agrupa en 'ovatb_guest_info'.
     */
    public function aggregate_guest_info_for_cart($cart_item_data, $product_id, $variation_id)
    {
        if (empty($_POST)) return $cart_item_data;

        $guest_info = [];
        $found_data = false;
        $quantity_keys = [];

        // 1. Identificar qué tipos de pasajeros tienen cantidad (numberof_*)
        foreach ($_POST as $key => $value) {
            if (preg_match('/^numberof_([a-zA-Z0-9_]+)$/', $key, $matches)) {
                if ($value > 0) {
                    $quantity_keys[] = $matches[1]; // ej: 'adult'
                }
            }
        }

        // 2. Recolectar info de pasajeros
        foreach ($_POST as $key => $value) {
            // Buscamos patrones como 'ovatb_adult_info', 'ovatb_child_info', etc.
            if (preg_match('/^ovatb_([a-zA-Z0-9_]+)_info$/', $key, $matches)) {
                $guest_type = $matches[1]; // ej: 'adult', 'child', 'pax'
                
                // Ignorar el campo 'guest' literal si existiera
                if ($guest_type === 'guest') continue;

                if (!empty($value) && is_array($value)) {
                    $guest_info[$guest_type] = $value;
                    $found_data = true;
                }
            }
        }

        // 3. FIX: Mapeo inteligente si hay discrepancia de slugs (ej: 'adult' vs 'pax')
        // Si tenemos cantidad para 'adult' pero info para 'pax', asignamos info de 'pax' a 'adult'.
        if ($found_data && !empty($quantity_keys)) {
             // Caso común: El sistema usa 'adult' para cantidad, pero 'pax' para info
             if (in_array('adult', $quantity_keys) && isset($guest_info['pax']) && !isset($guest_info['adult'])) {
                 $guest_info['adult'] = $guest_info['pax'];
             }
        }

        // 4. Guardar en el carrito
        if ($found_data) {
            if (isset($cart_item_data['ovatb_guest_info']) && is_array($cart_item_data['ovatb_guest_info'])) {
                $cart_item_data['ovatb_guest_info'] = array_merge($cart_item_data['ovatb_guest_info'], $guest_info);
            } else {
                $cart_item_data['ovatb_guest_info'] = $guest_info;
            }
        }

        return $cart_item_data;
    }
}
