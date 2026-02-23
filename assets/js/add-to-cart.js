jQuery(document).ready(function($) {

    function validateForm($form) {
        var isValid = true;

        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return false;
        }

        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error-field');
            } else {
                $(this).removeClass('error-field');
            }
        });

        var checkin = $form.find('input[name="checkin_date"]').val();
        if (!checkin && $form.find('.ovatb-datepicker').length) {
             var visibleDate = $form.find('.ovatb-datepicker').val();
             if(!visibleDate) {
                 isValid = false;
                 $form.find('.ovatb-datepicker').addClass('error-field');
             }
        }
        
        if (!isValid) {
            $form.addClass('shake');
            setTimeout(function(){ $form.removeClass('shake'); }, 500);
        }
        return isValid;
    }

    function doAjaxAddToCart($form, $btn, isDirectCheckout) {
        if (!validateForm($form)) {
            return;
        }

        var formData = new FormData($form[0]);
        formData.append('action', 'wiwa_ajax_add_to_cart');
        formData.append('is_ajax_cart', '1');
        
        if (typeof wiwaAjax !== 'undefined' && wiwaAjax.nonce) {
             formData.append('security', wiwaAjax.nonce);
        }
        
        // UI Loading
        var originalText = $btn.html();
        var loadingText = (typeof wiwaAjax !== 'undefined' && wiwaAjax.strings ? wiwaAjax.strings.processing : 'Procesando...');
        
        $form.find('button').prop('disabled', true);
        $btn.addClass('loading').html('<span class="wiwa-spinner"></span> ' + loadingText);

        var ajaxUrl = (typeof wiwaAjax !== 'undefined') ? wiwaAjax.ajax_url : ((typeof ovatbAjaxObject !== 'undefined') ? ovatbAjaxObject.ajax_url : '/wp-admin/admin-ajax.php');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $btn.removeClass('loading');
                
                var res = typeof response === 'string' ? JSON.parse(response) : response;

                if (res.success) {
                    var data = res.data || {};
                    if (isDirectCheckout && data.checkout_url) {
                        window.location.href = data.checkout_url;
                    } else {
                        handleSoftAddSuccess(data);
                    }
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : (res.message || 'Error al agregar al carrito.');
                    alert(msg);
                    resetButtons($form, $btn, originalText);
                }
            },
            error: function(err) {
                console.error(err);
                alert('Ocurrió un error de conexión. Intente nuevamente.');
                resetButtons($form, $btn, originalText);
            }
        });
    }

    function handleSoftAddSuccess(data) {
        $('#ova-booking-actions-container').slideUp();
        
        var $successLayer = $('#ova-booking-success-layer');
        if (data.product_title) {
            $('#success-tour-name').text(data.product_title);
        }
        if (data.product_image) {
            $('#success-tour-image').attr('src', data.product_image).show();
        } else {
            $('#success-tour-image').hide();
        }
        if (data.product_date) {
            $('#success-tour-date').text(data.product_date).show();
        } else {
            $('#success-tour-date').hide();
        }
        // Update view cart link if provided
        if (data.cart_url) {
            $('.btn-view-cart').attr('href', data.cart_url);
        }
        if (data.checkout_url) {
            $('.btn-reserve-now').attr('href', data.checkout_url);
        }
        
        $successLayer.fadeIn();

        $(document.body).trigger('wc_fragment_refresh');
        $(document.body).trigger('added_to_cart', [data.fragments || {}, data.cart_hash || '', $]);
    }

    function resetButtons($form, $btn, originalText) {
        $btn.html(originalText);
        $form.find('button').prop('disabled', false);
    }

    // Flujo "Reservar" (Direct Checkout)
    $(document).on('submit', '#booking-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('#btn-direct-checkout');
        if(!$btn.length) $btn = $form.find('button[type="submit"]');
        doAjaxAddToCart($form, $btn, true);
    });

    // Flujo "Agregar al Carrito" (Soft Add)
    $(document).on('click', '#btn-add-to-cart-soft', function(e) {
        e.preventDefault();
        var $form = $('#booking-form');
        var $btn = $(this);
        doAjaxAddToCart($form, $btn, false);
    });

    // Reset Modal when JetPopup hides
    $(window).on('jet-popup/hide', function() {
        setTimeout(function() {
            var $form = $('#booking-form');
            if ($form.length) {
                $form[0].reset();
                $form.find('select').val(null).trigger('change');
                $form.find('input[name="checkin_date"]').val('');
                $form.find('input[name="checkout_date"]').val('');
                
                $('#ova-booking-actions-container').show();
                $('.ova-booking-form-fields').show();
                $('#ova-booking-success-layer').hide();
                
                $form.find('button').prop('disabled', false);
            }
        }, 300);
    });

    // Cerrar popup manual usando la "X" custom
    $(document).on('click', '.btn-close-popup', function(e) {
        e.preventDefault();
        $(this).closest('.jet-popup').find('.jet-popup__close-button').trigger('click');
        $('.jet-popup-close-button').trigger('click');
    });

});
