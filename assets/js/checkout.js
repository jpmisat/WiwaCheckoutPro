/**
 * Wiwa Tour Checkout Scripts v2.5.0
 * @author Juan Pablo Misat - Connexis
 * Incluye validación completa en el cliente
 * Terms persistence y WOOCS integration
 */
jQuery(document).ready(function ($) {
    'use strict';

    // ==================== TERMS CHECKBOX PERSISTENCE ====================

    // Restore terms checkbox state from sessionStorage
    if (sessionStorage.getItem('wiwa_accept_terms') === 'true') {
        $('#accept_terms').prop('checked', true);
    }

    // Save terms checkbox state on change
    $('#accept_terms').on('change', function () {
        sessionStorage.setItem('wiwa_accept_terms', $(this).is(':checked'));
    });

    // ==================== VALIDACIÓN DEL FORMULARIO ====================
    /**
     * Validar un campo individual
     * Solo muestra error si el campo está vacío y fue tocado (blur/submit)
     */
    function validateField($field) {
        var isValid = true;
        var $formField = $field.closest('.form-field');
        var $errorMsg = $formField.find('.error-message');

        // Limpiar estado previo
        $field.removeClass('error');
        $formField.removeClass('has-error');

        // Solo validar si el campo ha sido tocado o está vacío
        var fieldValue = $field.val();

        // Campo requerido vacío
        if ($field.prop('required') && (!fieldValue || fieldValue === '')) {
            isValid = false;
        }

        // Validar email solo si tiene valor
        if ($field.attr('type') === 'email' && fieldValue) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(fieldValue)) {
                isValid = false;
            }
        }

        // Mostrar error si no es válido
        if (!isValid) {
            $field.addClass('error');
            $formField.addClass('has-error');
        }

        return isValid;
    }

    /**
     * Validar todos los campos del formulario
     */
    function validateForm($form) {
        var isValid = true;
        var firstError = null;
        var $errorAccordion = null;
        var errorAccordions = [];

        // Validar campos de contacto
        $form.find('input[required], select[required]').each(function () {
            if (!validateField($(this))) {
                if (!firstError) {
                    firstError = $(this);
                }
                isValid = false;
            }
        });

        // Validar campos de pasajeros (data-guest-field="required" OR data-required="true")
        $form.find('[data-guest-field="required"], [data-required="true"]').each(function () {
            var $field = $(this);
            var $formField = $field.closest('.form-field');
            var $accordion = $field.closest('.tour-accordion-item');

            if (!$field.val() || $field.val() === '') {
                $field.addClass('error');
                $formField.addClass('has-error');

                // Track this accordion as having errors
                if ($accordion.length && errorAccordions.indexOf($accordion[0]) === -1) {
                    errorAccordions.push($accordion[0]);
                }

                if (!firstError) {
                    firstError = $field;
                    $errorAccordion = $accordion;
                }
                isValid = false;
            } else {
                $field.removeClass('error');
                $formField.removeClass('has-error');
            }
        });

        // Validar términos y condiciones
        if (!$('#accept_terms').is(':checked')) {
            $('#terms-error').show();
            if (!firstError) {
                firstError = $('#accept_terms');
            }
            isValid = false;
        } else {
            $('#terms-error').hide();
        }

        // Si hay error en un acordeón cerrado, abrirlo
        if (!isValid && errorAccordions.length > 0) {
            // First, add visual indicator to all accordions with errors
            $(errorAccordions).each(function () {
                $(this).addClass('has-error');
            });

            // Open the first accordion that has an error and is closed
            var $firstClosedError = null;
            $(errorAccordions).each(function () {
                if (!$(this).hasClass('active')) {
                    $firstClosedError = $(this);
                    return false; // break
                }
            });

            if ($firstClosedError) {
                // Close all and open the one with error
                $firstClosedError.addClass('active');

                // Update firstError to be within this accordion
                firstError = $firstClosedError.find('.error').first();
            }
        }

        // Scroll al primer error
        if (!isValid && firstError && firstError.length) {
            // Small delay to allow accordion to open
            setTimeout(function () {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 400, function () {
                    firstError.focus();
                });
            }, 100);
        }

        return isValid;
    }

    // ==================== ACORDEONES ====================

    // Abrir primer acordeón por defecto
    $('.tour-accordion-item').first().addClass('active');

    // Toggle accordion
    $('.tour-accordion-header').on('click', function () {
        var $item = $(this).closest('.tour-accordion-item');

        if ($item.hasClass('active')) {
            $item.removeClass('active');
            $item.find('.tour-accordion-body').slideUp(200);
        } else {
            $('.tour-accordion-item').removeClass('active');
            $('.tour-accordion-body').slideUp(200);
            $item.addClass('active');
            $item.find('.tour-accordion-body').slideDown(200);
        }
    });

    // ==================== STEP NAVIGATION VALIDATION ====================

    // Prevent clicking step 2 without validating step 1
    $('#step-2-link').on('click', function (e) {
        e.preventDefault();

        var $form = $('#wiwa-checkout-step-1');

        // If on step 1, validate before navigating
        if ($form.length) {
            if (validateForm($form)) {
                // Form is valid, submit it to go to step 2
                $form.submit();
            }
            // If invalid, validateForm already handles error display
        }
    });

    // Allow clicking step 1 link to go back
    $('.step[data-step="1"]').on('click', function (e) {
        // Allow normal navigation to step 1
    });

    // ==================== VALIDACIÓN EN TIEMPO REAL ====================

    // Validar al perder foco
    $('#wiwa-checkout-step-1 input, #wiwa-checkout-step-1 select').on('blur', function () {
        if ($(this).prop('required') || $(this).data('guest-field') === 'required' || $(this).data('required') === true) {
            validateField($(this));
        }
    });

    // Limpiar error al escribir
    $('#wiwa-checkout-step-1 input, #wiwa-checkout-step-1 select').on('input change', function () {
        if ($(this).hasClass('error')) {
            $(this).removeClass('error');
            $(this).closest('.form-field').removeClass('has-error');
        }
        // Also clear accordion error when fields are filled
        var $accordion = $(this).closest('.tour-accordion-item');
        if ($accordion.length && $accordion.hasClass('has-error')) {
            // Check if all required fields in this accordion are now valid
            var hasEmptyRequired = false;
            $accordion.find('[data-guest-field="required"], [data-required="true"]').each(function () {
                if (!$(this).val()) {
                    hasEmptyRequired = true;
                    return false; // break
                }
            });
            if (!hasEmptyRequired) {
                $accordion.removeClass('has-error');
            }
        }
    });

    // ==================== SUBMIT DEL FORMULARIO ====================

    $('#wiwa-checkout-step-1').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);

        // Validar formulario
        if (!validateForm($form)) {
            return false;
        }

        var $submitBtn = $form.find('button[type="submit"], .btn-continue');
        var originalText = $submitBtn.text();

        // Deshabilitar botón
        $submitBtn.prop('disabled', true).text('Guardando...');

        // AJAX para guardar datos y actualizar carrito (Bridge for Ova Tour Booking)
        var formData = $form.serialize();

        $.ajax({
            url: wiwaCheckout.ajaxUrl,
            type: 'POST',
            data: formData + '&action=wiwa_update_order_data&nonce=' + wiwaCheckout.nonce,
            success: function (response) {
                if (response.success) {
                    // Redireccionar al paso 2
                    window.location.href = $form.attr('action');
                } else {
                    alert('Error guardando datos. Por favor intenta de nuevo.');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function () {
                alert('Error de conexión. Por favor intenta de nuevo.');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // ==================== PASO 2: PAYMENT ====================

    // Payment Method Selection
    $(document).on('change', 'input[name="payment_method"]', function () {
        $('.payment-method-description').slideUp(200);
        $(this).closest('.payment-method-option').find('.payment-method-description').slideDown(300);
    });

    // ==================== WOOCS CURRENCY SWITCHER ====================

    // Button style currency selector
    $(document).on('click', '.currency-btn', function (e) {
        e.preventDefault();
        var currency = $(this).data('currency');
        changeCurrency(currency);
    });

    // Dropdown style currency selector
    $(document).on('change', '#wiwa-currency-select', function () {
        var currency = $(this).val();
        changeCurrency(currency);
    });

    // Legacy support for radio inputs
    $(document).on('change', 'input[name="order_currency"]', function () {
        var currency = $(this).val();
        changeCurrency(currency);
    });

    // Currency change function (WOOCS compatible)
    function changeCurrency(currency) {
        // Show loading state
        $('.order-summary-card').css('opacity', '0.6');
        $('.currency-btn').prop('disabled', true);
        $('.currency-btn.active').removeClass('active');
        $('.currency-btn[data-currency="' + currency + '"]').addClass('active');

        // WOOCS uses URL parameter to switch currency
        // This is the most reliable method and works with all WOOCS configurations
        var url = window.location.href;

        // Remove existing currency parameter
        url = url.replace(/([?&])currency=[^&]*/g, '');

        // Clean up URL
        url = url.replace(/\?&/, '?').replace(/&&/, '&').replace(/\?$/, '').replace(/&$/, '');

        // Add new currency parameter
        url += (url.indexOf('?') === -1 ? '?' : '&') + 'currency=' + currency;

        // Redirect to new URL
        window.location.href = url;
    }

    // Apply Coupon
    $('#apply_coupon').on('click', function (e) {
        e.preventDefault();
        var couponCode = $('#coupon_code').val().trim();

        if (!couponCode) {
            showCouponMessage('Por favor ingresa un código de descuento', 'error');
            return;
        }

        $.ajax({
            url: wiwaCheckout.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wiwa_apply_coupon',
                coupon_code: couponCode,
                nonce: wiwaCheckout.nonce
            },
            beforeSend: function () {
                $('#apply_coupon').prop('disabled', true).text('Aplicando...');
            },
            success: function (response) {
                if (response.success) {
                    showCouponMessage(response.data.message, 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                } else {
                    showCouponMessage(response.data.message, 'error');
                }
            },
            complete: function () {
                $('#apply_coupon').prop('disabled', false).text('Aplicar');
            }
        });
    });

    function showCouponMessage(message, type) {
        var $msg = $('#coupon-message');
        $msg.removeClass('success error').addClass(type).text(message).fadeIn();
        setTimeout(function () {
            $msg.fadeOut();
        }, 5000);
    }

    // Step 2 Process Payment
    // Step 2 Process Payment
    /* 
    // DISABLED: Let WooCommerce and Gateways handle the submit naturally
    $('#wiwa-checkout-step-2').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            url: wiwaCheckout.ajaxUrl,
            type: 'POST',
            data: formData + '&action=wiwa_process_checkout&nonce=' + wiwaCheckout.nonce,
            beforeSend: function () {
                $('#place_order').prop('disabled', true).text('Procesando pago...');
            },
            success: function (response) {
                if (response.result === 'success') {
                    window.location.href = response.redirect;
                } else if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    alert('Error: ' + (response.messages || 'Error desconocido'));
                    $('#place_order').prop('disabled', false).text('Confirmar y pagar');
                }
            },
            error: function () {
                alert('Error en el proceso de pago. Por favor intenta nuevamente.');
                $('#place_order').prop('disabled', false).text('Confirmar y pagar');
            }
        });
    });
    */

    // ==================== AUTO-SAVE (OPCIONAL) ====================

    if ($('#wiwa-checkout-step-1').length) {
        $('#wiwa-checkout-step-1 input, #wiwa-checkout-step-1 select').on('blur', function () {
            var fieldName = $(this).attr('name');
            var fieldValue = $(this).val();

            if (fieldName && fieldValue) {
                sessionStorage.setItem('wiwa_' + fieldName, fieldValue);
            }
        });

        // Restaurar valores guardados
        $('#wiwa-checkout-step-1 input, #wiwa-checkout-step-1 select').each(function () {
            var fieldName = $(this).attr('name');
            var savedValue = sessionStorage.getItem('wiwa_' + fieldName);

            if (savedValue && !$(this).val()) {
                $(this).val(savedValue);
            }
        });
    }
});
