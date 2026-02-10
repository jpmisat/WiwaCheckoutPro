<?php defined('ABSPATH') or exit; ?>

<div class="wiwa-tab-general">
    <h3><?php _e('Configuración General', 'wiwa-checkout'); ?></h3>
    
    <form id="wiwa-general-settings" method="post">
        <?php wp_nonce_field('wiwa_save_settings', 'wiwa_settings_nonce'); ?>
        
        <!-- Activar Checkout Section -->
        <div class="wiwa-settings-section">
            <h4><?php _e('Estado del Checkout', 'wiwa-checkout'); ?></h4>
            
            <div class="wiwa-setting-row">
                <div class="wiwa-setting-label">
                    <?php _e('Activar Checkout Wiwa', 'wiwa-checkout'); ?>
                </div>
                <div class="wiwa-setting-control">
                    <label class="wiwa-toggle-large">
                        <input type="checkbox" name="wiwa_checkout_enabled" value="1" <?php checked(1, get_option('wiwa_checkout_enabled'), true); ?>>
                        <span class="wiwa-toggle-slider-large"></span>
                    </label>
                    <p class="description">
                        <?php _e('Activa el checkout personalizado de Wiwa Tours. Esto reemplazará el checkout y carrito de WooCommerce.', 'wiwa-checkout'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Shortcode Section -->
        <div class="wiwa-settings-section">
            <h4><?php _e('Shortcode', 'wiwa-checkout'); ?></h4>
            
            <div class="wiwa-shortcode-card">
                <code id="wiwa-shortcode">[wiwa_checkout]</code>
                <button type="button" class="wiwa-copy-btn" id="copy-shortcode" title="<?php esc_attr_e('Copiar', 'wiwa-checkout'); ?>">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </div>
            <p class="description" style="margin-top: 12px;">
                <?php _e('Usa este shortcode para mostrar el checkout en cualquier página.', 'wiwa-checkout'); ?>
            </p>
        </div>
        
        <!-- Página de Checkout Section -->
        <div class="wiwa-settings-section">
            <h4><?php _e('Página de Checkout', 'wiwa-checkout'); ?></h4>
            
            <div class="wiwa-setting-row">
                <div class="wiwa-setting-label">
                    <?php _e('Página del Checkout', 'wiwa-checkout'); ?>
                </div>
                <div class="wiwa-setting-control">
                    <?php
wp_dropdown_pages([
    'name' => 'wiwa_checkout_page_id',
    'selected' => get_option('wiwa_checkout_page_id'),
    'show_option_none' => __('— Seleccionar —', 'wiwa-checkout'),
    'option_none_value' => '',
    'class' => 'wiwa-select-styled'
]);
?>
                    <p class="description">
                        <?php _e('Página donde se mostrará el checkout. Asegúrate de que contenga el shortcode [wiwa_checkout].', 'wiwa-checkout'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <p class="submit">
            <button type="submit" class="wiwa-submit-btn">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Guardar Cambios', 'wiwa-checkout'); ?>
            </button>
            <span class="wiwa-save-status"></span>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode
    $('#copy-shortcode').on('click', function() {
        navigator.clipboard.writeText('[wiwa_checkout]').then(function() {
            var $btn = $('#copy-shortcode');
            $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
            setTimeout(function() {
                $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
            }, 1500);
        });
    });
    
    // AJAX save - unified toggle affects cart + checkout override
    $('#wiwa-general-settings').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $status = $('.wiwa-save-status');
        var $btn = $form.find('.wiwa-submit-btn');
        var isEnabled = $('[name="wiwa_checkout_enabled"]').is(':checked') ? 1 : 0;
        
        $btn.prop('disabled', true);
        $status.text('<?php _e("Guardando...", "wiwa-checkout"); ?>').css('color', '#6B7280');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wiwa_save_general_settings',
                nonce: $('#wiwa_settings_nonce').val(),
                wiwa_checkout_enabled: isEnabled,
                wiwa_override_wc_checkout: isEnabled,
                wiwa_override_wc_cart: isEnabled,
                wiwa_checkout_page_id: $('[name="wiwa_checkout_page_id"]').val()
            },
            success: function(response) {
                $status.text('<?php _e("¡Guardado!", "wiwa-checkout"); ?>').css('color', '#10B981');
                setTimeout(function() { $status.text(''); }, 2500);
            },
            error: function() {
                $status.text('<?php _e("Error al guardar", "wiwa-checkout"); ?>').css('color', '#EF4444');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
