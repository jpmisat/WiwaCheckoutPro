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

        if (!self::is_active() || !is_object($WOOCS)) {
            return wc_price($price);
        }

        // Convert using exchange rate, then format with wc_price
        $converted = self::convert_price(floatval($price));
        return wc_price($converted);
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
     * Hook into OvaTourBooking's price conversion to add WOOCS support.
     *
     * OvaTourBooking's ovatb_convert_price() only handles CURCY and WPML-MC.
     * This filter callback applies the WOOCS exchange rate so prices in the
     * booking modal (total, calendar day-prices, deposits) display correctly
     * in the user-selected currency.
     *
     * @param float $new_price  The (possibly already converted) price.
     * @param float $price      The original base-currency price.
     * @param array $args       Extra args (may contain 'currency').
     * @param bool  $convert    Whether conversion was requested.
     * @return float
     */
    public static function ovatb_convert_price($new_price, $price, $args, $convert)
    {
        // Skip if WOOCS is not active
        if (!self::is_active()) {
            return $new_price;
        }

        // Skip if caller explicitly disabled conversion
        if (!$convert) {
            return $new_price;
        }

        // Use our reliable convert_price method which grabs the rate explicitly
        $converted_price = self::convert_price((float)$price);
        
        // Return whichever is higher between the previously modified new_price (if any) and our explicitly converted price,
        // or just return our converted price.
        return $converted_price;
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
