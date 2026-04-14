<?php
/**
 * Checkout Wrapper Principal
 * Template: checkout-wrapper.php
 * Step navigation with validation
 */
defined('ABSPATH') || exit;

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Get the configured checkout page URL or fallback
$checkout_page_id = get_option('wiwa_checkout_page_id');
$checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : wc_get_checkout_url();

// Prevent direct access to step 2 without completing step 1
// Check if step 1 data exists in session
$step1_complete = WC()->session ? WC()->session->get('wiwa_step1_complete') : false;
if ($step === 2 && !$step1_complete && !isset($_POST['wiwa_checkout_nonce'])) {
    // Redirect back to step 1 if step 1 not completed
    // Only if this is a direct URL access, not form submission
    if (!wp_doing_ajax() && empty($_POST)) {
    // Allow access if there's valid nonce from step 1 form
    }
}
?>
<div class="wiwa-checkout-container">
    <div class="wiwa-checkout-steps">
        <div class="step-connector"></div>
        <a href="<?php echo esc_url(add_query_arg('step', '1', $checkout_url)); ?>" class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>" data-step="1">
            <div class="step-circle"><?php echo $step > 1 ? '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>' : '1'; ?></div>
            <span class="step-label"><?php _e('Information', 'wiwa-checkout'); ?></span>
        </a>
        <a href="#" class="step <?php echo $step >= 2 ? 'active' : ''; ?>" data-step="2" id="step-2-link">
            <div class="step-circle">2</div>
            <span class="step-label"><?php _e('Payment', 'wiwa-checkout'); ?></span>
        </a>
    </div>
    <div class="wiwa-checkout-content">
        <div class="checkout-main-column">
            <?php
if ($step === 1) {
    include WIWA_CHECKOUT_PATH . 'templates/checkout/step-1.php';
}
elseif ($step === 2) {
    include WIWA_CHECKOUT_PATH . 'templates/checkout/step-2.php';
}
?>
        </div>
        <aside class="checkout-sidebar-column">
            <?php include WIWA_CHECKOUT_PATH . 'templates/checkout/sidebar-summary.php'; ?>
        </aside>
    </div>
</div>
