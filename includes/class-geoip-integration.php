<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_GeoIP_Integration
{

    /**
     * Detectar ciudad del usuario
     * 
     * @return array {
     *     @type string $city      Nombre de la ciudad
     *     @type string $state     Estado/Provincia
     *     @type string $country   Código ISO del país
     * }
     */
    public static function detect_city()
    {
        // Estrategia 1: WooCommerce MaxMind
        if (self::is_wc_maxmind_configured()) {
            return self::get_city_from_woocommerce();
        }

        // Estrategia 2: MaxMind API Directa
        if (self::is_plugin_maxmind_configured()) {
            return self::get_city_from_maxmind_api();
        }

        // Fallback: Retornar vacío
        return array(
            'city' => '',
            'state' => '',
            'country' => '',
        );
    }

    /**
     * Verificar si WooCommerce tiene MaxMind configurado
     */
    public static function is_wc_maxmind_configured()
    {
        // WooCommerce >= 3.9 stores license key in woocommerce_maxmind_geolocation_settings
        $wc_geoip_settings = get_option('woocommerce_maxmind_geolocation_settings');
        $license_key = '';
        
        if (is_array($wc_geoip_settings) && !empty($wc_geoip_settings['license_key'])) {
            $license_key = $wc_geoip_settings['license_key'];
        } else {
            // Fallback to older versions
            $license_key = get_option('woocommerce_maxmind_license_key');
        }

        return !empty($license_key) && class_exists('WC_Geolocation');
    }

    /**
     * Obtener ciudad desde WooCommerce
     */
    private static function get_city_from_woocommerce()
    {
        try {
            $location = WC_Geolocation::geolocate_ip();

            return array(
                'city' => $location['city'] ?? '',
                'state' => $location['state'] ?? '',
                'country' => $location['country'] ?? '',
            );
        }
        catch (Exception $e) {
            error_log('Wiwa GeoIP Error: ' . $e->getMessage());
            return array('city' => '', 'state' => '', 'country' => '');
        }
    }

    /**
     * Verificar si el plugin tiene MaxMind configurado
     */
    private static function is_plugin_maxmind_configured()
    {
        $license_key = get_option('wiwa_maxmind_license_key');
        $account_id = get_option('wiwa_maxmind_account_id');
        return !empty($license_key) && !empty($account_id);
    }

    /**
     * Obtener ciudad desde MaxMind API directa
     */
    private static function get_city_from_maxmind_api()
    {
        $license_key = get_option('wiwa_maxmind_license_key');
        $account_id = get_option('wiwa_maxmind_account_id');

        $ip = self::get_user_ip();

        // MaxMind GeoIP2 Precision Web Services
        $url = "https://geoip.maxmind.com/geoip/v2.1/city/{$ip}";

        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_id . ':' . $license_key),
                'Accept' => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            error_log('Wiwa MaxMind API Error: ' . $response->get_error_message());
            return array('city' => '', 'state' => '', 'country' => '');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['city'])) {
            return array('city' => '', 'state' => '', 'country' => '');
        }

        // Preferir nombres en español
        $city = $data['city']['names']['es'] ?? $data['city']['names']['en'] ?? '';
        $state = $data['subdivisions'][0]['names']['es'] ?? $data['subdivisions'][0]['names']['en'] ?? '';
        $country = $data['country']['iso_code'] ?? '';

        return compact('city', 'state', 'country');
    }

    /**
     * Obtener IP real del usuario (considerando proxies)
     */
    private static function get_user_ip()
    {
        $headers_to_check = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP', // Nginx
            'HTTP_X_FORWARDED_FOR', // Proxy
            'HTTP_CLIENT_IP', // ISP Proxy
            'REMOTE_ADDR', // Directo
        );

        foreach ($headers_to_check as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validar que sea IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
