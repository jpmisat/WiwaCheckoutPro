<?php defined('ABSPATH') or exit; ?>

<form method="post" action="options.php" id="wiwa-integrations-form">
<?php settings_fields('wiwa_checkout_integrations'); ?>

<div class="wiwa-tab-integrations">
    <h3><?php _e('Integraciones', 'wiwa-checkout'); ?></h3>
    
    <!-- GeoIP Section -->
    <div class="wiwa-settings-section">
        <h4><?php _e('Detección Geográfica (MaxMind)', 'wiwa-checkout'); ?></h4>
        
        <div class="wiwa-setting-row">
            <div class="wiwa-setting-label">
                <?php _e('Estrategia de GeoIP', 'wiwa-checkout'); ?>
            </div>
            <div class="wiwa-setting-control">
                <div class="wiwa-radio-row">
                    <input type="radio" name="wiwa_geoip_strategy" id="geoip-wc" value="woocommerce" <?php checked('woocommerce', get_option('wiwa_geoip_strategy', 'woocommerce')); ?>>
                    <label for="geoip-wc">
                        <?php _e('Usar configuración de WooCommerce (Recomendado)', 'wiwa-checkout'); ?>
                        <?php if (Wiwa_GeoIP_Integration::is_wc_maxmind_configured()): ?>
                            <span class="wiwa-status-indicator status-ok">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('MaxMind configurado', 'wiwa-checkout'); ?>
                            </span>
                        <?php
else: ?>
                            <span class="wiwa-status-indicator status-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <?php _e('No configurado', 'wiwa-checkout'); ?>
                                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=integration'); ?>" class="wiwa-link"><?php _e('Configurar →', 'wiwa-checkout'); ?></a>
                            </span>
                        <?php
endif; ?>
                    </label>
                </div>
                <div class="wiwa-radio-row">
                    <input type="radio" name="wiwa_geoip_strategy" id="geoip-yellowtree" value="yellowtree" <?php checked('yellowtree', get_option('wiwa_geoip_strategy')); ?>>
                    <label for="geoip-yellowtree">
                        <?php _e('Usar YellowTree (GeoIP Detect / JS API)', 'wiwa-checkout'); ?>
                    </label>
                </div>
                <div class="wiwa-radio-row">
                    <input type="radio" name="wiwa_geoip_strategy" id="geoip-direct" value="direct" <?php checked('direct', get_option('wiwa_geoip_strategy')); ?>>
                    <label for="geoip-direct"><?php _e('Usar API propia de MaxMind', 'wiwa-checkout'); ?></label>
                </div>
            </div>
        </div>
        
        <!-- MaxMind Direct Fields (hidden by default) -->
        <div class="wiwa-maxmind-direct" style="<?php echo get_option('wiwa_geoip_strategy') !== 'direct' ? 'display: none;' : ''; ?>">
            <hr class="wiwa-divider">
            
            <div class="wiwa-setting-row">
                <div class="wiwa-setting-label">
                    <?php _e('MaxMind Account ID', 'wiwa-checkout'); ?>
                </div>
                <div class="wiwa-setting-control">
                    <input type="text" name="wiwa_maxmind_account_id" class="wiwa-field-input" style="max-width: 300px;" value="<?php echo esc_attr(get_option('wiwa_maxmind_account_id')); ?>" placeholder="123456">
                    <p class="description">
                        <?php _e('Obtén tu Account ID en', 'wiwa-checkout'); ?> 
                        <a href="https://www.maxmind.com/en/accounts/current/license-key" target="_blank" class="wiwa-link"><?php _e('MaxMind Dashboard', 'wiwa-checkout'); ?></a>
                    </p>
                </div>
            </div>
            
            <div class="wiwa-setting-row">
                <div class="wiwa-setting-label">
                    <?php _e('MaxMind License Key', 'wiwa-checkout'); ?>
                </div>
                <div class="wiwa-setting-control">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="password" name="wiwa_maxmind_license_key" class="wiwa-field-input" style="max-width: 300px;" value="<?php echo esc_attr(get_option('wiwa_maxmind_license_key')); ?>" placeholder="xxxxxxxxxxxx">
                        <button type="button" class="button test-maxmind"><?php _e('Probar Conexión', 'wiwa-checkout'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="wiwa-divider">
        
        <div class="wiwa-setting-row">
            <div class="wiwa-setting-label">
                <?php _e('Opciones', 'wiwa-checkout'); ?>
            </div>
            <div class="wiwa-setting-control">
                <div class="wiwa-checkbox-row">
                    <input type="checkbox" name="wiwa_geoip_autocomplete_city" id="geoip-city" value="1" <?php checked(1, get_option('wiwa_geoip_autocomplete_city'), true); ?>>
                    <label for="geoip-city"><?php _e('Autocompletar campo de ciudad al cargar checkout', 'wiwa-checkout'); ?></label>
                </div>
                <div class="wiwa-checkbox-row">
                    <input type="checkbox" name="wiwa_geoip_detect_country" id="geoip-country" value="1" <?php checked(1, get_option('wiwa_geoip_detect_country'), true); ?>>
                    <label for="geoip-country"><?php _e('Detectar país automáticamente', 'wiwa-checkout'); ?></label>
                </div>
            </div>
        </div>
    </div>

    <!-- FOX Currency Section -->
    <div class="wiwa-settings-section">
        <h4><?php _e('Multi-Moneda (FOX Currency Switcher)', 'wiwa-checkout'); ?></h4>
        
        <?php if (Wiwa_FOX_Integration::is_active()): ?>
            <div class="wiwa-notice wiwa-notice-success">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php _e('FOX Currency Switcher detectado y activo', 'wiwa-checkout'); ?></span>
            </div>
            
            <table class="wiwa-data-table">
                <thead>
                    <tr>
                        <th><?php _e('Bandera', 'wiwa-checkout'); ?></th>
                        <th><?php _e('Código', 'wiwa-checkout'); ?></th>
                        <th><?php _e('Nombre', 'wiwa-checkout'); ?></th>
                        <th><?php _e('Símbolo', 'wiwa-checkout'); ?></th>
                        <th><?php _e('Tasa', 'wiwa-checkout'); ?></th>
                        <th><?php _e('Estado', 'wiwa-checkout'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (Wiwa_FOX_Integration::get_currencies() as $code => $currency): ?>
                    <tr>
                        <td><?php echo $currency['flag']; ?></td>
                        <td><strong><?php echo esc_html($code); ?></strong></td>
                        <td><?php echo esc_html($currency['name']); ?></td>
                        <td><?php echo esc_html($currency['symbol']); ?></td>
                        <td><?php echo number_format($currency['rate'], 4); ?></td>
                        <td>
                            <?php if ($currency['is_etalon']): ?>
                                <span class="wiwa-badge wiwa-badge-primary"><?php _e('Base', 'wiwa-checkout'); ?></span>
                            <?php
        else: ?>
                                <span class="wiwa-badge wiwa-badge-success"><?php _e('Activa', 'wiwa-checkout'); ?></span>
                            <?php
        endif; ?>
                        </td>
                    </tr>
                    <?php
    endforeach; ?>
                </tbody>
            </table>
            
            <p class="description" style="margin-top: 16px;">
                <?php _e('Las monedas se configuran en', 'wiwa-checkout'); ?> 
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=woocs'); ?>" class="wiwa-link"><?php _e('FOX Settings →', 'wiwa-checkout'); ?></a>
            </p>
            
            <hr class="wiwa-divider">
            
            <h4 style="margin-top: 0;"><?php _e('Opciones de Visualización en Checkout', 'wiwa-checkout'); ?></h4>
            
            <div class="wiwa-setting-row">
                <div class="wiwa-setting-label">
                    <?php _e('Mostrar selector de moneda', 'wiwa-checkout'); ?>
                </div>
                <div class="wiwa-setting-control">
                    <div class="wiwa-checkbox-row">
                        <input type="checkbox" name="wiwa_show_currency_selector" id="show-currency" value="1" <?php checked(1, get_option('wiwa_show_currency_selector'), true); ?>>
                        <label for="show-currency"><?php _e('Activar selector en checkout', 'wiwa-checkout'); ?></label>
                    </div>
                </div>
            </div>
            
            <div class="wiwa-setting-row">
                <div class="wiwa-setting-label">
                    <?php _e('Estilo del selector', 'wiwa-checkout'); ?>
                </div>
                <div class="wiwa-setting-control">
                    <div class="wiwa-radio-row">
                        <input type="radio" name="wiwa_currency_selector_style" id="style-dropdown" value="dropdown" <?php checked('dropdown', get_option('wiwa_currency_selector_style', 'dropdown')); ?>>
                        <label for="style-dropdown"><?php _e('Dropdown (recomendado para >4 monedas)', 'wiwa-checkout'); ?></label>
                    </div>
                    <div class="wiwa-radio-row">
                        <input type="radio" name="wiwa_currency_selector_style" id="style-buttons" value="buttons" <?php checked('buttons', get_option('wiwa_currency_selector_style')); ?>>
                        <label for="style-buttons"><?php _e('Botones (recomendado para ≤4 monedas)', 'wiwa-checkout'); ?></label>
                    </div>
                </div>
            </div>
            
            <div class="wiwa-setting-row">
                <div class="wiwa-setting-label">
                    <?php _e('Actualización de precios', 'wiwa-checkout'); ?>
                </div>
                <div class="wiwa-setting-control">
                    <div class="wiwa-checkbox-row">
                        <input type="checkbox" name="wiwa_currency_auto_update" id="auto-update" value="1" <?php checked(1, get_option('wiwa_currency_auto_update'), true); ?>>
                        <label for="auto-update"><?php _e('Actualizar precios automáticamente al cambiar moneda', 'wiwa-checkout'); ?></label>
                    </div>
                </div>
            </div>
            
        <?php
else: ?>
            <div class="wiwa-notice wiwa-notice-warning">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong><?php _e('FOX Currency Switcher no detectado', 'wiwa-checkout'); ?></strong>
                    <p style="margin: 8px 0 0 0;">
                        <?php _e('Este plugin requiere FOX Currency Switcher Professional para funcionalidad de multi-moneda.', 'wiwa-checkout'); ?>
                        <a href="https://currency-switcher.com/" target="_blank" class="wiwa-link"><?php _e('Comprar FOX →', 'wiwa-checkout'); ?></a>
                    </p>
                </div>
            </div>
        <?php
endif; ?>
    </div>
    
    <p class="submit">
        <button type="submit" class="wiwa-submit-btn">
            <span class="dashicons dashicons-saved"></span>
            <?php _e('Guardar Cambios', 'wiwa-checkout'); ?>
        </button>
    </p>
</div>
</form>

<script>
jQuery(document).ready(function($) {
    // Toggle MaxMind direct fields
    $('[name="wiwa_geoip_strategy"]').on('change', function() {
        if ($(this).val() === 'direct') {
            $('.wiwa-maxmind-direct').slideDown(200);
        } else {
            $('.wiwa-maxmind-direct').slideUp(200);
        }
    });

    // Test MaxMind connection
    $('.test-maxmind').on('click', function() {
        var $btn = $(this);
        var accountId = $('[name="wiwa_maxmind_account_id"]').val();
        var licenseKey = $('[name="wiwa_maxmind_license_key"]').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wiwa_test_maxmind',
                account_id: accountId,
                license_key: licenseKey,
                nonce: '<?php echo wp_create_nonce("wiwa_admin_nonce"); ?>'
            },
            beforeSend: function() {
                $btn.prop('disabled', true).text('<?php _e("Probando...", "wiwa-checkout"); ?>');
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ <?php _e("Conexión exitosa!", "wiwa-checkout"); ?>\n\n<?php _e("Ciudad detectada:", "wiwa-checkout"); ?> ' + response.data.city);
                } else {
                    alert('❌ Error: ' + response.data.message);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e("Probar Conexión", "wiwa-checkout"); ?>');
            }
        });
    });
});
</script>
