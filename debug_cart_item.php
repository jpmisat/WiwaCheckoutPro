<?php
require_once 'wp-load.php';
$cart = WC()->cart;
foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
    echo "Item keys:\n";
    print_r(array_keys($cart_item));
    echo "\nOvaTB Deposit Info:\n";
    if (isset($cart_item['ovatb_deposit'])) print_r($cart_item['ovatb_deposit']);
    if (isset($cart_item['ovatb_amount_deposit'])) print_r($cart_item['ovatb_amount_deposit']);
    if (isset($cart_item['ovatb_full_amount'])) print_r($cart_item['ovatb_full_amount']);
    if (isset($cart_item['ovatb_remaining_amount'])) print_r($cart_item['ovatb_remaining_amount']);
    break;
}
