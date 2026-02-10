<?php

if (!defined('ABSPATH')) {
    exit;
}

class Wiwa_Thankyou_Handler
{

    public function __construct()
    {
        add_action('woocommerce_thankyou', [$this, 'custom_thankyou_page'], 10, 1);
    }

    public function custom_thankyou_page($order_id)
    {
        if (!$order_id)
            return;

        $order = wc_get_order($order_id);

        // Load custom template
        // Note: WooCommerce hooks 'woocommerce_thankyou' inside its own template. 
        // To fully replace it, we might need a template override or redirection.
        // For this task, we will just output content at the top or hook into content.

        // Simple approach: Output custom message
?>
        <div class="wiwa-thankyou-message">
            <h2>¡Gracias por reservar con Wiwa Tours!</h2>
            <p>Hemos recibido tu reserva #<?php echo $order->get_order_number(); ?>.</p>
        </div>
        <?php
    }
}
