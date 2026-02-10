<?php
/**
 * Checkout Step 1: Detalles de la Reserva
 * Diseño premium con tour accordion y passenger blocks
 * Campos dinámicos desde Ova Tour Booking Guest Information
 */
defined('ABSPATH') || exit;

// Get checkout URL
$checkout_page_id = get_option('wiwa_checkout_page_id');
$checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : wc_get_checkout_url();

// Get countries and phone codes
$countries = WC()->countries->get_countries();
$phone_codes = Wiwa_Typeahead_Data::get_phone_codes();

// Get guest fields from Ova Tour Booking
$guest_fields = Wiwa_Tour_Booking_Integration::get_guest_info_fields();

// Detect tours in cart
$cart_items = WC()->cart->get_cart();
$tours = [];

foreach ($cart_items as $cart_item_key => $cart_item) {
    $product_id = $cart_item['product_id'];
    $post_type = get_post_type($product_id);
    $is_tour = false;

    // Check by post type
    if (strpos($post_type, 'tour') !== false || strpos($post_type, 'ova') !== false) {
        $is_tour = true;
    }

    // Check by cart item keys (Ova Tour Booking)
    if (isset($cart_item['checkin_date']) || isset($cart_item['numberof_guests']) || isset($cart_item['numberof_pax'])) {
        $is_tour = true;
    }

    // Check by booking data
    if (isset($cart_item['ovabrw_booking_data']) || isset($cart_item['booking_data'])) {
        $is_tour = true;
    }

    if ($is_tour) {
        $product = $cart_item['data'];
        $total_pax = Wiwa_Tour_Booking_Integration::get_pax_count($cart_item);
        $tour_date = Wiwa_Tour_Booking_Integration::get_tour_date($cart_item);
        $tour_time = Wiwa_Tour_Booking_Integration::get_tour_time($cart_item);

        $tours[] = [
            'cart_key' => $cart_item_key,
            'product_id' => $product_id,
            'name' => $product->get_name(),
            'thumbnail' => get_the_post_thumbnail($product_id, 'thumbnail'),
            'total_pax' => $total_pax,
            'tour_date' => $tour_date,
            'tour_time' => $tour_time,
        ];
    }
}

if (empty($tours)) {
    echo '<div class="woocommerce-info">' . __('No hay tours en el carrito.', 'wiwa-checkout') . ' <a href="' . esc_url(home_url('/')) . '">' . __('Continuar comprando', 'wiwa-checkout') . '</a></div>';
    return;
}

// Get billing fields from plugin settings
$billing_fields = Wiwa_Fields_Manager::get_fields()['billing'] ?? [];
?>
<form id="wiwa-checkout-step-1" class="wiwa-checkout-form" method="post" action="<?php echo esc_url(add_query_arg('step', '2', $checkout_url)); ?>" novalidate>
    <?php wp_nonce_field('wiwa_checkout_step_1', 'wiwa_step_1_nonce'); ?>
    
    <h2><?php _e('Detalles de tu reserva', 'wiwa-checkout'); ?></h2>
    
    <!-- Contact Section -->
    <div class="contact-section">
        <div class="section-header">
            <h3><?php _e('Datos de contacto', 'wiwa-checkout'); ?></h3>
            <p class="description"><?php _e('Aquí enviaremos la confirmación y podrás gestionar tus reservas', 'wiwa-checkout'); ?></p>
        </div>
        <div class="form-grid form-grid-2">
                        <?php
// Render each billing field dynamically
foreach ($billing_fields as $field_key => $field):
    // Skip disabled fields
    if (empty($field['enabled']))
        continue;

    $is_required = !empty($field['required']);
    $field_type = $field['type'] ?? 'text';
    $field_label = $field['label'] ?? ucfirst(str_replace('billing_', '', $field_key));

    // Determine width/position (Prioritize 'position', fallback to 'width')
    $position = $field['position'] ?? ($field['width'] ?? 'full');

    // Normalize legacy values
    if ($position === 'half' || $position === 'quarter')
        $position = 'left';
    if ($position === 'three-quarter')
        $position = 'full';

    // Specialized checks
    $is_country = ($field_type === 'country');
    $is_phone = ($field_type === 'phone');
    $is_document = ($field_type === 'document');

    $field_options = $field['options'] ?? [];
    $field_placeholder = $field['placeholder'] ?? '';

    // CSS class for width
    $width_class = 'form-field';
    if ($position === 'left')
        $width_class .= ' field-left';
    elseif ($position === 'right')
        $width_class .= ' field-right';
    else
        $width_class .= ' field-full'; // Default to full
?>
            
            <?php if ($is_document): ?>
                <!-- Document Type + Number (combined) -->
                <div class="<?php echo esc_attr($width_class); ?> field-group-document">
                    <label><?php echo esc_html($field_label); ?> 
                        <?php if ($is_required): ?><span class="required">*</span><?php
        endif; ?>
                    </label>
                    <div class="combined-input-group">
                        <select name="<?php echo esc_attr($field_key); ?>_type" id="<?php echo esc_attr($field_key); ?>_type" class="wiwa-select document-type" <?php echo $is_required ? 'required' : ''; ?>>
                            <?php
        $doc_types = Wiwa_Fields_Manager::get_document_types();
        foreach ($doc_types as $opt_value => $opt_label): ?>
                                <option value="<?php echo esc_attr($opt_value); ?>"><?php echo esc_html($opt_label); ?></option>
                            <?php
        endforeach; ?>
                        </select>
                        <input type="text" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" 
                               <?php echo $is_required ? 'required' : ''; ?> 
                               class="wiwa-input" 
                               placeholder="<?php echo esc_attr($field_placeholder ?: __('Número de documento', 'wiwa-checkout')); ?>">
                    </div>
                </div>
                
            <?php
    elseif ($is_phone): ?>
                <!-- Phone Code + Number (combined) -->
                <div class="<?php echo esc_attr($width_class); ?> field-group-phone">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?> 
                        <?php if ($is_required): ?><span class="required">*</span><?php
        endif; ?>
                    </label>
                    <div class="combined-input-group">
                        <select name="<?php echo esc_attr($field_key); ?>_code" class="wiwa-select phone-code-select">
                            <?php foreach ($phone_codes as $phone): ?>
                                <option value="<?php echo esc_attr($phone['code']); ?>" <?php selected($phone['code'], '+57'); ?>>
                                    <?php echo esc_html($phone['flag'] . ' ' . $phone['code']); ?>
                                </option>
                            <?php
        endforeach; ?>
                        </select>
                        <input type="tel" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" 
                               placeholder="<?php echo esc_attr($field_placeholder ?: '3001234567'); ?>" 
                               <?php echo $is_required ? 'required' : ''; ?> 
                               class="wiwa-input">
                    </div>
                </div>
            
            <?php
    elseif ($is_country): ?>
                <!-- Nationality/Country Select with search (Typeahead) -->
                <div class="<?php echo esc_attr($width_class); ?> field-group-country">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?> 
                        <?php if ($is_required): ?><span class="required">*</span><?php
        endif; ?>
                    </label>
                    <select name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" 
                            <?php echo $is_required ? 'required' : ''; ?> class="wiwa-select wiwa-country-select">
                        <option value=""><?php _e('Seleccionar', 'wiwa-checkout'); ?></option>
                        <?php foreach ($countries as $code => $name):
            // Add flag if available in our list
            $flag = '';
            $our_countries = Wiwa_Fields_Manager::get_countries();
            if (isset($our_countries[$code])) {
                $flag = $our_countries[$code]['flag'] . ' ';
            }
?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($code, 'CO'); ?>><?php echo esc_html($flag . $name); ?></option>
                        <?php
        endforeach; ?>
                    </select>
                </div>
            
            <?php
    elseif ($field_type === 'select'): ?>
                <!-- Generic select field -->
                <div class="<?php echo esc_attr($width_class); ?>">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?> 
                        <?php if ($is_required): ?><span class="required">*</span><?php
        endif; ?>
                    </label>
                    <select name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" 
                            <?php echo $is_required ? 'required' : ''; ?> class="wiwa-select">
                        <option value=""><?php _e('Seleccionar', 'wiwa-checkout'); ?></option>
                        <?php foreach ($field_options as $opt_value => $opt_label): ?>
                            <option value="<?php echo esc_attr($opt_value); ?>"><?php echo esc_html($opt_label); ?></option>
                        <?php
        endforeach; ?>
                    </select>
                </div>
            
            <?php
    else: ?>
                <!-- Standard input field -->
                <div class="<?php echo esc_attr($width_class); ?>">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?> 
                        <?php if ($is_required): ?><span class="required">*</span><?php
        endif; ?>
                    </label>
                    <input type="<?php echo esc_attr($field_type === 'tel' ? 'tel' : ($field_type === 'email' ? 'email' : 'text')); ?>" 
                           name="<?php echo esc_attr($field_key); ?>" 
                           id="<?php echo esc_attr($field_key); ?>" 
                           <?php echo $is_required ? 'required' : ''; ?> 
                           class="wiwa-input"
                           placeholder="<?php echo esc_attr($field_placeholder); ?>">
                </div>
            <?php
    endif; ?>
            
            <?php
endforeach; ?>
        </div>
    </div>
    
    <!-- Passengers Section Divider -->
    <div class="section-divider-text"><span><?php _e('Datos de los viajeros', 'wiwa-checkout'); ?></span></div>
    
    <!-- Tours Passengers Section -->
    <div class="tours-passengers-section">
        <div class="section-header">
            <h3><?php _e('*Necesitamos información sobre los pasajeros para poder confirmar estos tours.', 'wiwa-checkout'); ?></h3>
        </div>
        
        <div class="tours-accordion">
            <?php foreach ($tours as $tour_index => $tour): ?>
            <div class="tour-accordion-item <?php echo $tour_index === 0 ? 'active' : ''; ?>" data-tour-index="<?php echo $tour_index; ?>">
                <div class="tour-accordion-header" data-tour="<?php echo $tour_index + 1; ?>">
                    <div class="tour-accordion-header-left">
                        <?php if ($tour['thumbnail']): ?>
                        <div class="tour-thumbnail"><?php echo $tour['thumbnail']; ?></div>
                        <?php
    endif; ?>
                        <div class="tour-info">
                            <h4 class="tour-name">
                                <?php echo esc_html($tour['name']); ?>
                            </h4>
                            <div class="tour-meta">
                                <span class="meta-item">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                                    <?php printf(_n('%d Viajero', '%d Viajeros', $tour['total_pax'], 'wiwa-checkout'), $tour['total_pax']); ?>
                                </span>
                                <?php if (!empty($tour['tour_date'])): ?>
                                <span class="meta-item">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                    <?php echo esc_html($tour['tour_date']); ?>
                                </span>
                                <?php
    endif; ?>
                            </div>
                        </div>
                    </div>
                    <span class="accordion-error-badge"><?php _e('Campos pendientes', 'wiwa-checkout'); ?></span>
                    <button type="button" class="tour-accordion-toggle">
                        <svg class="chevron" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                
                <div class="tour-accordion-body">
                    <div class="tour-accordion-body-inner">
                    <input type="hidden" name="tours[<?php echo $tour_index; ?>][cart_key]" value="<?php echo esc_attr($tour['cart_key']); ?>">
                    <input type="hidden" name="tours[<?php echo $tour_index; ?>][product_id]" value="<?php echo esc_attr($tour['product_id']); ?>">
                    <input type="hidden" name="tours[<?php echo $tour_index; ?>][total_pax]" value="<?php echo esc_attr($tour['total_pax']); ?>">
                    
                    <?php for ($pax = 1; $pax <= $tour['total_pax']; $pax++):
        $guest_index = ($tour_index * 100) + $pax;
?>
                    <div class="passenger-block compact">
                        <div class="passenger-header">
                            <span class="passenger-icon">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1v-1c0-1-1-4-6-4s-6 3-6 4v1a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12z"/></svg>
                            </span>
                            <h5><?php printf(__('Pasajero %d', 'wiwa-checkout'), $pax); ?></h5>
                        </div>
                        
                        <div class="form-grid form-grid-2">
                            <?php foreach ($guest_fields as $field_key => $field):
            // Skip disabled fields (already filtered but double check)
            if (isset($field['enabled']) && !$field['enabled'])
                continue;

            $field_name = "guest_{$field_key}_{$guest_index}";
            $field_id = "guest_{$field_key}_{$guest_index}";
            $field_label = isset($field['label']) ? $field['label'] : ucfirst(str_replace('_', ' ', $field_key));
            $field_type = isset($field['type']) ? $field['type'] : 'text';
            $field_required = !empty($field['required']);
            $field_options = isset($field['options']) ? $field['options'] : [];
            $required_attr = $field_required ? 'data-required="true"' : '';

            // Detect specialized types
            $is_country = ($field_type === 'country' || strpos($field_key, 'nationality') !== false || strpos($field_key, 'country') !== false);
            $is_phone = ($field_type === 'phone' || $field_type === 'tel' || strpos($field_key, 'phone') !== false);
            $is_document_type = ($field_type === 'document_type');
            $is_document = ($field_type === 'document');
?>
                            <div class="form-field">
                                <label for="<?php echo esc_attr($field_id); ?>">
                                    <?php echo esc_html($field_label); ?>
                                    <?php if ($field_required): ?><span class="required">*</span><?php
            endif; ?>
                                </label>
                                
                                <?php if ($is_country): ?>
                                    <select name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_id); ?>" class="wiwa-select wiwa-country-select" <?php echo $required_attr; ?>>
                                        <option value=""><?php _e('Seleccionar', 'wiwa-checkout'); ?></option>
                                        <?php foreach ($countries as $code => $name): ?>
                                            <option value="<?php echo esc_attr($code); ?>" <?php selected($code, 'CO'); ?>><?php echo esc_html($name); ?></option>
                                        <?php
                endforeach; ?>
                                    </select>
                                
                                <?php
            elseif ($is_phone): ?>
                                    <div class="phone-input combined-input-group">
                                        <select name="<?php echo esc_attr($field_name); ?>_code" class="phone-code-select wiwa-select">
                                            <?php foreach ($phone_codes as $phone): ?>
                                                <option value="<?php echo esc_attr($phone['code']); ?>" <?php selected($phone['code'], '+57'); ?>>
                                                    <?php echo esc_html($phone['flag'] . ' ' . $phone['code']); ?>
                                                </option>
                                            <?php
                endforeach; ?>
                                        </select>
                                        <input type="tel" name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_id); ?>" class="wiwa-input" <?php echo $required_attr; ?>>
                                    </div>
                                
                                <?php
            elseif ($is_document): ?>
                                    <div class="document-input combined-input-group">
                                        <select name="<?php echo esc_attr($field_name); ?>_type" class="document-type wiwa-select">
                                            <?php $doc_types = Wiwa_Fields_Manager::get_document_types(); ?>
                                            <?php foreach ($doc_types as $doc_key => $doc_label): ?>
                                                <option value="<?php echo esc_attr($doc_key); ?>"><?php echo esc_html($doc_label); ?></option>
                                            <?php
                endforeach; ?>
                                        </select>
                                        <input type="text" name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_id); ?>" class="wiwa-input" <?php echo $required_attr; ?> placeholder="<?php esc_attr_e('Número de documento', 'wiwa-checkout'); ?>">
                                    </div>
                                
                                <?php
            elseif ($is_document_type): ?>
                                    <select name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_id); ?>" class="wiwa-select" <?php echo $required_attr; ?>>
                                        <option value=""><?php _e('Seleccionar', 'wiwa-checkout'); ?></option>
                                        <?php $doc_types = Wiwa_Fields_Manager::get_document_types(); ?>
                                        <?php foreach ($doc_types as $doc_key => $doc_label): ?>
                                            <option value="<?php echo esc_attr($doc_key); ?>"><?php echo esc_html($doc_label); ?></option>
                                        <?php
                endforeach; ?>
                                    </select>
                                
                                <?php
            elseif ($field_type === 'select' && !empty($field_options)): ?>
                                    <select name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_id); ?>" class="wiwa-select" <?php echo $required_attr; ?>>
                                        <option value=""><?php _e('Seleccionar', 'wiwa-checkout'); ?></option>
                                        <?php foreach ($field_options as $opt_val => $opt_label): ?>
                                            <option value="<?php echo esc_attr($opt_val); ?>"><?php echo esc_html($opt_label); ?></option>
                                        <?php
                endforeach; ?>
                                    </select>
                                
                                <?php
            elseif ($field_type === 'textarea'): ?>
                                    <textarea name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_id); ?>" class="wiwa-input" <?php echo $required_attr; ?>></textarea>
                                
                                <?php
            elseif ($field_type === 'date'): ?>
                                    <input type="date" name="<?php echo esc_attr($field_name); ?>" id="<?php echo esc_attr($field_id); ?>" class="wiwa-input" <?php echo $required_attr; ?>>
                                
                                <?php
            else: ?>
                                    <input type="<?php echo esc_attr($field_type === 'number' ? 'number' : ($field_type === 'email' ? 'email' : 'text')); ?>" 
                                           name="<?php echo esc_attr($field_name); ?>" 
                                           id="<?php echo esc_attr($field_id); ?>" 
                                           class="wiwa-input" 
                                           <?php echo $required_attr; ?>>
                                <?php
            endif; ?>
                                
                                <span class="error-message"><?php _e('Este campo es requerido', 'wiwa-checkout'); ?></span>
                            </div>
                            <?php
        endforeach; ?>
                        </div>
                        
                        <input type="hidden" name="tour_<?php echo $tour_index + 1; ?>_guest_<?php echo $pax; ?>_index" value="<?php echo $guest_index; ?>">
                    </div>
                    <?php
    endfor; ?>
                    </div><!-- .tour-accordion-body-inner -->
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>
    
    <!-- Form Footer -->
    <div class="form-footer">
        <label class="checkbox-label">
            <input type="checkbox" name="accept_terms" id="accept_terms" required>
            <span>
                <?php printf(
    __('Aceptar %sTérminos y Condiciones%s y %sPolítica de Privacidad%s', 'wiwa-checkout'),
    '<a href="' . esc_url(get_permalink(get_option('woocommerce_terms_page_id'))) . '" target="_blank">',
    '</a>',
    '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">',
    '</a>'
); ?>
            </span>
        </label>
        <span class="error-message" id="terms-error"><?php _e('Debes aceptar los términos y condiciones', 'wiwa-checkout'); ?></span>
        
        <button type="submit" class="btn-primary btn-continue">
            <?php _e('Ir al pago', 'wiwa-checkout'); ?>
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</form>

<script>
jQuery(document).ready(function($) {
    // Initialize Select2 for country and phone code selects
    if ($.fn.select2) {
        // Destroy existing instances if any to prevent conflicts
        $('.wiwa-country-select, .phone-code-select, .document-type, select[name*="_type"]').each(function() {
            if ($(this).hasClass("select2-hidden-accessible")) {
                $(this).select2('destroy');
            }
        });

        // General Country Select
        $('.wiwa-country-select').select2({
            width: '100%',
            placeholder: '<?php _e("Seleccionar país", "wiwa-checkout"); ?>',
            allowClear: true,
            dropdownCssClass: 'wiwa-select2-dropdown'
        });

        // Phone Code Select (in composite group)
        $('.phone-code-select').select2({
            width: '110px', 
            selectionCssClass: 'wiwa-composite-select2', // Custom class for targeting
            dropdownCssClass: 'wiwa-select2-dropdown',
            matcher: function(params, data) {
                 if ($.trim(params.term) === '') return data;
                 if (typeof data.text === 'undefined') return null;
                 if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) return data;
                 return null;
            }
        });

        // Document Type Select (in composite group)
        $('select.document-type').select2({
            minimumResultsForSearch: Infinity,
            width: '150px',
            selectionCssClass: 'wiwa-composite-select2', // Custom class for targeting
            dropdownCssClass: 'wiwa-select2-dropdown'
        });
        
        // Other types or fallbacks
        $('select[name*="_type"]:not(.document-type)').select2({
             minimumResultsForSearch: Infinity,
             width: '100%',
             dropdownCssClass: 'wiwa-select2-dropdown'
        });
    }
});
</script>
