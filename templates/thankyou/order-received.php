<?php
/**
 * Custom Thank You / Order Received Page
 * Branded design with order details, tour info, and status colors
 */
defined('ABSPATH') || exit;

$order = wc_get_order($order_id);
if (!$order) {
    return;
}

$order_status = $order->get_status();
$status_colors = [
    'pending' => ['bg' => '#FEF3C7', 'color' => '#92400E', 'icon' => '⏳'],
    'processing' => ['bg' => '#DBEAFE', 'color' => '#1E40AF', 'icon' => '⚙️'],
    'on-hold' => ['bg' => '#FED7AA', 'color' => '#C2410C', 'icon' => '⏸️'],
    'completed' => ['bg' => '#D1FAE5', 'color' => '#065F46', 'icon' => '✅'],
    'cancelled' => ['bg' => '#FEE2E2', 'color' => '#991B1B', 'icon' => '❌'],
    'refunded' => ['bg' => '#E5E7EB', 'color' => '#374151', 'icon' => '↩️'],
    'failed' => ['bg' => '#FEE2E2', 'color' => '#991B1B', 'icon' => '⚠️'],
];
$status_style = $status_colors[$order_status] ?? ['bg' => '#E5E7EB', 'color' => '#374151', 'icon' => '📋'];
$status_label = wc_get_order_status_name($order_status);
?>

<div class="wiwa-thankyou-container">
    <!-- Header -->
    <div class="wiwa-thankyou-header">
        <div class="wiwa-thankyou-icon">
            <?php if ($order_status === 'completed' || $order_status === 'processing'): ?>
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            <?php
else: ?>
                <span style="font-size: 48px;"><?php echo $status_style['icon']; ?></span>
            <?php
endif; ?>
        </div>
        <h1 class="wiwa-thankyou-title">
            <?php if ($order_status === 'completed' || $order_status === 'processing'): ?>
                <?php _e('Thank you for your booking!', 'wiwa-checkout'); ?>
            <?php
else: ?>
                <?php _e('Order status', 'wiwa-checkout'); ?>
            <?php
endif; ?>
        </h1>
        <p class="wiwa-thankyou-subtitle">
            <?php printf(__('Order #%s', 'wiwa-checkout'), $order->get_order_number()); ?>
        </p>
    </div>

    <!-- Status Badge -->
    <div class="wiwa-status-badge" style="background: <?php echo $status_style['bg']; ?>; color: <?php echo $status_style['color']; ?>;">
        <span class="status-icon"><?php echo $status_style['icon']; ?></span>
        <span class="status-text"><?php echo esc_html($status_label); ?></span>
    </div>

    <!-- Order Summary -->
    <div class="wiwa-thankyou-section">
        <h3><?php _e('Summary of your booking', 'wiwa-checkout'); ?></h3>
        <div class="wiwa-order-items">
            <?php foreach ($order->get_items() as $item_id => $item):
    $product = $item->get_product();
    $product_id = $item->get_product_id();
    $thumbnail = get_the_post_thumbnail($product_id, 'thumbnail');
    $meta_data = $item->get_meta_data();
?>
            <div class="wiwa-order-item">
                <?php if ($thumbnail): ?>
                    <div class="item-thumbnail"><?php echo $thumbnail; ?></div>
                <?php
    endif; ?>
                <div class="item-details">
                    <h4 class="item-name"><?php echo esc_html($item->get_name()); ?></h4>
                    <div class="item-meta">
                        <?php foreach ($meta_data as $meta):
        if (strpos($meta->key, '_') === 0)
            continue; // Skip private meta
?>
                            <span class="meta-item">
                                <strong><?php echo esc_html($meta->key); ?>:</strong> 
                                <?php echo esc_html($meta->value); ?>
                            </span>
                        <?php
    endforeach; ?>
                    </div>
                    <div class="item-price">
                        <?php echo $order->get_formatted_line_subtotal($item); ?>
                    </div>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>

    <!-- Totals -->
    <div class="wiwa-thankyou-section wiwa-totals-section">
        <table class="wiwa-totals-table">
            <tr>
                <td><?php _e('Subtotal', 'wiwa-checkout'); ?></td>
                <td><?php echo wc_price($order->get_subtotal()); ?></td>
            </tr>
            <?php if ($order->get_discount_total() > 0): ?>
            <tr class="discount-row">
                <td><?php _e('Discount', 'wiwa-checkout'); ?></td>
                <td>-<?php echo wc_price($order->get_discount_total()); ?></td>
            </tr>
            <?php
endif; ?>
            <?php if ($order->get_total_tax() > 0): ?>
            <tr>
                <td><?php _e('Taxes', 'wiwa-checkout'); ?></td>
                <td><?php echo wc_price($order->get_total_tax()); ?></td>
            </tr>
            <?php
endif; ?>
            <tr class="total-row">
                <td><strong><?php _e('Total', 'wiwa-checkout'); ?></strong></td>
                <td><strong><?php echo $order->get_formatted_order_total(); ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Customer Details -->
    <div class="wiwa-thankyou-section wiwa-customer-section">
        <div class="customer-column">
            <h4><?php _e('Contact details', 'wiwa-checkout'); ?></h4>
            <p>
                <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?><br>
                <?php echo esc_html($order->get_billing_email()); ?><br>
                <?php echo esc_html($order->get_billing_phone()); ?>
            </p>
        </div>
        <div class="customer-column">
            <h4><?php _e('Payment method', 'wiwa-checkout'); ?></h4>
            <p><?php echo wp_kses_post($order->get_payment_method_title()); ?></p>
        </div>
    </div>

    <!-- Confirmation Message -->
    <div class="wiwa-confirmation-message">
        <p>
            <?php _e('We have sent a confirmation email to', 'wiwa-checkout'); ?> 
            <strong><?php echo esc_html($order->get_billing_email()); ?></strong>
        </p>
        <p class="small-text">
            <?php _e('If you have any questions, contact us via WhatsApp or email.', 'wiwa-checkout'); ?>
        </p>
    </div>

    <!-- CTA -->
    <div class="wiwa-thankyou-cta">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <?php _e('Explore more tours', 'wiwa-checkout'); ?>
        </a>
    </div>
</div>

<style>
.wiwa-thankyou-container {
    max-width: 700px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}
.wiwa-thankyou-header {
    text-align: center;
    margin-bottom: 32px;
}
.wiwa-thankyou-icon {
    margin-bottom: 20px;
}
.wiwa-thankyou-title {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px;
}
.wiwa-thankyou-subtitle {
    font-size: 16px;
    color: #6B7280;
    margin: 0;
}
.wiwa-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
    margin: 0 auto 32px;
    display: flex;
    justify-content: center;
    width: fit-content;
}
.wiwa-thankyou-section {
    background: #F9FAFB;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
}
.wiwa-thankyou-section h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 16px;
    color: #111827;
}
.wiwa-order-items {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.wiwa-order-item {
    display: flex;
    gap: 16px;
    background: white;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #E5E7EB;
}
.item-thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.item-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.item-name {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px;
    color: #111827;
}
.item-meta {
    font-size: 13px;
    color: #6B7280;
    margin-bottom: 8px;
}
.item-meta .meta-item {
    display: block;
    margin-bottom: 4px;
}
.item-price {
    font-size: 16px;
    font-weight: 700;
    color: #1E3A2B;
}
.wiwa-totals-table {
    width: 100%;
    border-collapse: collapse;
}
.wiwa-totals-table td {
    padding: 10px 0;
    border-bottom: 1px solid #E5E7EB;
}
.wiwa-totals-table td:last-child {
    text-align: right;
}
.wiwa-totals-table .discount-row td {
    color: #10B981;
}
.wiwa-totals-table .total-row td {
    font-size: 18px;
    border-bottom: none;
    padding-top: 16px;
}
.wiwa-customer-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
.customer-column h4 {
    font-size: 14px;
    font-weight: 600;
    color: #6B7280;
    margin: 0 0 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.customer-column p {
    font-size: 15px;
    color: #111827;
    margin: 0;
    line-height: 1.6;
}
.wiwa-confirmation-message {
    text-align: center;
    padding: 24px;
    background: #F0FDF4;
    border-radius: 12px;
    margin-bottom: 24px;
}
.wiwa-confirmation-message p {
    margin: 0 0 8px;
    color: #065F46;
}
.wiwa-confirmation-message .small-text {
    font-size: 13px;
    color: #6B7280;
}
.wiwa-thankyou-cta {
    text-align: center;
}
.wiwa-thankyou-cta .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #1E3A2B;
    color: white;
    padding: 14px 28px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s;
}
.wiwa-thankyou-cta .btn-primary:hover {
    background: #152A1F;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(30,58,43,0.2);
}
@media (max-width: 600px) {
    .wiwa-customer-section {
        grid-template-columns: 1fr;
    }
    .wiwa-order-item {
        flex-direction: column;
    }
    .item-thumbnail {
        width: 100%;
        height: 150px;
    }
}
</style>
