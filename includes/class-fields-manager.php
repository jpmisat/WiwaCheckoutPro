<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Fields_Manager
{

    /**
     * Get all custom fields, with labels translated via WPML on the frontend.
     */
    public static function get_fields()
    {
        $fields = get_option('wiwa_checkout_fields', self::get_default_fields());

        // On frontend, translate labels through WPML String Translation
        if (!is_admin()) {
            foreach ($fields as $group => &$group_fields) {
                foreach ($group_fields as $key => &$field) {
                    if (!empty($field['label'])) {
                        $field['label'] = self::translate_field_label($key, $field['label']);
                    }
                    // Translate select options too
                    if (!empty($field['options']) && is_array($field['options'])) {
                        foreach ($field['options'] as $opt_key => &$opt_label) {
                            if (!empty($opt_label)) {
                                $opt_label = self::translate_field_label($key . '_opt_' . $opt_key, $opt_label);
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Translate a single field label via WPML String Translation.
     *
     * @param string $name  Unique identifier for the string (field key).
     * @param string $label The original label text.
     * @return string Translated label.
     */
    public static function translate_field_label($name, $label)
    {
        return apply_filters(
            'wpml_translate_single_string',
            $label,
            'wiwa-checkout',
            'field_' . sanitize_key($name)
        );
    }

    /**
     * Register all saved field labels with WPML String Translation.
     * Called on `init` so labels appear in WPML > String Translation.
     */
    public static function register_fields_for_wpml()
    {
        $fields = get_option('wiwa_checkout_fields', []);
        if (empty($fields) || !is_array($fields)) {
            return;
        }

        foreach ($fields as $group => $group_fields) {
            if (!is_array($group_fields)) continue;

            foreach ($group_fields as $key => $field) {
                if (!empty($field['label'])) {
                    do_action(
                        'wpml_register_single_string',
                        'wiwa-checkout',
                        'field_' . sanitize_key($key),
                        $field['label']
                    );
                }
                // Register select options too
                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $opt_key => $opt_label) {
                        if (!empty($opt_label)) {
                            do_action(
                                'wpml_register_single_string',
                                'wiwa-checkout',
                                'field_' . sanitize_key($key) . '_opt_' . sanitize_key($opt_key),
                                $opt_label
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Default fields structure - expanded with common WooCommerce billing fields
     */
    private static function get_default_fields()
    {
        return [
            'billing' => [
                'billing_first_name' => [
                    'label' => __('Name', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'billing_last_name' => [
                    'label' => __('Last Name', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'billing_email' => [
                    'label' => __('Email', 'wiwa-checkout'),
                    'type' => 'email',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'full'
                ],
                'billing_phone' => [
                    'label' => __('Phone', 'wiwa-checkout'),
                    'type' => 'tel',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'billing_country' => [
                    'label' => __('Nationality', 'wiwa-checkout'),
                    'type' => 'select',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'billing_city' => [
                    'label' => __('City', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => false,
                    'enabled' => true,
                    'width' => 'half',
                    'geoip_auto' => true
                ],
                'billing_document_type' => [
                    'label' => __('Document Type', 'wiwa-checkout'),
                    'type' => 'select',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'quarter',
                    'options' => [
                        'cc' => __('ID Card', 'wiwa-checkout'),
                        'passport' => __('Passport', 'wiwa-checkout'),
                        'nit' => __('Tax ID (NIT)', 'wiwa-checkout'),
                        'ce' => __('Foreign ID', 'wiwa-checkout'),
                        'other' => __('Other', 'wiwa-checkout')
                    ]
                ],
                'billing_document' => [
                    'label' => __('Document Number', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'three-quarter'
                ],
                'billing_company' => [
                    'label' => __('Company', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => false,
                    'enabled' => false,
                    'width' => 'full'
                ],
                'billing_address_1' => [
                    'label' => __('Address', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => false,
                    'enabled' => false,
                    'width' => 'full'
                ],
                'billing_postcode' => [
                    'label' => __('Zip Code', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => false,
                    'enabled' => false,
                    'width' => 'half'
                ]
            ],
            'passenger' => [
                'guest_first_name' => [
                    'label' => __('Name', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'guest_last_name' => [
                    'label' => __('Last Name', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'guest_phone' => [
                    'label' => __('Phone', 'wiwa-checkout'),
                    'type' => 'tel',
                    'required' => false,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'guest_passport' => [
                    'label' => __('Document', 'wiwa-checkout'),
                    'type' => 'text',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'guest_nationality' => [
                    'label' => __('Nationality', 'wiwa-checkout'),
                    'type' => 'select',
                    'required' => true,
                    'enabled' => true,
                    'width' => 'half'
                ],
                'guest_diet' => [
                    'label' => __('Dietary preferences', 'wiwa-checkout'),
                    'type' => 'select',
                    'required' => false,
                    'enabled' => true,
                    'width' => 'half',
                    'options' => [
                        '' => __('None', 'wiwa-checkout'),
                        'vegetarian' => __('Vegetarian', 'wiwa-checkout'),
                        'vegan' => __('Vegan', 'wiwa-checkout'),
                        'gluten_free' => __('Gluten-free', 'wiwa-checkout'),
                        'lactose_free' => __('Lactose-free', 'wiwa-checkout')
                    ]
                ],
                'guest_birthdate' => [
                    'label' => __('Date of Birth', 'wiwa-checkout'),
                    'type' => 'date',
                    'required' => false,
                    'enabled' => false,
                    'width' => 'half'
                ]
            ]
        ];
    }

    /**
     * Get available field types
     */
    public static function get_field_types()
    {
        return [
            'text' => __('Text', 'wiwa-checkout'),
            'email' => __('Email', 'wiwa-checkout'),
            'tel' => __('Phone (Simple)', 'wiwa-checkout'),
            'number' => __('Number', 'wiwa-checkout'),
            'date' => __('Date', 'wiwa-checkout'),
            'select' => __('Selector', 'wiwa-checkout'),
            'textarea' => __('Text area', 'wiwa-checkout'),
            // Specialized types
            'country' => __('Nationality (Typeahead + Flags)', 'wiwa-checkout'),
            'phone' => __('Full Phone (Code + Number)', 'wiwa-checkout'),
            'document' => __('Full Document (Type + Number)', 'wiwa-checkout'),
            // Sub-components
            'phone_code' => __('Area Code Only', 'wiwa-checkout'),
            'document_type' => __('Document Type Only', 'wiwa-checkout'),
        ];
    }

    /**   
     * Update fields
     */
    public static function update_fields($new_fields)
    {
        // Sort fields by order key if present
        foreach ($new_fields as $group => &$group_fields) {
            uasort($group_fields, function ($a, $b) {
                $order_a = isset($a['order']) ? intval($a['order']) : 0;
                $order_b = isset($b['order']) ? intval($b['order']) : 0;
                return $order_a - $order_b;
            });
        }
        update_option('wiwa_checkout_fields', $new_fields);
    }

    /**
     * Add a new field
     */
    public static function add_field($group, $key, $field_data)
    {
        $fields = self::get_fields();
        if (!isset($fields[$group])) {
            $fields[$group] = [];
        }
        $fields[$group][$key] = $field_data;
        self::update_fields($fields);
    }

    /**
     * Remove a field
     */
    public static function remove_field($group, $key)
    {
        $fields = self::get_fields();
        if (isset($fields[$group][$key])) {
            unset($fields[$group][$key]);
            self::update_fields($fields);
        }
    }

    /**
     * Get countries list with codes and flags
     */
    public static function get_countries()
    {
        return [
            'CO' => ['name' => 'Colombia', 'flag' => '🇨🇴', 'phone' => '+57'],
            'MX' => ['name' => 'México', 'flag' => '🇲🇽', 'phone' => '+52'],
            'AR' => ['name' => 'Argentina', 'flag' => '🇦🇷', 'phone' => '+54'],
            'PE' => ['name' => 'Perú', 'flag' => '🇵🇪', 'phone' => '+51'],
            'CL' => ['name' => 'Chile', 'flag' => '🇨🇱', 'phone' => '+56'],
            'EC' => ['name' => 'Ecuador', 'flag' => '🇪🇨', 'phone' => '+593'],
            'VE' => ['name' => 'Venezuela', 'flag' => '🇻🇪', 'phone' => '+58'],
            'PA' => ['name' => 'Panamá', 'flag' => '🇵🇦', 'phone' => '+507'],
            'CR' => ['name' => 'Costa Rica', 'flag' => '🇨🇷', 'phone' => '+506'],
            'GT' => ['name' => 'Guatemala', 'flag' => '🇬🇹', 'phone' => '+502'],
            'BO' => ['name' => 'Bolivia', 'flag' => '🇧🇴', 'phone' => '+591'],
            'PY' => ['name' => 'Paraguay', 'flag' => '🇵🇾', 'phone' => '+595'],
            'UY' => ['name' => 'Uruguay', 'flag' => '🇺🇾', 'phone' => '+598'],
            'HN' => ['name' => 'Honduras', 'flag' => '🇭🇳', 'phone' => '+504'],
            'SV' => ['name' => 'El Salvador', 'flag' => '🇸🇻', 'phone' => '+503'],
            'NI' => ['name' => 'Nicaragua', 'flag' => '🇳🇮', 'phone' => '+505'],
            'DO' => ['name' => 'Rep. Dominicana', 'flag' => '🇩🇴', 'phone' => '+1'],
            'CU' => ['name' => 'Cuba', 'flag' => '🇨🇺', 'phone' => '+53'],
            'PR' => ['name' => 'Puerto Rico', 'flag' => '🇵🇷', 'phone' => '+1'],
            'BR' => ['name' => 'Brasil', 'flag' => '🇧🇷', 'phone' => '+55'],
            'US' => ['name' => 'Estados Unidos', 'flag' => '🇺🇸', 'phone' => '+1'],
            'CA' => ['name' => 'Canadá', 'flag' => '🇨🇦', 'phone' => '+1'],
            'ES' => ['name' => 'España', 'flag' => '🇪🇸', 'phone' => '+34'],
            'FR' => ['name' => 'Francia', 'flag' => '🇫🇷', 'phone' => '+33'],
            'DE' => ['name' => 'Alemania', 'flag' => '🇩🇪', 'phone' => '+49'],
            'IT' => ['name' => 'Italia', 'flag' => '🇮🇹', 'phone' => '+39'],
            'GB' => ['name' => 'Reino Unido', 'flag' => '🇬🇧', 'phone' => '+44'],
            'PT' => ['name' => 'Portugal', 'flag' => '🇵🇹', 'phone' => '+351'],
            'NL' => ['name' => 'Países Bajos', 'flag' => '🇳🇱', 'phone' => '+31'],
            'BE' => ['name' => 'Bélgica', 'flag' => '🇧🇪', 'phone' => '+32'],
            'CH' => ['name' => 'Suiza', 'flag' => '🇨🇭', 'phone' => '+41'],
            'AT' => ['name' => 'Austria', 'flag' => '🇦🇹', 'phone' => '+43'],
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺', 'phone' => '+61'],
            'JP' => ['name' => 'Japón', 'flag' => '🇯🇵', 'phone' => '+81'],
            'CN' => ['name' => 'China', 'flag' => '🇨🇳', 'phone' => '+86'],
            'KR' => ['name' => 'Corea del Sur', 'flag' => '🇰🇷', 'phone' => '+82'],
            'IN' => ['name' => 'India', 'flag' => '🇮🇳', 'phone' => '+91'],
            'RU' => ['name' => 'Rusia', 'flag' => '🇷🇺', 'phone' => '+7'],
            'ZA' => ['name' => 'Sudáfrica', 'flag' => '🇿🇦', 'phone' => '+27'],
            'IL' => ['name' => 'Israel', 'flag' => '🇮🇱', 'phone' => '+972'],
            'AE' => ['name' => 'Emiratos Árabes', 'flag' => '🇦🇪', 'phone' => '+971'],
        ];
    }

    /**
     * Get phone codes for selector
     */
    public static function get_phone_codes()
    {
        $countries = self::get_countries();
        $codes = [];

        foreach ($countries as $code => $country) {
            $codes[] = [
                'code' => $country['phone'],
                'country' => $country['name'],
                'flag' => $country['flag'],
                'iso' => $code
            ];
        }

        // Sort by phone code
        usort($codes, function ($a, $b) {
            return strcmp($a['code'], $b['code']);
        });

        return $codes;
    }

    /**
     * Get available field positions
     */
    public static function get_positions()
    {
        return [
            'full' => __('Full width', 'wiwa-checkout'),
            'left' => __('Left (50%)', 'wiwa-checkout'),
            'right' => __('Right (50%)', 'wiwa-checkout'),
        ];
    }
    /**
     * Get document types
     */
    public static function get_document_types()
    {
        return [
            '' => __('Doc. Type', 'wiwa-checkout'),
            'cc' => __('National ID', 'wiwa-checkout'),
            'passport' => __('Passport', 'wiwa-checkout'),
            'ce' => __('Foreign ID', 'wiwa-checkout'),
            'nit' => __('Tax ID (NIT)', 'wiwa-checkout'),
            'ti' => __('ID Card', 'wiwa-checkout'),
            'other' => __('Other', 'wiwa-checkout')
        ];
    }
}
