<?php
/**
 * Plugin Name: Wiwa Tour Checkout Pro
 * Plugin URI: http://connexis.co/
 * Description: Sistema enterprise de checkout personalizado para tours con backend visual, integraciones avanzadas (GeoIP, WOOCS) y soporte multi-idioma.
 * Version: 2.6.0
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
define('WIWA_CHECKOUT_VERSION', '2.8.8');
define('WIWA_CHECKOUT_FILE', __FILE__);
define('WIWA_CHECKOUT_PATH', plugin_dir_path(__FILE__));
define('WIWA_CHECKOUT_URL', plugin_dir_url(__FILE__));
define('WIWA_CHECKOUT_BASENAME', plugin_basename(__FILE__));


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

        // FIX: Agrupar datos de pasajeros (Guest Info) en el servidor para evitar fallos de JS
        add_filter('woocommerce_add_cart_item_data', [$this, 'aggregate_guest_info_for_cart'], 10, 3);

        // DEBUG PATH HOOK
        add_action('wp_head', [$this, 'debug_paths']);
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
    /**
     * Agrega la información de los pasajeros al item del carrito.
     * Busca campos 'ovatb_{tipo}_info' en $_POST y los agrupa en 'ovatb_guest_info'.
     */
    public function aggregate_guest_info_for_cart($cart_item_data, $product_id, $variation_id)
    {
        if (empty($_POST)) return $cart_item_data;

        $guest_info = [];
        $found_data = false;

        // Recorrer $_POST buscando patrones de OvaTourBooking
        foreach ($_POST as $key => $value) {
            // Buscamos patrones como 'ovatb_adult_info', 'ovatb_child_info', etc.
            if (preg_match('/^ovatb_([a-zA-Z0-9_]+)_info$/', $key, $matches)) {
                $guest_type = $matches[1]; // ej: 'adult', 'child'
                
                // Ignorar el campo 'guest' literal si existiera, buscamos tipos específicos
                if ($guest_type === 'guest') continue;

                // Estructura esperada por el backend: $guest_info['adult'] = [ ... ]
                if (!empty($value) && is_array($value)) {
                    $guest_info[$guest_type] = $value;
                    $found_data = true;
                }
            }
        }

        // Si encontramos datos y no existe ya 'ovatb_guest_info' (o está vacío), lo asignamos
        if ($found_data) {
            // Si ya existe data parcial, la mezclamos (priorizando la que acabamos de armar)
            if (isset($cart_item_data['ovatb_guest_info']) && is_array($cart_item_data['ovatb_guest_info'])) {
                $cart_item_data['ovatb_guest_info'] = array_merge($cart_item_data['ovatb_guest_info'], $guest_info);
            } else {
                $cart_item_data['ovatb_guest_info'] = $guest_info;
            }
        }

        return $cart_item_data;
    }
}

// Iniciar
function wiwa_tour_checkout()
{
    return Wiwa_Tour_Checkout::instance();
}

// Arrancar el plugin
wiwa_tour_checkout();
