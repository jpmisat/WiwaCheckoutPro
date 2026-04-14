<?php
require_once("wp-load.php");

$total_payable = 215000;
echo "total: " . wc_price($total_payable) . "\n";
echo "bad: " . strip_tags(wc_price($total_payable)) . "\n";
echo "format: " . strip_tags(Wiwa_FOX_Integration::format_price($total_payable)) . "\n";
