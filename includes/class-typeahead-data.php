<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Typeahead_Data
{

    public static function get_countries()
    {
        if (function_exists('WC') && WC()->countries) {
            return WC()->countries->get_countries();
        }
        return [];
    }

    /**
     * Get phone codes for country selector
     * Returns array with 'code', 'country', 'flag' keys
     */
    public static function get_phone_codes()
    {
        // Return array with proper structure
        return [
            ['code' => '+57', 'country' => 'Colombia', 'flag' => '🇨🇴'],
            ['code' => '+1', 'country' => 'USA/Canada', 'flag' => '🇺🇸'],
            ['code' => '+52', 'country' => 'México', 'flag' => '🇲🇽'],
            ['code' => '+34', 'country' => 'España', 'flag' => '🇪🇸'],
            ['code' => '+54', 'country' => 'Argentina', 'flag' => '🇦🇷'],
            ['code' => '+55', 'country' => 'Brasil', 'flag' => '🇧🇷'],
            ['code' => '+56', 'country' => 'Chile', 'flag' => '🇨🇱'],
            ['code' => '+51', 'country' => 'Perú', 'flag' => '🇵🇪'],
            ['code' => '+593', 'country' => 'Ecuador', 'flag' => '🇪🇨'],
            ['code' => '+507', 'country' => 'Panamá', 'flag' => '🇵🇦'],
            ['code' => '+506', 'country' => 'Costa Rica', 'flag' => '🇨🇷'],
            ['code' => '+58', 'country' => 'Venezuela', 'flag' => '🇻🇪'],
            ['code' => '+502', 'country' => 'Guatemala', 'flag' => '🇬🇹'],
            ['code' => '+503', 'country' => 'El Salvador', 'flag' => '🇸🇻'],
            ['code' => '+504', 'country' => 'Honduras', 'flag' => '🇭🇳'],
            ['code' => '+505', 'country' => 'Nicaragua', 'flag' => '🇳🇮'],
            ['code' => '+591', 'country' => 'Bolivia', 'flag' => '🇧🇴'],
            ['code' => '+595', 'country' => 'Paraguay', 'flag' => '🇵🇾'],
            ['code' => '+598', 'country' => 'Uruguay', 'flag' => '🇺🇾'],
            ['code' => '+53', 'country' => 'Cuba', 'flag' => '🇨🇺'],
            ['code' => '+1809', 'country' => 'Rep. Dominicana', 'flag' => '🇩🇴'],
            ['code' => '+33', 'country' => 'France', 'flag' => '🇫🇷'],
            ['code' => '+49', 'country' => 'Germany', 'flag' => '🇩🇪'],
            ['code' => '+44', 'country' => 'UK', 'flag' => '🇬🇧'],
            ['code' => '+39', 'country' => 'Italy', 'flag' => '🇮🇹'],
            ['code' => '+41', 'country' => 'Switzerland', 'flag' => '🇨🇭'],
            ['code' => '+31', 'country' => 'Netherlands', 'flag' => '🇳🇱'],
            ['code' => '+32', 'country' => 'Belgium', 'flag' => '🇧🇪'],
            ['code' => '+351', 'country' => 'Portugal', 'flag' => '🇵🇹'],
            ['code' => '+43', 'country' => 'Austria', 'flag' => '🇦🇹'],
            ['code' => '+48', 'country' => 'Poland', 'flag' => '🇵🇱'],
            ['code' => '+46', 'country' => 'Sweden', 'flag' => '🇸🇪'],
            ['code' => '+47', 'country' => 'Norway', 'flag' => '🇳🇴'],
            ['code' => '+45', 'country' => 'Denmark', 'flag' => '🇩🇰'],
            ['code' => '+358', 'country' => 'Finland', 'flag' => '🇫🇮'],
            ['code' => '+61', 'country' => 'Australia', 'flag' => '🇦🇺'],
            ['code' => '+64', 'country' => 'New Zealand', 'flag' => '🇳🇿'],
            ['code' => '+81', 'country' => 'Japan', 'flag' => '🇯🇵'],
            ['code' => '+82', 'country' => 'South Korea', 'flag' => '🇰🇷'],
            ['code' => '+86', 'country' => 'China', 'flag' => '🇨🇳'],
            ['code' => '+91', 'country' => 'India', 'flag' => '🇮🇳'],
            ['code' => '+972', 'country' => 'Israel', 'flag' => '🇮🇱'],
            ['code' => '+971', 'country' => 'UAE', 'flag' => '🇦🇪'],
            ['code' => '+966', 'country' => 'Saudi Arabia', 'flag' => '🇸🇦'],
            ['code' => '+27', 'country' => 'South Africa', 'flag' => '🇿🇦'],
        ];
    }
}
