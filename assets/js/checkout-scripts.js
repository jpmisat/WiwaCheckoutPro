/**
 * Wiwa Tour Checkout Scripts v1.0.3
 * @author Juan Pablo Misat - Connexis
 * Incluye validación completa en el cliente
 */
jQuery(document).ready(function ($) {
    'use strict';

    // ==================== VALIDACIÓN DEL FORMULARIO ====================

    /**
     * Validar un campo individual
     */
    function validateField($field) {
        var isValid = true;
        var $errorMsg = $field.closest('.form-field').find('.error-message');

        // Limpiar estado previo
        $field.removeClass('error');
        $errorMsg.hide();

        // Campo requerido vacío
        if ($field.prop('required') && !$field.val()) {
            isValid = false;
        }

        // Validar email
        if ($field.attr('type') === 'email' && $field.val()) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test($field.val())) {
                isValid = false;
            }
        }

        // Mostrar error si no es válido
        if (!isValid) {
            $field.addClass('error');
            $errorMsg.show();
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

        // Validar campos de contacto
        $form.find('input[required], select[required]').each(function () {
            if (!validateField($(this)) && isValid) {
                firstError = $(this);
                isValid = false;
            }
        });

        // Validar campos de pasajeros (data-guest-field="required")
        $form.find('[data-guest-field="required"]').each(function () {
            var $field = $(this);
            var $errorMsg = $field.closest('.form-field').find('.error-message');

            if (!$field.val() || $field.val() === '') {
                $field.addClass('error');
                $errorMsg.show();

                if (isValid) {
                    firstError = $field;
                    $errorAccordion = $field.closest('.tour-accordion-item');
                    isValid = false;
                }
            } else {
                $field.removeClass('error');
                $errorMsg.hide();
            }
        });

        // Validar términos y condiciones
        if (!$('#accept_terms').is(':checked')) {
            $('#terms-error').show();
            if (isValid) {
                firstError = $('#accept_terms');
                isValid = false;
            }
        } else {
            $('#terms-error').hide();
        }

        // Si hay error, abrir acordeón y hacer scroll
        if (!isValid && firstError) {
            if ($errorAccordion && $errorAccordion.length) {
                // Abrir el acordeón con error
                $('.tour-accordion-item').removeClass('active');
                $errorAccordion.addClass('active');
            }

            // Scroll al primer error
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);

            firstError.focus();
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
        } else {
            $('.tour-accordion-item').removeClass('active');
            $item.addClass('active');
        }
    });

    // ==================== VALIDACIÓN EN TIEMPO REAL ====================

    // Validar al perder foco
    $('#wiwa-checkout-step-1 input, #wiwa-checkout-step-1 select').on('blur', function () {
        if ($(this).prop('required') || $(this).data('guest-field') === 'required') {
            validateField($(this));
        }
    });

    // Limpiar error al escribir
    $('#wiwa-checkout-step-1 input, #wiwa-checkout-step-1 select').on('input change', function () {
        if ($(this).hasClass('error')) {
            $(this).removeClass('error');
            $(this).closest('.form-field').find('.error-message').hide();
        }
    });

    // ==================== SUBMIT DEL FORMULARIO ====================

    $('#wiwa-checkout-step-1').on('submit', function (e) {
        var $form = $(this);

        // Validar formulario
        if (!validateForm($form)) {
            e.preventDefault();
            return false;
        }

        // Deshabilitar botón para evitar doble submit
        $form.find('button[type="submit"]').prop('disabled', true).text('Procesando...');

        // Permitir submit
        return true;
    });

    // ==================== PASO 2: PAYMENT ====================

    // Payment Method Selection
    $(document).on('change', 'input[name="payment_method"]', function () {
        $('.payment-method-description').slideUp(200);
        $(this).closest('.payment-method-option').find('.payment-method-description').slideDown(300);
    });

    // Currency Change
    $(document).on('change', 'input[name="order_currency"]', function () {
        var currency = $(this).val();
        $.ajax({
            url: wiwaCheckout.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wiwa_update_currency',
                currency: currency,
                nonce: wiwaCheckout.nonce
            },
            beforeSend: function () {
                $('.order-summary-card').css('opacity', '0.6');
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                }
            },
            complete: function () {
                $('.order-summary-card').css('opacity', '1');
            }
        });
    });

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

    // ==================== STICKY SIDEBAR (JavaScript Implementation) ====================

    var $sidebar = $('.order-summary-sticky');
    var $sidebarColumn = $('.checkout-sidebar-column');
    var $mainContent = $('.wiwa-checkout-content');

    if ($sidebar.length && $sidebarColumn.length && $mainContent.length) {
        var stickyOffset = 100; // Distance from top when sticky
        var lastScrollTop = 0;

        function handleStickyScroll() {
            var scrollTop = $(window).scrollTop();
            var mainTop = $mainContent.offset().top;
            var mainHeight = $mainContent.outerHeight();
            var mainBottom = mainTop + mainHeight;
            var sidebarHeight = $sidebar.outerHeight();
            var sidebarColumnWidth = $sidebarColumn.width();
            var sidebarColumnLeft = $sidebarColumn.offset().left;

            // Calculate boundaries
            var startSticky = mainTop - stickyOffset;
            var endSticky = mainBottom - sidebarHeight - stickyOffset;

            if (scrollTop > startSticky && scrollTop < endSticky) {
                // STICKY MODE: sidebar follows scroll
                $sidebar.css({
                    'position': 'fixed',
                    'top': stickyOffset + 'px',
                    'width': sidebarColumnWidth + 'px',
                    'left': sidebarColumnLeft + 'px',
                    'right': 'auto'
                });
                $sidebarColumn.css('min-height', sidebarHeight + 'px');
            } else if (scrollTop >= endSticky) {
                // BOTTOM MODE: sidebar stops at bottom of main content
                $sidebar.css({
                    'position': 'absolute',
                    'top': 'auto',
                    'bottom': '0',
                    'width': sidebarColumnWidth + 'px',
                    'left': '0',
                    'right': 'auto'
                });
                $sidebarColumn.css({
                    'position': 'relative',
                    'min-height': sidebarHeight + 'px'
                });
            } else {
                // TOP MODE: sidebar at normal position
                $sidebar.css({
                    'position': 'relative',
                    'top': 'auto',
                    'width': '100%',
                    'left': 'auto',
                    'right': 'auto'
                });
                $sidebarColumn.css('min-height', 'auto');
            }

            lastScrollTop = scrollTop;
        }

        // Throttle scroll for performance
        var scrollTimeout;
        $(window).on('scroll', function () {
            if (scrollTimeout) {
                window.cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = window.requestAnimationFrame(handleStickyScroll);
        });

        // Recalculate on resize
        $(window).on('resize', function () {
            handleStickyScroll();
        });

        // Initial call
        handleStickyScroll();
    }
});
