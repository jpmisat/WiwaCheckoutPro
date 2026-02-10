<?php
/**
 * Checkout Wrapper Principal
 * Template: checkout-wrapper.php
 */
defined('ABSPATH') || exit;
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
?>
<div class="wiwa-checkout-container">
    <div class="wiwa-checkout-steps">
        <div class="step-connector"></div>
        <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
            <div class="step-circle"><?php echo $step > 1 ? '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>' : '1'; ?></div>
            <span class="step-label">Información</span>
        </div>
        <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
            <div class="step-circle">2</div>
            <span class="step-label">Pago</span>
        </div>
    </div>
    <div class="wiwa-checkout-content">
        <div class="checkout-main-column">
            <?php
            if ($step === 1) {
                include WIWA_CHECKOUT_PATH . 'templates/checkout-step-1.php';
            } elseif ($step === 2) {
                include WIWA_CHECKOUT_PATH . 'templates/checkout-step-2.php';
            }
            ?>
        </div>
        <aside class="checkout-sidebar-column">
            <?php include WIWA_CHECKOUT_PATH . 'templates/order-summary.php'; ?>
        </aside>
    </div>
</div>
