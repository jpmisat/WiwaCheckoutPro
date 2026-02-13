/**
 * Wiwa Tour Checkout Scripts v3.0.0 (Vanilla JS)
 * @author Juan Pablo Misat - Connexis
 * Optimized for performance and modern browsers.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Helper: Select elements safely
    const $ = (selector, parent = document) => parent.querySelector(selector);
    const $$ = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

    // Helper: Add Event Listener
    const on = (selector, event, handler) => {
        document.addEventListener(event, e => {
            if (e.target.closest(selector)) {
                handler(e, e.target.closest(selector));
            }
        });
    };

    // Helper: Trigger Event
    const trigger = (el, eventName) => {
        if (el) el.dispatchEvent(new Event(eventName, { bubbles: true }));
    };

    // Global Config
    const config = window.wiwaCheckout || {};

    // ==================== TERMS CHECKBOX PERSISTENCE ====================
    
    const termsCheckbox = document.getElementById('accept_terms');
    if (termsCheckbox) {
        // Restore state
        if (sessionStorage.getItem('wiwa_accept_terms') === 'true') {
            termsCheckbox.checked = true;
        }

        // Save state
        termsCheckbox.addEventListener('change', function () {
            sessionStorage.setItem('wiwa_accept_terms', this.checked);
        });
    }

    // ==================== FORM VALIDATION ====================

    function validateField(field) {
        let isValid = true;
        const formField = field.closest('.form-field');
        if (!formField) return true;

        // Reset state
        field.classList.remove('error');
        formField.classList.remove('has-error');

        const fieldValue = field.value.trim();

        // Check required
        if (field.required && !fieldValue) {
            isValid = false;
        }

        // Check email
        if (field.type === 'email' && fieldValue) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(fieldValue)) {
                isValid = false;
            }
        }

        if (!isValid) {
            field.classList.add('error');
            formField.classList.add('has-error');
        }

        return isValid;
    }

    function validateForm(form) {
        let isValid = true;
        let firstError = null;
        let errorAccordions = [];

        // 1. Validate contact fields
        const contactFields = $$('input[required], select[required]', form);
        contactFields.forEach(field => {
            if (!validateField(field)) {
                if (!firstError) firstError = field;
                isValid = false;
            }
        });

        // 2. Validate Guest Fields (custom data attributes)
        const guestFields = $$('[data-guest-field="required"], [data-required="true"]', form);
        guestFields.forEach(field => {
            const formField = field.closest('.form-field');
            const accordion = field.closest('.tour-accordion-item');
            
            if (!field.value.trim()) {
                field.classList.add('error');
                if (formField) formField.classList.add('has-error');

                if (accordion && !errorAccordions.includes(accordion)) {
                    errorAccordions.push(accordion);
                }

                if (!firstError) firstError = field;
                isValid = false;
            } else {
                field.classList.remove('error');
                if (formField) formField.classList.remove('has-error');
            }
        });

        // 3. Validate Terms
        const terms = document.getElementById('accept_terms');
        const termsError = document.getElementById('terms-error');
        
        if (terms && !terms.checked) {
            if (termsError) termsError.style.display = 'block';
            if (!firstError) firstError = terms;
            isValid = false;
        } else {
            if (termsError) termsError.style.display = 'none';
        }

        // Handle Accordions with errors
        if (!isValid && errorAccordions.length > 0) {
            errorAccordions.forEach(acc => acc.classList.add('has-error'));
            
            // Find first closed error accordion
            const closedErrorAcc = errorAccordions.find(acc => !acc.classList.contains('active'));
            if (closedErrorAcc) {
                // Close others?? No, just open this one for now to show user
                closedErrorAcc.classList.add('active');
                const body = closedErrorAcc.querySelector('.tour-accordion-body');
                if (body) body.style.display = 'block';
            }
        }

        // Scroll to first error
        if (!isValid && firstError) {
            setTimeout(() => {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => firstError.focus(), 500);
            }, 300);
        }

        return isValid;
    }

    // ==================== ACCORDIONS ====================
    
    // Open first one by default
    const firstAcc = $('.tour-accordion-item');
    if (firstAcc) {
        firstAcc.classList.add('active');
        const body = firstAcc.querySelector('.tour-accordion-body');
        if (body) body.style.display = 'block';
    }

    // Toggle
    on('.tour-accordion-header', 'click', (e, header) => {
        const item = header.closest('.tour-accordion-item');
        const body = item.querySelector('.tour-accordion-body');

        // Close all others (optional behavior, matching original)
        $$('.tour-accordion-item').forEach(acc => {
            if (acc !== item) {
                acc.classList.remove('active');
                const b = acc.querySelector('.tour-accordion-body');
                if (b) b.style.display = 'none'; // Simple toggle, ideally use CSS transitions or height animation
            }
        });

        // Toggle current
        if (item.classList.contains('active')) {
            item.classList.remove('active');
            if (body) body.style.display = 'none';
        } else {
            item.classList.add('active');
            if (body) body.style.display = 'block';
        }
    });

    // ==================== STEP NAVIGATION ====================

    // Step 2 Link
    const step2Link = document.getElementById('step-2-link');
    if (step2Link) {
        step2Link.addEventListener('click', e => {
            e.preventDefault();
            const form = document.getElementById('wiwa-checkout-step-1');
            if (form) {
                // Manually trigger submit handler
                form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        });
    }

    // ==================== REAL-TIME VALIDATION ====================

    const inputs = $$('#wiwa-checkout-step-1 input, #wiwa-checkout-step-1 select');
    inputs.forEach(input => {
        // Blur validation
        input.addEventListener('blur', () => {
            if (input.required || input.dataset.guestField === 'required' || input.dataset.required === 'true') {
                validateField(input);
            }
        });

        // Input clear error
        input.addEventListener('input', () => {
            if (input.classList.contains('error')) {
                input.classList.remove('error');
                const group = input.closest('.form-field');
                if (group) group.classList.remove('has-error');
            }

            // Check accordion error state
            const accordion = input.closest('.tour-accordion-item');
            if (accordion && accordion.classList.contains('has-error')) {
                // Re-check validity of this accordion
                const requiredInAcc = $$('[data-guest-field="required"], [data-required="true"]', accordion);
                const hasEmpty = requiredInAcc.some(el => !el.value.trim());
                if (!hasEmpty) {
                    accordion.classList.remove('has-error');
                }
            }
        });
    });

    // ==================== FORM SUBMIT (FETCH) ====================

    const form1 = document.getElementById('wiwa-checkout-step-1');
    if (form1) {
        form1.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateForm(this)) return false;

            const submitBtn = this.querySelector('button[type="submit"], .btn-continue');
            const originalText = submitBtn ? submitBtn.innerText : 'Continuar';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerText = 'Guardando...';
            }

            const formData = new FormData(this);
            formData.append('action', 'wiwa_update_order_data');
            formData.append('nonce', config.nonce);

            fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = this.getAttribute('action');
                } else {
                    alert('Error guardando datos: ' + (data.data || 'Error desconocido'));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerText = originalText;
                }
            });
        });
    }

    // ==================== PAYMENT METHODS ====================

    on('input[name="payment_method"]', 'change', (e, input) => {
        // Hide all descriptions
        $$('.payment-method-description').forEach(el => el.style.display = 'none'); // Use explicit display none
        
        // Show current
        const option = input.closest('.payment-method-option');
        if (option) {
            const desc = option.querySelector('.payment-method-description');
            if (desc) desc.style.display = 'block'; // Simple toggle
        }
    });

    // ==================== CURRENCY SWITCHER ====================

    function changeCurrency(currency) {
        // Visually disable
        const card = $('.order-summary-card');
        if (card) card.style.opacity = '0.6';
        
        $$('.currency-btn').forEach(btn => btn.disabled = true);

        // Update URL
        const url = new URL(window.location.href);
        url.searchParams.set('currency', currency);
        window.location.href = url.toString();
    }

    on('.currency-btn', 'click', (e, btn) => {
        e.preventDefault();
        changeCurrency(btn.dataset.currency);
    });

    on('#wiwa-currency-select', 'change', (e, select) => {
        changeCurrency(select.value);
    });

    on('input[name="order_currency"]', 'change', (e, input) => {
        changeCurrency(input.value);
    });

    // ==================== COUPON ====================

    const applyCouponBtn = document.getElementById('apply_coupon');
    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', e => {
            e.preventDefault();
            const codeInput = document.getElementById('coupon_code');
            const code = codeInput ? codeInput.value.trim() : '';

            if (!code) {
                showCouponMessage('Ingresa un código', 'error');
                return;
            }

            applyCouponBtn.disabled = true;
            applyCouponBtn.innerText = 'Aplicando...';

            const formData = new FormData();
            formData.append('action', 'wiwa_apply_coupon');
            formData.append('coupon_code', code);
            formData.append('nonce', config.nonce);

            fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showCouponMessage(res.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showCouponMessage(res.data.message, 'error');
                }
            })
            .catch(err => {
                showCouponMessage('Error de conexión', 'error');
            })
            .finally(() => {
                applyCouponBtn.disabled = false;
                applyCouponBtn.innerText = 'Aplicar';
            });
        });
    }

    function showCouponMessage(msg, type) {
        const el = document.getElementById('coupon-message');
        if (!el) return;
        el.className = 'coupon-message ' + type;
        el.innerText = msg;
        el.style.display = 'block';
        setTimeout(() => {
            el.style.display = 'none';
        }, 5000);
    }

    // ==================== AUTO SAVE ====================
    
    // Autosave inputs to session storage
    if (form1) {
        const saveInput = (input) => {
            if (input.name && input.value) {
                sessionStorage.setItem('wiwa_' + input.name, input.value);
            }
        };

        $$('input, select', form1).forEach(input => {
            // Restore
            if (input.name) {
                const saved = sessionStorage.getItem('wiwa_' + input.name);
                if (saved && !input.value) {
                    input.value = saved;
                }
            }

            // Save on blur
            input.addEventListener('blur', () => saveInput(input));
        });
    }

    // ==================== CART SYNC ====================
    
    // Detect Cart Updates from Sidebar/Mini-Cart
    $(document.body).on('wc_fragments_refreshed', function() {
        // Only if we are on our custom checkout page
        if ($('#wiwa-checkout-step-1').length > 0 || $('.wiwa-checkout-payment-wrapper').length > 0) {
            // Store a flag in session to know we are reloading due to cart update
            // to potentially show a message "Updated form based on cart changes"
            
            // Check if it was a quantity change vs just an initial load
            // We assume if this event fires AFTER page load, it's a change.
            // But it also fires on page load. We need to distinguish.
            
            // Simple check: Is the side-cart open? 
            // Or just check if the "block-UI" is redundant.
            // Actually, safest is: Check if the number of forms in DOM matches specific data attribute?
            // But 'wc_fragments_refreshed' provides fragments, not the full cart data object usually.
            
            // User requirement: "If quantity changes in sidebar... update traveler info window".
            // Since side-cart.js triggers 'wc_fragments_refreshed' AFTER ajax success.
            // We can trust this event implies a change initiated by the user.
            
            // However, this event ALSO fires on initial page load by WC script.
            // We must filter that out.
            // WC typically sets `wc_fragments_refreshed` triggered by `wc_cart_fragments_params`.
            
            // Workaround: side-cart.js triggers 'wc_update_cart' or we can add a custom event in side-cart.js
            // In side-cart.js (viewed earlier), I saw:
            // $(document.body).trigger('wc_fragment_refresh');
            // $(document.body).trigger('wc_fragments_refreshed');
            
            // I will assume if this event happens > 2 seconds after load, it's a user action.
            if (performance.now() > 2000) {
                 console.log('[Wiwa] Cart updated. Reloading checkout...');
                 window.location.reload();
            }
        }
    });

});
