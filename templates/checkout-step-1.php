<?php
/**
 * Checkout Step 1: Detalles de la Reserva
 * Con campos mapeados correctamente a Travel Tour Booking
 */
defined('ABSPATH') || exit;

$cart_items = WC()->cart->get_cart();
$tours = [];

// Detectar tours con múltiples métodos
foreach ($cart_items as $cart_item_key => $cart_item) {
    $product_id = $cart_item['product_id'];
    $post_type = get_post_type($product_id);
    $is_tour = false;

    if (strpos($post_type, 'tour') !== false || strpos($post_type, 'ova') !== false) {
        $is_tour = true;
    }

    if (isset($cart_item['ovabrw_booking_data']) || isset($cart_item['booking_data'])) {
        $is_tour = true;
    }

    if (!$is_tour) {
        $product_meta = get_post_meta($product_id);
        foreach ($product_meta as $meta_key => $meta_value) {
            if (strpos($meta_key, 'ovabrw') !== false || strpos($meta_key, 'tour') !== false) {
                $is_tour = true;
                break;
            }
        }
    }

    if ($is_tour) {
        $booking_data = isset($cart_item['ovabrw_booking_data']) ? $cart_item['ovabrw_booking_data'] : [];

        $adults = isset($booking_data['ovabrw_adults']) ? $booking_data['ovabrw_adults'] :
            (isset($booking_data['adults']) ? $booking_data['adults'] : 1);
        $children = isset($booking_data['ovabrw_children']) ? $booking_data['ovabrw_children'] :
            (isset($booking_data['children']) ? $booking_data['children'] : 0);
        $tour_date = isset($booking_data['ovabrw_pickup_date']) ? $booking_data['ovabrw_pickup_date'] :
            (isset($booking_data['date']) ? $booking_data['date'] : '');
        $tour_time = isset($booking_data['ovabrw_pickup_time']) ? $booking_data['ovabrw_pickup_time'] :
            (isset($booking_data['time']) ? $booking_data['time'] : '');

        $tours[] = [
            'cart_key' => $cart_item_key,
            'product_id' => $product_id,
            'name' => $cart_item['data']->get_name(),
            'thumbnail' => get_the_post_thumbnail($product_id, 'thumbnail'),
            'adults' => $adults,
            'children' => $children,
            'tour_date' => $tour_date,
            'tour_time' => $tour_time,
            'total_pax' => $adults + $children
        ];
    }
}

if (empty($tours)) {
    echo '<div class="woocommerce-info">No hay tours en el carrito. <a href="' . esc_url(home_url('/')) . '">Continuar comprando</a></div>';
    return;
}
?>
<form id="wiwa-checkout-step-1" class="wiwa-checkout-form" method="post" action="<?php echo esc_url(home_url('/checkout-wiwa/?step=2')); ?>" novalidate>
    <h2>Detalles de tu reserva</h2>
    <div class="contact-section">
        <div class="section-header">
            <h3>Datos de contacto</h3>
            <p class="description">Aquí enviaremos la confirmación y podrás gestionar tus reservas</p>
        </div>
        <div class="form-grid form-grid-2">
            <div class="form-field">
                <label for="billing_first_name">Nombre <span class="required">*</span></label>
                <input type="text" name="billing_first_name" id="billing_first_name" required class="wiwa-input" placeholder="Juan Pablo">
                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Este campo es requerido</span>
            </div>
            <div class="form-field">
                <label for="billing_last_name">Apellido <span class="required">*</span></label>
                <input type="text" name="billing_last_name" id="billing_last_name" required class="wiwa-input" placeholder="Misat Carvajal">
                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Este campo es requerido</span>
            </div>
            <div class="form-field">
                <label for="billing_email">Correo electrónico <span class="required">*</span></label>
                <input type="email" name="billing_email" id="billing_email" required class="wiwa-input" placeholder="juanpablo@gmail.com">
                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Ingresa un email válido</span>
            </div>
            <div class="form-field">
                <label for="billing_country">Nacionalidad <span class="required">*</span></label>
                <select name="billing_country" id="billing_country" required class="wiwa-select">
                    <option value="">Seleccionar</option>
                    <?php foreach (WC()->countries->get_countries() as $code => $name) {
    printf('<option value="%s" %s>%s</option>', esc_attr($code), $code === 'CO' ? 'selected' : '', esc_html($name));
}?>
                </select>
                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Selecciona tu nacionalidad</span>
            </div>
            <div class="form-field">
                <label for="billing_city">Ciudad</label>
                <input type="text" name="billing_city" id="billing_city" class="wiwa-input" placeholder="">
            </div>
            <div class="form-field">
                <label for="billing_document">Documento <span class="required">*</span></label>
                <div class="combined-input-group">
                    <select name="billing_document_type" id="billing_document_type" class="document-type wiwa-select">
                        <option value="cedula" selected>Cédula de Ciudadanía</option>
                        <option value="cedula_ext">Cédula de Extranjería</option>
                        <option value="passport">Pasaporte</option>
                        <option value="nit">NIT</option>
                        <option value="other">Otro</option>
                    </select>
                    <input type="text" name="billing_document" id="billing_document" required class="wiwa-input" placeholder="">
                </div>
                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Este campo es requerido</span>
            </div>
            <div class="form-field">
                <label for="billing_phone">Teléfono <span class="required">*</span></label>
                <div class="combined-input-group">
                    <select name="billing_phone_code" id="billing_phone_code" class="phone-code-select wiwa-select">
                        <option value="+57" data-flag="🇨🇴" selected>🇨🇴 +57</option>
                        <option value="+1" data-flag="🇺🇸">🇺🇸 +1</option>
                        <option value="+52" data-flag="🇲🇽">🇲🇽 +52</option>
                        <option value="+34" data-flag="🇪🇸">🇪🇸 +34</option>
                        <option value="+54" data-flag="🇦🇷">🇦🇷 +54</option>
                        <option value="+55" data-flag="🇧🇷">🇧🇷 +55</option>
                        <option value="+56" data-flag="🇨🇱">🇨🇱 +56</option>
                        <option value="+51" data-flag="🇵🇪">🇵🇪 +51</option>
                        <option value="+593" data-flag="🇪🇨">🇪🇨 +593</option>
                    </select>
                    <input type="tel" name="billing_phone" id="billing_phone" placeholder="3114928790" required class="wiwa-input">
                </div>
                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Este campo es requerido</span>
            </div>
        </div>
    </div>
    <div class="section-divider-text"><span>Datos de los viajeros</span></div>
    <div class="tours-passengers-section">
        <div class="section-header">
            <h3>*Necesitamos información sobre los pasajeros para poder confirmar estos tours.</h3>
        </div>
        <div class="tours-accordion">
            <?php foreach ($tours as $tour_index => $tour):
    $tour_number = $tour_index + 1; ?>
            <div class="tour-accordion-item <?php echo $tour_index === 0 ? 'active' : ''; ?>" data-tour-index="<?php echo $tour_index; ?>">
                <div class="tour-accordion-header" data-tour="<?php echo $tour_number; ?>">
                    <div class="tour-accordion-header-left">
                        <?php if ($tour['thumbnail']): ?>
                        <div class="tour-thumbnail"><?php echo $tour['thumbnail']; ?></div>
                        <?php
    endif; ?>
                        <div class="tour-info">
                            <h4 class="tour-name"><?php echo esc_html($tour['name']); ?></h4>
                            <div class="tour-meta">
                                <span class="meta-item">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                                    <?php echo $tour['total_pax']; ?> Viajero<?php echo $tour['total_pax'] > 1 ? 's' : ''; ?>
                                </span>
                                <?php if ($tour['tour_date']): ?>
                                <span class="meta-item">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                    <?php echo date_i18n('j M Y', strtotime($tour['tour_date'])); ?>
                                </span>
                                <?php
    endif; ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="tour-accordion-toggle">
                        <svg class="chevron" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                <div class="tour-accordion-body">
                    <?php for ($pax = 1; $pax <= $tour['total_pax']; $pax++):
        $is_child = $pax > $tour['adults'];
        $guest_index = ($tour_index * 100) + $pax; // ID único global
?>
                    <div class="passenger-block compact">
                        <div class="passenger-header">
                            <span class="passenger-icon"><svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1v-1c0-1-1-4-6-4s-6 3-6 4v1a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12z"/></svg></span>
                            <h5>Pasajero <?php echo $pax; ?> <span class="pax-type">(<?php echo $is_child ? 'Niño' : 'Adulto'; ?>)</span></h5>
                        </div>
                        <div class="form-grid form-grid-2">
                            <div class="form-field">
                                <label>Nombre <span class="required">*</span></label>
                                <input type="text" name="guest_first_name_<?php echo $guest_index; ?>" required class="wiwa-input" placeholder="Juan Pablo" data-guest-field="required">
                                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Nombre requerido</span>
                            </div>
                            <div class="form-field">
                                <label>Apellido <span class="required">*</span></label>
                                <input type="text" name="guest_last_name_<?php echo $guest_index; ?>" required class="wiwa-input" placeholder="Misat" data-guest-field="required">
                                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Apellido requerido</span>
                            </div>
                            <div class="form-field">
                                <label>Teléfono</label>
                                <input type="tel" name="guest_phone_<?php echo $guest_index; ?>" class="wiwa-input" placeholder="opcional">
                            </div>
                            <div class="form-field">
                                <label>Documento <span class="required">*</span></label>
                                <input type="text" name="guest_passport_<?php echo $guest_index; ?>" required class="wiwa-input" data-guest-field="required">
                                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Documento requerido</span>
                            </div>
                            <div class="form-field">
                                <label>Nacionalidad <span class="required">*</span></label>
                                <select name="guest_nationality_<?php echo $guest_index; ?>" required class="wiwa-select" data-guest-field="required">
                                    <option value="">Seleccionar</option>
                                    <?php foreach (WC()->countries->get_countries() as $code => $name) {
            printf('<option value="%s" %s>%s</option>', $code, $code === 'CO' ? 'selected' : '', $name);
        }?>
                                </select>
                                <span class="error-message" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Nacionalidad requerida</span>
                            </div>
                            <div class="form-field">
                                <label>Preferencias alimenticias</label>
                                <select name="guest_diet_<?php echo $guest_index; ?>" class="wiwa-select">
                                    <option value="">Ninguna</option>
                                    <option value="vegetarian">Vegetariana</option>
                                    <option value="vegan">Vegana</option>
                                    <option value="gluten_free">Sin gluten</option>
                                    <option value="lactose_free">Sin lactosa</option>
                                </select>
                            </div>
                        </div>
                        <!-- Campos ocultos para mapear al formato de Travel Tour Booking -->
                        <input type="hidden" name="tour_<?php echo $tour_number; ?>_guest_<?php echo $pax; ?>_index" value="<?php echo $guest_index; ?>">
                    </div>
                    <?php
    endfor; ?>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>
    <div class="form-footer">
        <label class="checkbox-label">
            <input type="checkbox" name="accept_terms" id="accept_terms" required>
            <span>Aceptar <a href="#" target="_blank">Términos y Condiciones</a> y <a href="#" target="_blank">Política de Privacidad</a></span>
        </label>
        <span class="error-message" id="terms-error" style="display:none;color:#EF4444;font-size:13px;margin-top:4px;">Debes aceptar los términos y condiciones</span>
        <button type="submit" class="btn-primary btn-continue">
            Ir al pago
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
    <?php wp_nonce_field('wiwa_checkout_step_1', 'wiwa_step_1_nonce'); ?>
</form>
