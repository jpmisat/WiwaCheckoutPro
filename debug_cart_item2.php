<?php
require_once 'wp-load.php';
$cart = WC()->cart;
foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
    echo "Item:\n";
    $product = $cart_item['data'];
    $pay_deposit = $product->get_meta('pay_deposit');
    $deposit_type = $product->get_meta('deposit_type');
    $deposit_value = $product->get_meta('deposit_value');
    $total_payable = $product->get_meta('total_payable');
    
    echo "pay_deposit: " . var_export($pay_deposit, true) . "\n";
    echo "total_payable: " . var_export($total_payable, true) . "\n";
    echo "line_subtotal: " . $cart_item['line_subtotal'] . "\n";
}
