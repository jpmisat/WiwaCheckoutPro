jQuery(document).ready(function($) {
    // Escuchar el botón suave de agregar al carrito
    $(document).on('click', '#btn-add-to-cart-soft', function(e) {
        e.preventDefault();
        
        var $form = $('#booking-form');
        var $btn = $(this);
        var $originalBtn = $form.find('button[name="add-to-cart"]');
        
        // 1. Validación
        var isValid = true;

        // Validación nativa HTML5
        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }

        // Validación manual de campos requeridos (backup)
        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error-field');
            } else {
                $(this).removeClass('error-field');
            }
        });

        // Validación de fechas (OvaTourBooking suele usar campos hidden para fechas reales)
        var checkin = $form.find('input[name="checkin_date"]').val();
        if (!checkin && $form.find('.ovatb-datepicker').length) {
             // Si hay datepicker y no hay fecha, intentar validar el input visible
             var visibleDate = $form.find('.ovatb-datepicker').val();
             if(!visibleDate) {
                 isValid = false;
                 $form.find('.ovatb-datepicker').addClass('error-field');
             }
        }
        
        if (!isValid) {
            // Animación de shake u otro feedback visual
            $form.addClass('shake');
            setTimeout(function(){ $form.removeClass('shake'); }, 500);
            return;
        }

        // 2. Preparar Datos
        var formData = new FormData($form[0]);
        formData.append('action', 'wiwa_ajax_add_to_cart');
        formData.append('is_ajax_cart', '1');
        
        if (typeof wiwaAjax !== 'undefined' && wiwaAjax.nonce) {
             formData.append('security', wiwaAjax.nonce);
        }
        
        // UI Loading
        $btn.addClass('loading');
        $originalBtn.prop('disabled', true);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="wiwa-spinner"></span> Procesando...');

        // 3. AJAX Request
        var ajaxUrl = (typeof wiwaAjax !== 'undefined') ? wiwaAjax.ajax_url : ((typeof ovatbAjaxObject !== 'undefined') ? ovatbAjaxObject.ajax_url : '/wp-admin/admin-ajax.php');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $btn.removeClass('loading');
                
                // Parse response if string
                var res = response;
                if (typeof response === 'string') {
                    try {
                        res = JSON.parse(response);
                    } catch (e) {
                        console.log('Error parsing JSON:', e);
                    }
                }

                if (res.success) {
                    handleSuccess(res && res.data ? res.data : res);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : (res.message || 'Error al agregar al carrito.');
                    alert(msg);
                    resetButtons($btn, originalText);
                }
            },
            error: function(err) {
                console.error(err);
                alert('Ocurrió un error de conexión. Intente nuevamente.');
                resetButtons($btn, originalText);
            }
        });
    });

    function handleSuccess(data) {
        // Ocultar formulario y mostrar success
        $('#ova-booking-actions-container').slideUp();
        // Opcional: Ocultar todo el form
        // $('.ova-booking-form-fields').slideUp(); 
        
        var $successLayer = $('#ova-booking-success-layer');
        
        // Actualizar datos dinámicos
        if (data.product_title) {
            $('#success-tour-name').text(data.product_title);
        }
        
        $successLayer.fadeIn();

        // Actualizar fragmentos de WooCommerce
        $(document.body).trigger('wc_fragment_refresh');
        $(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, $]);
    }

    function resetButtons($btn, originalText) {
        $btn.prop('disabled', false).html(originalText || '<span class="icon-cart"></span> Agregar al Carrito');
        $('button[name="add-to-cart"]').prop('disabled', false);
    }

    // Lógica de Reinicio al cerrar JetPopup
    $(window).on('jet-popup/hide', function() {
        setTimeout(function() {
            var $form = $('#booking-form');
            if ($form.length) {
                $form[0].reset();
                // Resetear selects de Select2 si existen
                $form.find('select').val(null).trigger('change');
                
                // Limpiar inputs hidden de fecha si es necesario
                $form.find('input[name="checkin_date"]').val('');
                $form.find('input[name="checkout_date"]').val('');
                
                // Mostrar formulario de nuevo
                $('#ova-booking-actions-container').show();
                $('.ova-booking-form-fields').show();
                $('#ova-booking-success-layer').hide();
                
                // Resetear botones
                var $btn = $('#btn-add-to-cart-soft');
                resetButtons($btn, '<span class="icon-cart"></span> Agregar al Carrito');
                $btn.removeClass('loading');
            }
        }, 300);
    });

    // Cerrar popup manual
    $(document).on('click', '.btn-close-popup', function(e) {
        e.preventDefault();
        // Trigger de cierre de JetPopup
        $(this).closest('.jet-popup').find('.jet-popup__close-button').trigger('click');
        // Fallback
        $('.jet-popup-close-button').trigger('click');
    });
});
