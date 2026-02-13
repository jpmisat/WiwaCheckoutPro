<?php
/**
 * Plugin Name: Wiwa Tour Checkout Pro
 * Plugin URI: http://connexis.co/
 * Description: Sistema enterprise de checkout personalizado para tours con backend visual, integraciones avanzadas (GeoIP, WOOCS) y soporte multi-idioma.
 * Version: 2.11.7
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
define('WIWA_CHECKOUT_VERSION', '2.11.7');
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
        require_once WIWA_CHECKOUT_PATH . 'includes/wiwa-helpers.php'; // Global Helpers

        // Integrations
        require_once WIWA_CHECKOUT_PATH . 'includes/class-geoip-integration.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-fox-integration.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-typeahead-data.php';
        require_once WIWA_CHECKOUT_PATH . 'includes/class-tour-booking-integration.php';

        // Handlers
        require_once WIWA_CHECKOUT_PATH . 'includes/class-wiwa-assets.php'; // Asset Manager
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
            new Wiwa_Assets();
            new Wiwa_Checkout_Handler();
            new Wiwa_Thankyou_Handler();
            new Wiwa_Ajax_Handler();
            new Wiwa_Cart_Handler();
        });

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
        if (defined('WP_DEBUG') && WP_DEBUG) {
             echo "\n<!-- DEBUG PATH INFO: " . WIWA_CHECKOUT_PATH . " -->\n";
        }
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
