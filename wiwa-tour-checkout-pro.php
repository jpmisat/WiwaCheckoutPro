<?php
/**
 * Plugin Name: Wiwa Tour Checkout Pro
 * Plugin URI: http://connexis.co/
 * Description: Sistema enterprise de checkout personalizado para tours con backend visual, integraciones avanzadas (GeoIP, WOOCS) y soporte multi-idioma.
 * Version: 2.11.6
 * Author: Juan Pablo Misat - Connexis
 * Author URI: http://connexis.co/
 * Text Domain: wiwa-checkout
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * WC requires at least: 7.0
 * WC tested up to: 8.9
 * License: GPL v2 or later
 * 
 * @package WiwaTourCheckout
 */

if (!defined('ABSPATH')) {
    exit;
}

// Declarar compatibilidad con WooCommerce HPOS
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Definir constantes
define('WIWA_CHECKOUT_VERSION', '2.11.6');
define('WIWA_CHECKOUT_FILE', __FILE__);
define('WIWA_CHECKOUT_PATH', plugin_dir_path(__FILE__));
define('WIWA_CHECKOUT_URL', plugin_dir_url(__FILE__));
define('WIWA_CHECKOUT_BASENAME', plugin_basename(__FILE__));

// ... (restored)

/**
 * Clase principal del plugin (Singleton)
 */
final class Wiwa_Tour_Checkout
{

    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();

        // Verificar dependencias en admin
        if (is_admin()) {
            add_action('admin_init', [$this, 'check_dependencies']);
        }
    }

    private function includes()
    {
        // Core & Backend
        require_once WIWA_CHECKOUT_PATH . 'includes/class-settings.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-fields-manager.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-i18n.php'; // Translation

        // Integrations
        require_once WIWA_CHECKOUT_PATH . 'includes/class-geoip-integration.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-fox-integration.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-typeahead-data.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-tour-booking-integration.php';

        // Handlers
        require_once WIWA_CHECKOUT_PATH . 'includes/class-checkout-handler.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-payment-handler.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-thankyou-handler.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-cart-handler.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-ajax-handler.php';

        // Admin
        if (is_admin()) {
            require_once WIWA_CHECKOUT_PATH . 'admin/class-admin-page.php';
        }
    }

    private function init_hooks()
    {
        // Inicializar internacionalización
        add_action('plugins_loaded', ['Wiwa_I18n', 'load_plugin_textdomain']);

        // Hooks de activación/desactivación
        register_activation_hook(WIWA_CHECKOUT_FILE, [$this, 'activate']);
        register_deactivation_hook(WIWA_CHECKOUT_FILE, [$this, 'deactivate']);

        // Inicializar integración de settings
        add_action('plugins_loaded', function () {
            Wiwa_Settings::init();
        });


        // Inicializar Handlers
        add_action('plugins_loaded', function () {
            new Wiwa_Checkout_Handler();
            new Wiwa_Thankyou_Handler();
            new Wiwa_Ajax_Handler();
            new Wiwa_Cart_Handler();
        });

        // AJAX Add to Cart Hooks
        add_action('wp_ajax_wiwa_ajax_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_wiwa_ajax_add_to_cart', [$this, 'ajax_add_to_cart']);

        // FIX: Agrupar datos de pasajeros (Guest Info) en el servidor para evitar fallos de JS
        add_filter('woocommerce_add_cart_item_data', [$this, 'aggregate_guest_info_for_cart'], 10, 3);

        // DEBUG PATH HOOK
        add_action('wp_head', [$this, 'debug_paths']);

        // Enqueue Custom Add to Cart Script
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_scripts']);

        // --- CART REDESIGN HOOKS ---
        // --- CART REDESIGN HOOKS ---
        // Filter to customize Mini Cart Quantity
        add_filter('woocommerce_widget_cart_item_quantity', [$this, 'custom_mini_cart_item_quantity'], 999, 3);
        
        // Filter to customize Main Cart Quantity
        add_filter('woocommerce_cart_item_quantity', [$this, 'custom_cart_item_quantity'], 999, 3);
        
        // Remove Redundant "Cantidad de viajeros" metadata if present (to avoid duplication with input)
        add_filter('woocommerce_get_item_data', [$this, 'clean_cart_item_data'], 10, 2);
        
        // AJAX Handler for Mini Cart Quantity Update
        add_action('wp_ajax_wiwa_update_mini_cart_qty', [$this, 'ajax_update_mini_cart_qty']);
        add_action('wp_ajax_nopriv_wiwa_update_mini_cart_qty', [$this, 'ajax_update_mini_cart_qty']);

        // AJAX Handler for Smart Pax Update (OvaTourBooking Metadata)
        add_action('wp_ajax_wiwa_update_tour_pax', [$this, 'ajax_update_tour_pax']);
        add_action('wp_ajax_nopriv_wiwa_update_tour_pax', [$this, 'ajax_update_tour_pax']);
    }

    public function check_dependencies()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p><strong>Wiwa Tour Checkout Pro</strong> requiere WooCommerce activo.</p></div>';
            });
        }
    }

    public function activate()
    {
        // Verificar PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            wp_die('Este plugin requiere PHP 7.4 o superior.');
        }

        // Crear página de checkout personalizada
        $this->create_checkout_page();

        // Setup inicial
        flush_rewrite_rules();
        set_transient('wiwa_checkout_activated', true, 60);
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    private function create_checkout_page()
    {
        $page_slug = 'checkout-wiwa';
        $page = get_page_by_path($page_slug);

        if (!$page) {
            wp_insert_post([
                'post_title' => 'Checkout Wiwa',
                'post_name' => $page_slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[wiwa_checkout]',
                'post_author' => get_current_user_id()
            ]);
        }
    }

    // DEBUG PATH
    public function debug_paths()
    {
        echo "\n<!-- DEBUG PATH INFO: " . WIWA_CHECKOUT_PATH . " -->\n";
        echo "<!-- DEBUG TEMPLATE 1: " . WIWA_CHECKOUT_PATH . "templates/checkout/step-1.php -->\n";
    }

    public function enqueue_custom_scripts()
    {
        // --- ADD TO CART POPUP ASSETS (Global) ---
        if ( !is_admin() ) {
            wp_enqueue_style('wiwa-add-to-cart', WIWA_CHECKOUT_URL . 'assets/css/add-to-cart.css', [], WIWA_CHECKOUT_VERSION);
            wp_enqueue_script('wiwa-add-to-cart', WIWA_CHECKOUT_URL . 'assets/js/add-to-cart.js', ['jquery'], WIWA_CHECKOUT_VERSION, true);
            wp_localize_script('wiwa-add-to-cart', 'wiwaAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wiwa_checkout_nonce')
            ]);
        }

        // --- CART PAGE SPECIFIC ASSETS ---
        if ( !is_admin() && is_cart() ) {
            // 1. Tailwind CSS CDN (cart page only to avoid breaking other pages)
            wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com?plugins=forms,container-queries', [], null, false);

            // 2. Tailwind config with Stitch design tokens (inline after tailwind loads)
            $tw_config = "
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                'wiwa-cream': '#fdfbf7',
                                'wiwa-bg': '#f9f9f9',
                                'wiwa-green': '#1a3c28',
                                'wiwa-green-light': '#2b4c3b',
                                'wiwa-text-gray': '#4b5563',
                                'wiwa-border': '#e5e7eb',
                            },
                            fontFamily: {
                                sans: ['Montserrat', 'Roboto', 'sans-serif'],
                            }
                        }
                    }
                }
            ";
            wp_add_inline_script('tailwindcss', $tw_config);

            // 3. Google Material Symbols (icons used in the design)
            wp_enqueue_style('material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', [], null);

            // 4. Montserrat Font
            wp_enqueue_style('google-montserrat', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap', [], null);

            // 5. Custom cart styles (overrides + Stitch extras)
            wp_enqueue_style('wiwa-cart-styles', WIWA_CHECKOUT_URL . 'assets/css/wiwa-cart-styles.css', [], WIWA_CHECKOUT_VERSION);
        }

        // --- MINI CART / SIDEBAR ASSETS (Global) ---
        if ( !is_admin() ) {
            wp_enqueue_style('wiwa-cart-styles-global', WIWA_CHECKOUT_URL . 'assets/css/wiwa-cart-styles.css', [], WIWA_CHECKOUT_VERSION);
            wp_enqueue_script('wiwa-mini-cart', WIWA_CHECKOUT_URL . 'assets/js/wiwa-mini-cart.js', ['jquery'], WIWA_CHECKOUT_VERSION, true);
            wp_localize_script('wiwa-mini-cart', 'wiwa_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wiwa_checkout_nonce')
            ]);
        }
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
     * AJAX Handler: Update Mini Cart Quantity
     */
    public function ajax_update_mini_cart_qty()
    {
        check_ajax_referer('wiwa_checkout_nonce', 'security');

        $cart_key = isset($_POST['cart_key']) ? sanitize_text_field($_POST['cart_key']) : '';
        $qty = isset($_POST['qty']) ? max(0, min(99, intval($_POST['qty']))) : 0;

        if (!$cart_key || !isset(WC()->cart->get_cart()[$cart_key])) {
            wp_send_json_error(['message' => 'Missing cart key']);
        }

        $item_removed = false;

        if ($qty <= 0) {
            WC()->cart->remove_cart_item($cart_key);
            $item_removed = true;
        } else {
            WC()->cart->set_quantity($cart_key, $qty, true); // true = refresh totals
        }

        WC()->cart->calculate_totals();
        WC()->cart->maybe_set_cart_cookies();

        wp_send_json_success([
            'message' => 'Updated',
            'item_removed' => $item_removed,
            'item_subtotal' => $item_removed ? '' : $this->get_cart_item_subtotal_html($cart_key),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_total' => WC()->cart->get_total(),
            'totals_html' => $this->get_cart_totals_html(),
        ]);
    }

    /**
     * Render cart totals block for AJAX responses.
     */
    private function get_cart_totals_html()
    {
        if (!function_exists('woocommerce_cart_totals')) {
            return '';
        }

        ob_start();
        woocommerce_cart_totals();
        return ob_get_clean();
    }

    /**
     * Resolve line subtotal HTML for a specific cart item.
     */
    private function get_cart_item_subtotal_html($cart_item_key)
    {
        $cart = WC()->cart ? WC()->cart->get_cart() : [];
        if (!isset($cart[$cart_item_key])) {
            return '';
        }

        $cart_item = $cart[$cart_item_key];
        if (!isset($cart_item['data'])) {
            return '';
        }

        return WC()->cart->get_product_subtotal($cart_item['data'], $cart_item['quantity']);
    }

    /**
     * Extract and normalize guest breakdown from cart item metadata.
     */
    private function get_cart_item_guest_breakdown($cart_item)
    {
        $breakdown = [];

        foreach ((array) $cart_item as $key => $value) {
            if (strpos($key, 'numberof_') !== 0 || $key === 'numberof_guests' || !is_numeric($value)) {
                continue;
            }

            $count = max(0, intval($value));
            if ($count <= 0) {
                continue;
            }

            $slug = str_replace('numberof_', '', $key);
            $breakdown[$slug] = [
                'key' => $key,
                'label' => ucwords(str_replace(['_', '-'], ' ', $slug)),
                'count' => $count,
            ];
        }

        return $breakdown;
    }

    /**
     * Determine the editable passenger metadata key for a cart item.
     */
    private function resolve_target_guest_key($cart_item, $requested_key = '')
    {
        $all_guest_keys = [];

        foreach ((array) $cart_item as $key => $value) {
            if (strpos($key, 'numberof_') === 0 && $key !== 'numberof_guests' && is_numeric($value)) {
                $all_guest_keys[] = $key;
            }
        }

        foreach (['numberof_pax', 'numberof_adult', 'numberof_adults'] as $fallback_key) {
            if (isset($cart_item[$fallback_key]) && !in_array($fallback_key, $all_guest_keys, true)) {
                $all_guest_keys[] = $fallback_key;
            }
        }

        if (empty($all_guest_keys)) {
            return ['', []];
        }

        if ($requested_key) {
            $normalized = strpos($requested_key, 'numberof_') === 0 ? $requested_key : 'numberof_' . $requested_key;
            if (in_array($normalized, $all_guest_keys, true)) {
                return [$normalized, $all_guest_keys];
            }
        }

        if (in_array('numberof_pax', $all_guest_keys, true)) {
            return ['numberof_pax', $all_guest_keys];
        }

        foreach ($all_guest_keys as $guest_key) {
            if (!empty($cart_item[$guest_key]) && intval($cart_item[$guest_key]) > 0) {
                return [$guest_key, $all_guest_keys];
            }
        }

        return [$all_guest_keys[0], $all_guest_keys];
    }


    /**
     * AJAX Handler for Add to Cart
     */
    public function ajax_add_to_cart()
    {
        // Verificar nonce (opcional pero recomendado, aunque ova-tour a veces usa 'ovatb-admin-ajax')
        // check_ajax_referer('ovatb-admin-ajax', 'security'); 

        $product_id = isset($_POST['ovatb-product-id']) ? intval($_POST['ovatb-product-id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(['message' => 'ID de producto inválido.']);
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Producto no encontrado.']);
        }

        // --- VALIDACIÓN ---
        // Replicamos la logica de OVATB_Ajaxs::ovatb_calculate_total para validar
        
        // 1. Fechas y Horas
        $checkin_date_str = isset($_POST['checkin_date']) ? sanitize_text_field($_POST['checkin_date']) : '';
        $checkout_date_str = isset($_POST['checkout_date']) ? sanitize_text_field($_POST['checkout_date']) : '';
        $start_time_str = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';

        $checkin_date = strtotime($checkin_date_str);
        $checkout_date = strtotime($checkout_date_str);
        $start_time = strtotime($start_time_str);

        // Convert input date (Logic from ovatb)
        if (function_exists('OVATB')) {
            $new_dates = OVATB()->options->convert_input_date($product_id, $checkin_date, $checkout_date, $start_time);
            $checkin_date = strtotime($new_dates['checkin_date']);
            $checkout_date = strtotime($new_dates['checkout_date']);
        }

        // 2. Validate Booking
        if (function_exists('OVATB') && isset(OVATB()->booking)) {
            $passed = OVATB()->booking->booking_validation($product_id, $checkin_date, $checkout_date, isset($_POST['form_name']) ? $_POST['form_name'] : '');
            if ($passed && $passed !== true) {
                wp_send_json_error(['message' => $passed]);
            }
        }

        // 3. Guests Validation
        $data = [
            'product_id' => $product_id,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
        ];
        
        // Recopilar numberof_*
        $numberof_guests = 0;
        if (function_exists('OVATB') && $product->is_type('ovatb_tour')) {
            $guest_options = $product->get_guests();
            if ($guest_options) {
                foreach ($guest_options as $guest) {
                    $key = 'numberof_' . $guest['name']; // ovtab_numberof_... ? En el form suele ser numberof_adult
                    // El plugin usa 'ovatb_numberof_' en el ajax calculate total, pero el form input name suele ser 'numberof_adult'
                    // Revisar form booking-form... pero en calculate_total usa ovatb_get_meta_data que busca varios prefijos.
                    // Usaremos $_POST directo con fallback.
                    
                    $val = isset($_POST['numberof_' . $guest['name']]) ? intval($_POST['numberof_' . $guest['name']]) : 0;
                    // También probar con ovatb_numberof_ si falla el anterior
                    if (!$val && isset($_POST['ovatb_numberof_' . $guest['name']])) {
                        $val = intval($_POST['ovatb_numberof_' . $guest['name']]);
                    }

                    $data['numberof_' . $guest['name']] = $val;
                    $numberof_guests += $val;
                }
            }
        }
        $data['numberof_guests'] = $numberof_guests;

        if (function_exists('OVATB') && isset(OVATB()->booking)) {
            $mesg = OVATB()->booking->numberof_guests_validation($data, $product);
            if ($mesg && $mesg !== true) {
                 wp_send_json_error(['message' => $mesg]);
            }
            
            // Availability Check
             $available = OVATB()->booking->get_numberof_available_guests($product_id, $checkin_date, $checkout_date, $numberof_guests, isset($_POST['form_name']) ? $_POST['form_name'] : '');
             if (isset($available['error']) && $available['error']) {
                 wp_send_json_error(['message' => $available['error']]);
             }
        }

        // --- AGREGAR AL CARRITO ---
        // WooCommerce y OvaTourBooking hooks se encargarán de leer $_POST
        // para agregar la meta data (guests, dates, etc) al item del carrito.
        // Siempre que $_POST esté poblado (lo está en AJAX), esto debería funcionar.

        $added = WC()->cart->add_to_cart($product_id, 1);

        if ($added) {
            wp_send_json_success([
                'message' => 'Producto agregado al carrito',
                'product_title' => $product->get_name(),
                'cart_url' => wc_get_cart_url()
            ]);
        } else {
            // Recopilar errores de WC
            $errors = wc_get_notices('error');
            wc_clear_notices(); // Limpiar para que no salgan en la próxima página
            
            // Format mistakes
            $msg = 'No se pudo agregar al carrito.';
            if (!empty($errors)) {
                // $errors es array de arrays o HTML strings
                 // Simplificación:
                 $msg .= ' Verifique los datos.';
            }
            wp_send_json_error(['message' => $msg]);
        }
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

    /**
     * AJAX Handler: Update TOUR Pax (Metadata)
     * Handles the complex logic of updating 'numberof_X' metadata.
     */
    public function ajax_update_tour_pax()
    {
        check_ajax_referer('wiwa_checkout_nonce', 'security');

        $cart_key = isset($_POST['cart_key']) ? sanitize_text_field($_POST['cart_key']) : '';
        $action = isset($_POST['update_action']) ? sanitize_text_field($_POST['update_action']) : 'update';
        $requested_qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
        $requested_guest_key = isset($_POST['guest_key']) ? sanitize_key($_POST['guest_key']) : '';

        $cart = WC()->cart->get_cart();

        if (!$cart_key || !isset($cart[$cart_key])) {
            wp_send_json_error(['message' => 'Item not found']);
        }

        $cart_item = $cart[$cart_key];

        list($target_key, $all_guest_keys) = $this->resolve_target_guest_key($cart_item, $requested_guest_key);
        if (!$target_key) {
            wp_send_json_error(['message' => 'Passenger metadata not found.']);
        }

        $current_val = isset($cart_item[$target_key]) ? intval($cart_item[$target_key]) : 1;

        if ($requested_qty > 0) {
            $new_val = $requested_qty;
        } elseif ($action === 'increase') {
            $new_val = $current_val + 1;
        } else {
            $new_val = $current_val - 1;
        }

        $new_val = max(1, min(99, intval($new_val)));

        WC()->cart->cart_contents[$cart_key][$target_key] = $new_val;

        $total_guests = 0;
        foreach ($all_guest_keys as $guest_key) {
            $value = isset(WC()->cart->cart_contents[$cart_key][$guest_key]) ? intval(WC()->cart->cart_contents[$cart_key][$guest_key]) : 0;
            $value = max(0, $value);
            WC()->cart->cart_contents[$cart_key][$guest_key] = $value;
            $total_guests += $value;
        }

        if ($total_guests <= 0) {
            WC()->cart->cart_contents[$cart_key][$target_key] = 1;
            $new_val = 1;
            $total_guests = 1;
        }

        WC()->cart->cart_contents[$cart_key]['numberof_guests'] = $total_guests;
        WC()->cart->set_session();
        WC()->cart->calculate_totals();
        WC()->cart->maybe_set_cart_cookies();

        $cart_after = WC()->cart->get_cart();
        if (!isset($cart_after[$cart_key])) {
            wp_send_json_error(['message' => 'Unable to refresh cart item.']);
        }

        $updated_item = $cart_after[$cart_key];
        $breakdown_data = $this->get_cart_item_guest_breakdown($updated_item);

        $guest_breakdown = [];
        $guest_breakdown_text = [];
        foreach ($breakdown_data as $slug => $guest_data) {
            $guest_breakdown[$slug] = $guest_data['count'];
            $guest_breakdown_text[] = sprintf('%d %s', $guest_data['count'], $guest_data['label']);
        }

        wp_send_json_success([
            'new_qty' => $new_val,
            'total_pax' => isset($updated_item['numberof_guests']) ? intval($updated_item['numberof_guests']) : $new_val,
            'target_key' => $target_key,
            'guest_breakdown' => $guest_breakdown,
            'guest_breakdown_text' => implode(' - ', $guest_breakdown_text),
            'item_subtotal' => $this->get_cart_item_subtotal_html($cart_key),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_total' => WC()->cart->get_total(),
            'totals_html' => $this->get_cart_totals_html(),
            'message' => 'Pax updated',
        ]);
    }
}

// Iniciar
function wiwa_tour_checkout()
{
    return Wiwa_Tour_Checkout::instance();
}

// Arrancar el plugin
wiwa_tour_checkout();

/**
 * Helper: Extract tour meta from OvaTourBooking cart item.
 * Returns ['checkin' => string, 'checkout' => string, 'duration_days' => int, 'travelers' => int, 'guests_detail' => array]
 * Moved from template to avoid redeclaration errors.
 */
function wiwa_extract_tour_meta($cart_item) {
    // Check if Woo dates function exists, if not use fallback
    $date_format = get_option('date_format');

    $meta = [
        'checkin'        => '',
        'checkout'       => '',
        'duration_days'  => 0,
        'duration_label' => '',
        'travelers'      => isset($cart_item['quantity']) ? $cart_item['quantity'] : 1,
        'guests_detail'  => [],
    ];

    // --- Check-in / Check-out dates (OvaTourBooking keys) ---
    if (!empty($cart_item['checkin_date'])) {
        $ts = strtotime($cart_item['checkin_date']);
        $meta['checkin'] = $ts ? date_i18n('d-m-Y', $ts) : sanitize_text_field($cart_item['checkin_date']);
    }
    if (!empty($cart_item['checkout_date'])) {
        $ts_out = strtotime($cart_item['checkout_date']);
        $meta['checkout'] = $ts_out ? date_i18n('d-m-Y', $ts_out) : sanitize_text_field($cart_item['checkout_date']);
    }

    // --- Duration (diff between checkin and checkout) ---
    if (!empty($cart_item['checkin_date']) && !empty($cart_item['checkout_date'])) {
        $ts_in  = strtotime($cart_item['checkin_date']);
        $ts_out = strtotime($cart_item['checkout_date']);
        if ($ts_in && $ts_out && $ts_out >= $ts_in) {
            // Difference in nights + 1 = total inclusive days (standard tourism logic)
            $diff_nights = (int) ceil(($ts_out - $ts_in) / 86400);
            $meta['duration_days'] = $diff_nights + 1; 

            if ($meta['duration_days'] === 1) {
                $meta['duration_label'] = '1 día full';
            } else {
                $meta['duration_label'] = $meta['duration_days'] . ' días';
            }
        }
    }

    // If still no duration, try the product's duration metadata
    if ($meta['duration_days'] === 0 && isset($cart_item['data'])) {
        $_product = $cart_item['data'];
        if (method_exists($_product, 'get_meta_value')) {
            $dur_type = $_product->get_meta_value('duration_type');
            if ($dur_type === 'fixed') {
                $dur_number = (int) $_product->get_meta_value('duration_number');
                $dur_unit   = $_product->get_meta_value('duration_unit'); // 'day', 'hour', 'night'
                if ($dur_number > 0) {
                    $meta['duration_days'] = $dur_number;
                    if ($dur_unit === 'hour') {
                        $meta['duration_label'] = $dur_number . ' hora' . ($dur_number > 1 ? 's' : '');
                    } elseif ($dur_unit === 'night') {
                        $meta['duration_label'] = $dur_number . ' noche' . ($dur_number > 1 ? 's' : '');
                    } else {
                        $meta['duration_label'] = $dur_number . ' día' . ($dur_number > 1 ? 's' : '');
                    }
                }
            }
        }
    }

    // --- Travelers / Passengers total ---
    if (isset($cart_item['numberof_guests']) && intval($cart_item['numberof_guests']) > 0) {
        $meta['travelers'] = intval($cart_item['numberof_guests']);
    } else {
        // Sum all numberof_ keys as fallback
        $total_pax = 0;
        foreach ($cart_item as $key => $val) {
            if (strpos($key, 'numberof_') === 0 && $key !== 'numberof_guests') {
                $total_pax += intval($val);
            }
        }
        if ($total_pax > 0) {
            $meta['travelers'] = $total_pax;
        }
    }

    // Guest detail breakdown for the AJAX
    if (isset($cart_item['data'])) {
        $_product = $cart_item['data'];
        if (method_exists($_product, 'get_guests')) {
            $guest_options = $_product->get_guests();
            if (is_array($guest_options)) {
                foreach ($guest_options as $guest) {
                    $gname = $guest['name'];
                    $glabel = isset($guest['label']) ? $guest['label'] : ucfirst($gname);
                    $gcount = isset($cart_item['numberof_' . $gname]) ? intval($cart_item['numberof_' . $gname]) : 0;
                    $meta['guests_detail'][] = [
                        'name'  => $gname,
                        'label' => $glabel,
                        'count' => $gcount,
                    ];
                }
            }
        }
    }

    return $meta;
}
