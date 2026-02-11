<?php
/**
 * Empty Cart Template
 * Modern, branded design with CTA
 */
defined('ABSPATH') || exit;
?>

<div class="wiwa-empty-cart">
    <div class="wiwa-empty-cart-content">
        <div class="wiwa-empty-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5">
                <path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
        </div>
        
        <h2 class="wiwa-empty-title">
            <?php _e('Tu carrito está vacío', 'wiwa-checkout'); ?>
        </h2>
        
        <p class="wiwa-empty-description">
            <?php _e('Parece que aún no has agregado ningún tour a tu carrito. ¡Explora nuestros increíbles destinos y vive una aventura única!', 'wiwa-checkout'); ?>
        </p>
        
        <div class="wiwa-empty-cta">
            <a href="<?php echo esc_url(home_url('/tours/')); ?>" class="wiwa-empty-cart-btn">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <?php _e('Explorar Tours', 'wiwa-checkout'); ?>
            </a>
        </div>
        
        <div class="wiwa-empty-features">
            <div class="feature-item">
                <div class="feature-icon">🌴</div>
                <span><?php _e('Destinos únicos', 'wiwa-checkout'); ?></span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🎒</div>
                <span><?php _e('Tours de aventura', 'wiwa-checkout'); ?></span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">⭐</div>
                <span><?php _e('Experiencias 5 estrellas', 'wiwa-checkout'); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.wiwa-empty-cart {
    max-width: 600px;
    margin: 60px auto;
    padding: 0 20px;
    text-align: center;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}
.wiwa-empty-cart-content {
    background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
    border-radius: 24px;
    padding: 60px 40px;
    border: 1px solid #E5E7EB;
}
.wiwa-empty-icon {
    margin-bottom: 24px;
}
.wiwa-empty-title {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 16px;
}
.wiwa-empty-description {
    font-size: 16px;
    color: #6B7280;
    line-height: 1.7;
    margin: 0 0 32px;
}
.wiwa-empty-cta .wiwa-empty-cart-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #1E3A2B 0%, #2D5641 100%);
    color: white;
    padding: 16px 32px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(30, 58, 43, 0.3);
}
.wiwa-empty-cta .wiwa-empty-cart-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(30, 58, 43, 0.4);
    color: #ffffff;
}
.wiwa-empty-features {
    display: flex;
    justify-content: center;
    gap: 32px;
    margin-top: 48px;
    flex-wrap: wrap;
}
.feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.feature-icon {
    font-size: 32px;
}
.feature-item span {
    font-size: 14px;
    color: #6B7280;
    font-weight: 500;
}
@media (max-width: 480px) {
    .wiwa-empty-cart-content {
        padding: 40px 24px;
    }
    .wiwa-empty-title {
        font-size: 24px;
    }
    .wiwa-empty-features {
        gap: 20px;
    }
}
</style>
