<?php
/**
 * Empty Cart Template
 * Modern, branded design with CTA
 *
 * @package WiwaTourCheckout
 * @version 2.11.9
 */

defined('ABSPATH') || exit;
?>

<div class="wiwa-empty-cart">
    <div class="wiwa-empty-cart-content">
        <!-- Icon Wrapper with Centering -->
        <div class="wiwa-empty-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <h2 class="wiwa-empty-title">
            <?php esc_html_e('Tu carrito está vacío', 'wiwa-checkout'); ?>
        </h2>
        
        <p class="wiwa-empty-description">
            <?php esc_html_e('Parece que aún no has agregado ningún tour a tu carrito. ¡Explora nuestros increíbles destinos y vive una aventura única!', 'wiwa-checkout'); ?>
        </p>
        
        <div class="wiwa-empty-cta">
            <a href="<?php echo esc_url(apply_filters('wiwa_empty_cart_redirect', home_url('/tours/'))); ?>" class="wiwa-empty-cart-btn">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span><?php esc_html_e('Explorar Tours', 'wiwa-checkout'); ?></span>
            </a>
        </div>
        
        <div class="wiwa-empty-features">
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">🌴</div>
                <span><?php esc_html_e('Destinos únicos', 'wiwa-checkout'); ?></span>
            </div>
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">🎒</div>
                <span><?php esc_html_e('Tours de aventura', 'wiwa-checkout'); ?></span>
            </div>
            <div class="feature-item">
                <div class="feature-icon" aria-hidden="true">⭐</div>
                <span><?php esc_html_e('Experiencias 5 estrellas', 'wiwa-checkout'); ?></span>
            </div>
        </div>
    </div>
</div>
