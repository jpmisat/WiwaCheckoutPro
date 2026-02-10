<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_I18n
{
    public static function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'wiwa-checkout',
            false,
            dirname(WIWA_CHECKOUT_BASENAME) . '/languages'
        );
    }
}
