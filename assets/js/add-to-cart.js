/**
 * Wiwa Add-to-Cart Handler v2.14.0
 * Handles both "Reservar" (direct checkout) and "Agregar al carrito" (soft add)
 * Success popup is a standalone overlay appended to <body>.
 */
jQuery(function ($) {
    'use strict';

    // ==================== FORM VALIDATION ====================

    function validateForm($form) {
        var isValid = true;

        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return false;
        }

        $form.find('[required]').each(function () {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error-field');
            } else {
                $(this).removeClass('error-field');
            }
        });

        // Check for checkin date — OvaTour names its fields with the 'ovatb_' prefix
        var checkin = $form.find('input[name="ovatb_checkin_date"]').val()
                   || $form.find('input[name="checkin_date"]').val();

        if (!checkin && $form.find('.ovatb-datepicker').length) {
            var visibleDate = $form.find('.ovatb-datepicker').val();
            if (!visibleDate) {
                isValid = false;
                $form.find('.ovatb-datepicker').addClass('error-field');
            }
        }

        if (!isValid) {
            $form.addClass('shake');
            setTimeout(function () { $form.removeClass('shake'); }, 500);
        }
        return isValid;
    }

    // ==================== COLLECT FORM DATA ====================

    function collectFormData($form) {
        var formData = $form.serialize();
        // Add the product ID explicitly
        var productId = $form.find('input[name="ovatb-product-id"]').val()
                     || $form.find('button[name="add-to-cart"]').val();
        formData += '&product_id=' + encodeURIComponent(productId);
        return formData;
    }

    // ==================== DIRECT CHECKOUT ("Reservar") ====================

    $(document).on('click', '#btn-direct-checkout', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var $form = $btn.closest('form');

        if (!validateForm($form)) return;

        var originalText = $btn.html();
        $btn.html('<span class="wiwa-spinner"></span>');
        $form.find('button').prop('disabled', true);

        $.ajax({
            url: wc_add_to_cart_params.ajax_url || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: collectFormData($form) + '&action=wiwa_add_to_cart',
            success: function (res) {
                if (res.success) {
                    // Redirect to checkout
                    window.location.href = res.data.checkout_url || wc_add_to_cart_params.cart_url;
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : (res.message || 'Error al agregar al carrito.');
                    alert(msg);
                    resetButtons($form, $btn, originalText);
                }
            },
            error: function () {
                alert('Ocurrió un error de conexión. Intente nuevamente.');
                resetButtons($form, $btn, originalText);
            }
        });
    });

    // ==================== SOFT ADD TO CART ("Agregar al carrito") ====================

    $(document).on('click', '#btn-add-to-cart-soft', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var $form = $btn.closest('form');

        if (!validateForm($form)) return;

        var originalText = $btn.html();
        $btn.html('<span class="wiwa-spinner"></span>');
        $form.find('button').prop('disabled', true);

        $.ajax({
            url: wc_add_to_cart_params.ajax_url || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: collectFormData($form) + '&action=wiwa_add_to_cart',
            success: function (res) {
                if (res.success) {
                    handleSoftAddSuccess(res.data);
                    resetButtons($form, $btn, originalText);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : (res.message || 'Error al agregar al carrito.');
                    alert(msg);
                    resetButtons($form, $btn, originalText);
                }
            },
            error: function () {
                alert('Ocurrió un error de conexión. Intente nuevamente.');
                resetButtons($form, $btn, originalText);
            }
        });
    });

    // ==================== SUCCESS OVERLAY ====================

    function handleSoftAddSuccess(data) {
        // 1. Close the JetPopup / Elementor Popup modal
        closeBookingModal();

        // 2. Build and show the standalone overlay
        showSuccessOverlay(data);

        // 3. Refresh WooCommerce fragments (updates mini-cart count)
        $(document.body).trigger('wc_fragment_refresh');
        $(document.body).trigger('added_to_cart', [data.fragments || {}, data.cart_hash || '', $]);
    }

    function closeBookingModal() {
        // JetPopup close
        var $jetPopup = $('.jet-popup').filter(':visible');
        if ($jetPopup.length) {
            $jetPopup.find('.jet-popup__close-button').trigger('click');
        }

        // Elementor Popup close (fallback)
        var $elemPopup = $('.elementor-popup-modal').filter(':visible');
        if ($elemPopup.length) {
            $elemPopup.find('.dialog-close-button').trigger('click');
        }

        // Also try generic overlay close via ESC simulation
        var escEvent = new KeyboardEvent('keydown', { key: 'Escape', keyCode: 27, bubbles: true });
        document.dispatchEvent(escEvent);
    }

    function showSuccessOverlay(data) {
        // Remove any existing overlay
        $('#wiwa-success-overlay').remove();

        var imageHtml = '';
        if (data.product_image) {
            imageHtml = '<div class="wiwa-so-image"><img src="' + data.product_image + '" alt="Tour" /></div>';
        }

        var dateHtml = '';
        if (data.product_date) {
            dateHtml = '<p class="wiwa-so-date"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> ' + data.product_date + '</p>';
        }

        var cartUrl = data.cart_url || '/carrito/';
        var checkoutUrl = data.checkout_url || '/checkout/';
        var title = data.product_title || 'Tour';

        var overlayHtml = '' +
            '<div id="wiwa-success-overlay" class="wiwa-so">' +
                '<div class="wiwa-so-backdrop"></div>' +
                '<div class="wiwa-so-card">' +
                    '<button type="button" class="wiwa-so-close" aria-label="Cerrar">&times;</button>' +
                    '<div class="wiwa-so-content">' +
                        imageHtml +
                        '<div class="wiwa-so-details">' +
                            '<div class="wiwa-so-check">' +
                                '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="11" stroke="#22c55e" stroke-width="2"/><path d="M7 12.5l3 3 7-7" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '</div>' +
                            '<span class="wiwa-so-badge">¡Agregado al carrito!</span>' +
                            '<h3 class="wiwa-so-title">' + title + '</h3>' +
                            dateHtml +
                            '<div class="wiwa-so-actions">' +
                                '<a href="' + cartUrl + '" class="wiwa-so-btn wiwa-so-btn-outline">Ver carrito</a>' +
                                '<a href="' + checkoutUrl + '" class="wiwa-so-btn wiwa-so-btn-solid">Reservar ahora</a>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(overlayHtml);

        // Trigger animation (slight delay for DOM insertion)
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                $('#wiwa-success-overlay').addClass('wiwa-so--visible');
            });
        });

        // Bind close events
        bindOverlayClose();
    }

    function bindOverlayClose() {
        // Close button click
        $(document).on('click.wiwaOverlay', '.wiwa-so-close', function () {
            closeOverlay();
        });

        // Backdrop click
        $(document).on('click.wiwaOverlay', '.wiwa-so-backdrop', function () {
            closeOverlay();
        });

        // ESC key
        $(document).on('keydown.wiwaOverlay', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                closeOverlay();
            }
        });
    }

    function closeOverlay() {
        var $overlay = $('#wiwa-success-overlay');
        $overlay.removeClass('wiwa-so--visible');
        setTimeout(function () {
            $overlay.remove();
        }, 300);
        // Unbind events
        $(document).off('.wiwaOverlay');
    }

    // ==================== HELPERS ====================

    function resetButtons($form, $btn, originalText) {
        $btn.html(originalText);
        $form.find('button').prop('disabled', false);
    }

    // Reset form when modal opens (for re-use)
    $(document).on('jet-popup/show-event/after-show', function () {
        var $form = $('.ovatb-booking-form');
        $form.find('.field-wrap').show();
        $('#ova-booking-actions-container').show();
    });
});
