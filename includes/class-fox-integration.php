<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Wiwa_FOX_Integration
 * 
 * Sincronización total con FOX Currency Switcher Professional
 */
class Wiwa_FOX_Integration
{

    /**
     * Verificar si FOX está activo
     */
    public static function is_active()
    {
        global $WOOCS;
        return isset($WOOCS) && is_object($WOOCS);
    }

    /**
     * Obtener todas las monedas configuradas en FOX
     */
    public static function get_currencies()
    {
        global $WOOCS;

        if (!self::is_active()) {
            return array();
        }

        $currencies = $WOOCS->get_currencies();

        // Agregar banderas (FOX no las incluye por defecto)
        $flags = self::get_currency_flags();
        foreach ($currencies as $code => &$currency) {
            $currency['flag'] = $flags[$code] ?? '🏳️';
        }

        return $currencies;
    }

    /**
     * Obtener moneda actual
     */
    public static function get_current_currency()
    {
        global $WOOCS;

        if (!self::is_active()) {
            return get_woocommerce_currency();
        }

        return $WOOCS->current_currency;
    }

    /**
     * Cambiar moneda programáticamente
     */
    public static function set_currency($currency_code)
    {
        global $WOOCS;

        if (!self::is_active()) {
            return false;
        }

        // Validar que la moneda existe
        $currencies = self::get_currencies();
        if (!isset($currencies[$currency_code])) {
            return false;
        }

        // Cambiar usando método de FOX
        $WOOCS->set_currency($currency_code);

        return true;
    }

    /**
     * Formatear precio con moneda actual
     */
    public static function format_price($price)
    {
        global $WOOCS;

        if (!self::is_active()) {
            return wc_price($price);
        }

        // Usar método de FOX para aplicar conversión y formato
        return $WOOCS->woocommerce_price($price);
    }

    /**
     * Convertir precio a moneda específica
     */
    public static function convert_price($price, $to_currency = null)
    {
        global $WOOCS;

        if (!self::is_active()) {
            return $price;
        }

        if ($to_currency === null) {
            $to_currency = $WOOCS->current_currency;
        }

        $currencies = self::get_currencies();
        if (!isset($currencies[$to_currency])) {
            return $price;
        }

        $rate = floatval($currencies[$to_currency]['rate']);
        return $price * $rate;
    }

    /**
     * Obtener tasa de cambio de una moneda
     */
    public static function get_rate($currency_code)
    {
        $currencies = self::get_currencies();

        if (!isset($currencies[$currency_code])) {
            return 1;
        }

        return floatval($currencies[$currency_code]['rate']);
    }

    /**
     * Obtener símbolo de moneda
     */
    public static function get_symbol($currency_code = null)
    {
        if ($currency_code === null) {
            $currency_code = self::get_current_currency();
        }

        $currencies = self::get_currencies();

        if (!isset($currencies[$currency_code])) {
            return get_woocommerce_currency_symbol($currency_code);
        }

        return $currencies[$currency_code]['symbol'];
    }

    /**
     * Banderas de monedas comunes
     */
    private static function get_currency_flags()
    {
        return array(
            'COP' => '🇨🇴',
            'USD' => '🇺🇸',
            'EUR' => '🇪🇺',
            'GBP' => '🇬🇧',
            'CAD' => '🇨🇦',
            'AUD' => '🇦🇺',
            'MXN' => '🇲🇽',
            'BRL' => '🇧🇷',
            'ARS' => '🇦🇷',
            'CLP' => '🇨🇱',
            'PEN' => '🇵🇪',
            'JPY' => '🇯🇵',
            'CNY' => '🇨🇳',
            'INR' => '🇮🇳',
            'CHF' => '🇨🇭',
        );
    }
}
