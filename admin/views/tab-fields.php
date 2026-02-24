<?php defined('ABSPATH') or exit; ?>

<div class="wiwa-tab-content wiwa-fields-tab">
    <h3><?php _e('Field Manager', 'wiwa-checkout'); ?></h3>
    <p><?php _e('Configure the fields to be displayed in the checkout and passenger form.', 'wiwa-checkout'); ?></p>
    
    <?php
$fields = Wiwa_Fields_Manager::get_fields();
$field_types = Wiwa_Fields_Manager::get_field_types();
$positions = Wiwa_Fields_Manager::get_positions();

// Type icons for badges
$type_icons = [
    'text' => '📝',
    'email' => '📧',
    'tel' => '📞',
    'number' => '#️⃣',
    'date' => '📅',
    'select' => '☰',
    'textarea' => '📄',
    'country' => '🌍',
    'phone_code' => '📱',
    'phone' => '📞',
    'document_type' => '🪪',
    'document' => '📋'
];
?>

    <form id="wiwa-fields-form" method="post">
        <?php wp_nonce_field('wiwa_save_fields', 'wiwa_fields_nonce'); ?>
        
        <div class="wiwa-fields-container">
            <!-- Billing Fields -->
            <div class="wiwa-fields-section">
                <div class="wiwa-section-header">
                    <h4><?php _e('Billing / Contact Fields', 'wiwa-checkout'); ?></h4>
                    <button type="button" class="button wiwa-add-field-btn" data-group="billing">
                        <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Add Field', 'wiwa-checkout'); ?>
                    </button>
                </div>
                <div class="wiwa-info-notice">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('Drag fields to reorder them. Use specialized types (🌍 Country, 📞 Phone, 🪪 Document) for better UX in checkout.', 'wiwa-checkout'); ?>
                </div>
                <table class="widefat wiwa-fields-table" id="billing-fields-table">
                    <thead>
                        <tr>
                            <th class="col-handle"></th>
                            <th class="col-key"><?php _e('Field', 'wiwa-checkout'); ?></th>
                            <th class="col-label"><?php _e('Label', 'wiwa-checkout'); ?></th>
                            <th class="col-placeholder"><?php _e('Placeholder', 'wiwa-checkout'); ?></th>
                            <th class="col-type"><?php _e('Type', 'wiwa-checkout'); ?></th>
                            <th class="col-position"><?php _e('Position', 'wiwa-checkout'); ?></th>
                            <th class="col-required"><?php _e('Req.', 'wiwa-checkout'); ?></th>
                            <th class="col-enabled"><?php _e('Active', 'wiwa-checkout'); ?></th>
                            <th class="col-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields['billing'] as $key => $field):
    $field_type = isset($field['type']) ? $field['type'] : 'text';
    $field_icon = isset($type_icons[$field_type]) ? $type_icons[$field_type] : '📝';
    $is_core = in_array($key, ['billing_first_name', 'billing_last_name', 'billing_email']);
?>
                        <tr data-field-key="<?php echo esc_attr($key); ?>">
                            <td class="col-handle"><span class="dashicons dashicons-move"></span></td>
                            <td class="col-key"><code><?php echo esc_html($key); ?></code></td>
                            <td class="col-label">
                                <input type="text" name="wiwa_fields[billing][<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($field['label']); ?>" class="wiwa-field-input" />
                            </td>
                            <td class="col-placeholder">
                                <input type="text" name="wiwa_fields[billing][<?php echo esc_attr($key); ?>][placeholder]" value="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" class="wiwa-field-input" placeholder="..." />
                            </td>
                            <td class="col-type">
                                <span class="wiwa-type-badge type-<?php echo esc_attr($field_type); ?>"><?php echo $field_icon; ?> <?php echo esc_html($field_types[$field_type] ?? $field_type); ?></span>
                                <input type="hidden" name="wiwa_fields[billing][<?php echo esc_attr($key); ?>][type]" value="<?php echo esc_attr($field_type); ?>">
                                <input type="hidden" name="wiwa_fields[billing][<?php echo esc_attr($key); ?>][order]" value="<?php echo esc_attr($field['order'] ?? 0); ?>" class="wiwa-field-order">
                            </td>
                            <td class="col-position">
                                <select name="wiwa_fields[billing][<?php echo esc_attr($key); ?>][position]" class="wiwa-field-select wiwa-position-select">
                                    <?php
    $current_pos = isset($field['position']) ? $field['position'] : (isset($field['width']) ? $field['width'] : 'full');
    // Map old width values to position
    $pos_map = ['half' => 'left', 'quarter' => 'left', 'three-quarter' => 'full'];
    $current_pos = isset($pos_map[$current_pos]) ? $pos_map[$current_pos] : $current_pos;
?>
                                    <option value="full" <?php selected($current_pos, 'full'); ?>>100%</option>
                                    <option value="left" <?php selected($current_pos, 'left'); ?>>Izq</option>
                                    <option value="right" <?php selected($current_pos, 'right'); ?>>Der</option>
                                </select>
                            </td>
                            <td class="col-required">
                                <label class="wiwa-toggle-small">
                                    <input type="checkbox" name="wiwa_fields[billing][<?php echo esc_attr($key); ?>][required]" value="1" <?php checked(!empty($field['required'])); ?> />
                                    <span class="wiwa-toggle-slider-small"></span>
                                </label>
                            </td>
                            <td class="col-enabled">
                                <label class="wiwa-toggle-small">
                                    <input type="checkbox" name="wiwa_fields[billing][<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked(!empty($field['enabled'])); ?> />
                                    <span class="wiwa-toggle-slider-small"></span>
                                </label>
                            </td>
                            <td class="col-actions">
                                <?php if (!$is_core): ?>
                                <button type="button" class="button button-small wiwa-delete-field" data-key="<?php echo esc_attr($key); ?>" data-group="billing" title="<?php esc_attr_e('Delete', 'wiwa-checkout'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                                <?php
    else: ?>
                                <span class="wiwa-core-field" title="<?php esc_attr_e('Field required by WooCommerce', 'wiwa-checkout'); ?>">
                                    <span class="dashicons dashicons-lock"></span>
                                </span>
                                <?php
    endif; ?>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>

            <br><br>

            <!-- Ova Tour Booking Guest Fields (Read-Only with Required Override) -->
            <div class="wiwa-fields-section">
                <div class="wiwa-section-header">
                    <h4><?php _e('Passenger Fields (Ova Tour Booking)', 'wiwa-checkout'); ?></h4>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=ovatb_setting&section=guest_info')); ?>" class="button" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span> <?php _e('Manage in Tour Booking', 'wiwa-checkout'); ?>
                    </a>
                </div>
                <div class="wiwa-info-notice">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('Fields are managed from WooCommerce > Tour Booking > Guest Information. Use the "Required" switch to control validation at checkout.', 'wiwa-checkout'); ?>
                </div>
                <?php
$ovatb_guest_fields = get_option('ovatb_guest_fields', []);
$wiwa_passenger_required = get_option('wiwa_passenger_required', []);
if (!empty($ovatb_guest_fields)):
?>
                <table class="widefat striped wiwa-fields-table" id="ovatb-fields-table">
                    <thead>
                        <tr>
                            <th class="col-key"><?php _e('Field', 'wiwa-checkout'); ?></th>
                            <th class="col-label"><?php _e('Label', 'wiwa-checkout'); ?></th>
                            <th class="col-type"><?php _e('Type', 'wiwa-checkout'); ?></th>
                            <th class="col-required"><?php _e('Required', 'wiwa-checkout'); ?></th>
                            <th class="col-status"><?php _e('State', 'wiwa-checkout'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ovatb_guest_fields as $key => $field):
        $label = isset($field['label']) ? $field['label'] : ucfirst(str_replace('_', ' ', $key));
        $type = isset($field['type']) ? $field['type'] : 'text';
        $is_required = isset($wiwa_passenger_required[$key]) ? $wiwa_passenger_required[$key] : true;
?>
                        <tr data-field-key="<?php echo esc_attr($key); ?>">
                            <td class="col-key"><code><?php echo esc_html($key); ?></code></td>
                            <td class="col-label"><?php echo esc_html($label); ?></td>
                            <td class="col-type"><span class="wiwa-field-type-badge"><?php echo esc_html($type); ?></span></td>
                            <td class="col-required">
                                <label class="wiwa-toggle-small">
                                    <input type="checkbox" name="wiwa_passenger_required[<?php echo esc_attr($key); ?>]" value="1" <?php checked($is_required); ?> />
                                    <span class="wiwa-toggle-slider-small"></span>
                                </label>
                            </td>
                            <td class="col-status">
                                <span class="wiwa-status-badge active"><?php _e('Active', 'wiwa-checkout'); ?></span>
                            </td>
                        </tr>
                        <?php
    endforeach; ?>
                    </tbody>
                </table>
                <?php
else: ?>
                <div class="wiwa-empty-notice">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('No fields configured in Ova Tour Booking.', 'wiwa-checkout'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=ovatb_setting&section=guest_info')); ?>" target="_blank"><?php _e('Configure now', 'wiwa-checkout'); ?></a>
                </div>
                <?php
endif; ?>
            </div>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary wiwa-save-fields-btn">
                <span class="dashicons dashicons-saved"></span> 
                <?php _e('Save Fields', 'wiwa-checkout'); ?>
            </button>
            <span class="wiwa-save-status"></span>
        </p>
    </form>
</div>
