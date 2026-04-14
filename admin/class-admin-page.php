<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Admin_Page
{

    public static function render()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=wiwa-checkout-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'wiwa-checkout'); ?></a>
                <a href="?page=wiwa-checkout-settings&tab=fields" class="nav-tab <?php echo $active_tab == 'fields' ? 'nav-tab-active' : ''; ?>"><?php _e('Fields', 'wiwa-checkout'); ?></a>
                <a href="?page=wiwa-checkout-settings&tab=integrations" class="nav-tab <?php echo $active_tab == 'integrations' ? 'nav-tab-active' : ''; ?>"><?php _e('Integrations', 'wiwa-checkout'); ?></a>
            </h2>
            
            <?php
        // Each tab handles its own form and save logic
        if ($active_tab == 'general') {
            self::render_tab_general();
        }
        else if ($active_tab == 'fields') {
            self::render_tab_fields();
        }
        else if ($active_tab == 'integrations') {
            self::render_tab_integrations();
        }
?>
        </div>
        <?php
    }

    public static function enqueue_assets($hook)
    {
        $screen = get_current_screen();
        if (strpos($screen->id, 'wiwa-checkout-settings') === false) {
            return;
        }

        wp_enqueue_style('wiwa-admin-fields-css', WIWA_CHECKOUT_URL . 'admin/assets/css/admin-fields.css', [], WIWA_CHECKOUT_VERSION);
        wp_enqueue_script('wiwa-admin-fields-js', WIWA_CHECKOUT_URL . 'admin/assets/js/admin-fields.js', ['jquery', 'jquery-ui-sortable'], WIWA_CHECKOUT_VERSION, true);

        // Pass field types to JS
        wp_localize_script('wiwa-admin-fields-js', 'wiwaFieldTypes', Wiwa_Fields_Manager::get_field_types());
    }

    private static function render_tab_general()
    {
        require_once WIWA_CHECKOUT_PATH . 'admin/views/tab-general.php';
    }

    private static function render_tab_fields()
    {
        require_once WIWA_CHECKOUT_PATH . 'admin/views/tab-fields.php';
    }

    private static function render_tab_integrations()
    {
        require_once WIWA_CHECKOUT_PATH . 'admin/views/tab-integrations.php';
    }
}
