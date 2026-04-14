<?php
/**
 * Gestión de campos de pasajeros
 * 
 * @package WiwaTourCheckout
 * @author Juan Pablo Misat - Connexis
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Guest_Fields
{

    private $guest_types = [];

    public function __construct()
    {
        $this->load_guest_types();
    }

    /**
     * Cargar tipos de huéspedes desde settings de OVA
     */
    private function load_guest_types()
    {
        $settings = get_option('ovabrw_guest_settings', []);

        if (!empty($settings)) {
            $this->guest_types = $settings;
        }
        else {
            $this->guest_types = [
                'adult' => [
                    'label' => __('Adult', 'wiwa-checkout'),
                    'fields' => $this->get_default_fields()
                ]
            ];
        }
    }

    /**
     * Obtener campos por defecto
     */
    private function get_default_fields()
    {
        return [
            'guest_first_name' => ['label' => __('Name', 'wiwa-checkout'), 'required' => true, 'type' => 'text'],
            'guest_last_name' => ['label' => __('Last Name', 'wiwa-checkout'), 'required' => true, 'type' => 'text'],
            'guest_email' => ['label' => __('Email', 'wiwa-checkout'), 'required' => false, 'type' => 'email'],
            'guest_phone' => ['label' => __('Phone', 'wiwa-checkout'), 'required' => false, 'type' => 'tel'],
            'guest_passport' => ['label' => __('Document', 'wiwa-checkout'), 'required' => true, 'type' => 'text'],
            'guest_nationality' => ['label' => __('Nationality', 'wiwa-checkout'), 'required' => true, 'type' => 'select'],
        ];
    }

    /**
     * Obtener opciones de países
     */
    public function get_countries_options()
    {
        if (!function_exists('WC')) {
            return '';
        }

        $countries = WC()->countries->get_countries();
        $options = '';

        foreach ($countries as $code => $name) {
            $selected = $code === 'CO' ? 'selected' : '';
            $options .= sprintf('<option value="%s" %s>%s</option>', esc_attr($code), $selected, esc_html($name));
        }

        return $options;
    }
}
