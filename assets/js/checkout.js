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

    // ==================== "I AM THE MAIN TRAVELER" TOGGLE ====================

    const sameAsBillingToggle = document.getElementById('wiwa_same_as_billing');
    if (sameAsBillingToggle) {
        const toggleCard = document.getElementById('wiwa-traveler-toggle');
        const fieldMap = JSON.parse(sameAsBillingToggle.dataset.fieldMap || '{}');
        const pax1Indices = JSON.parse(sameAsBillingToggle.dataset.pax1Indices || '[]');

        /**
         * Find the billing source element by field key.
         * Handles regular inputs, selects, and compound sub-selects (_code, _type).
         */
        function getBillingEl(billingKey) {
            // Direct match by name or id
            return document.querySelector(
                '[name="' + billingKey + '"], #' + billingKey
            );
        }

        /**
         * Find guest target element(s) for Passenger 1 across all tours.
         * guestKey = e.g. "guest_first_name", pax1Indices = [101, 201...]
         * Names in DOM: "guest_first_name_101", "guest_first_name_201", …
         */
        function getGuestEls(guestKey) {
            const targets = [];
            pax1Indices.forEach(function (idx) {
                const el = document.querySelector('[name="guest_' + guestKey + '_' + idx + '"]')
                        || document.querySelector('[name="' + guestKey + '_' + idx + '"]');
                if (el) targets.push(el);
            });
            return targets;
        }

        /**
         * Copy value from billing field → all Pax1 guest fields
         */
        function syncField(billingKey, guestKey) {
            const src = getBillingEl(billingKey);
            if (!src) return;

            const guests = getGuestEls(guestKey);
            guests.forEach(function (guest) {
                guest.value = src.value;
                // Trigger Select2 update if it's a select
                if (guest.tagName === 'SELECT' && window.jQuery) {
                    jQuery(guest).val(src.value).trigger('change.select2');
                }
                // Clear any validation error
                guest.classList.remove('error');
                var formField = guest.closest('.form-field');
                if (formField) formField.classList.remove('has-error');
            });
        }

        /**
         * Sync ALL mapped fields
         */
        function syncAllFields() {
            Object.keys(fieldMap).forEach(function (billingKey) {
                syncField(billingKey, fieldMap[billingKey]);
            });
        }

        /**
         * Lock Pax1 fields (add overlay + readonly)
         */
        function lockPax1Fields() {
            Object.keys(fieldMap).forEach(function (billingKey) {
                var guestKey = fieldMap[billingKey];
                var guests = getGuestEls(guestKey);
                guests.forEach(function (guest) {
                    var formField = guest.closest('.form-field');
                    if (formField) formField.classList.add('wiwa-linked-field');
                    guest.readOnly = true;
                    guest.setAttribute('tabindex', '-1');
                });
            });
        }

        /**
         * Unlock Pax1 fields (remove overlay + readonly)
         */
        function unlockPax1Fields() {
            Object.keys(fieldMap).forEach(function (billingKey) {
                var guestKey = fieldMap[billingKey];
                var guests = getGuestEls(guestKey);
                guests.forEach(function (guest) {
                    var formField = guest.closest('.form-field');
                    if (formField) formField.classList.remove('wiwa-linked-field');
                    guest.readOnly = false;
                    guest.removeAttribute('tabindex');
                });
            });
        }

        /**
         * Clear Pax1 fields
         */
        function clearPax1Fields() {
            Object.keys(fieldMap).forEach(function (billingKey) {
                var guestKey = fieldMap[billingKey];
                var guests = getGuestEls(guestKey);
                guests.forEach(function (guest) {
                    guest.value = '';
                    if (guest.tagName === 'SELECT' && window.jQuery) {
                        jQuery(guest).val('').trigger('change.select2');
                    }
                });
            });
        }

        /**
         * Activate the toggle
         */
        function activateSync() {
            syncAllFields();
            lockPax1Fields();
            if (toggleCard) toggleCard.classList.add('is-active');
            sessionStorage.setItem('wiwa_same_as_billing', 'true');
        }

        /**
         * Deactivate the toggle
         */
        function deactivateSync() {
            unlockPax1Fields();
            clearPax1Fields();
            if (toggleCard) toggleCard.classList.remove('is-active');
            sessionStorage.setItem('wiwa_same_as_billing', 'false');
        }

        // Toggle change handler
        sameAsBillingToggle.addEventListener('change', function () {
            if (this.checked) {
                activateSync();
            } else {
                deactivateSync();
            }
        });

        // Real-time sync: when billing fields change, propagate to pax1
        Object.keys(fieldMap).forEach(function (billingKey) {
            var src = getBillingEl(billingKey);
            if (!src) return;

            var handler = function () {
                if (!sameAsBillingToggle.checked) return;
                syncField(billingKey, fieldMap[billingKey]);
            };

            src.addEventListener('input', handler);
            src.addEventListener('change', handler);

            // Also listen for Select2 changes
            if (src.tagName === 'SELECT' && window.jQuery) {
                jQuery(src).on('change', handler);
            }
        });

        // Restore persisted toggle state
        if (sessionStorage.getItem('wiwa_same_as_billing') === 'true') {
            sameAsBillingToggle.checked = true;
            // Delay slightly so Select2 and other controls have initialized
            setTimeout(function () {
                activateSync();
            }, 300);
        }
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
                    // Clear all wiwa_ sessionStorage keys — data is now saved server-side
                    Object.keys(sessionStorage).forEach(k => {
                        if (k.startsWith('wiwa_')) sessionStorage.removeItem(k);
                    });
                    window.location.href = this.getAttribute('action');
                } else {
                    const errorMessage = wiwaCheckout && wiwaCheckout.strings && wiwaCheckout.strings.errorSavingData ? wiwaCheckout.strings.errorSavingData : 'Error guardando datos: ';
                    const unknownError = wiwaCheckout && wiwaCheckout.strings && wiwaCheckout.strings.unknownError ? wiwaCheckout.strings.unknownError : 'Error desconocido';
                    alert(errorMessage + (data.data || unknownError));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerText = originalText;
                    }
                }
            })
            .catch(err => {
                console.error(err);
                const connectionError = wiwaCheckout && wiwaCheckout.strings && wiwaCheckout.strings.connectionError ? wiwaCheckout.strings.connectionError : 'Error de conexión.';
                alert(connectionError);
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
    
    // Autosave inputs to session storage & restore from server (for returning customers & reloads)
    if (form1) {
        // Cleanup old localStorage items from previous versions
        Object.keys(localStorage).forEach(key => {
            if (key.startsWith('wiwa_')) {
                localStorage.removeItem(key);
            }
        });

        const saveInput = (input) => {
            if (!input.name) return;
            if (input.type === 'checkbox' || input.type === 'radio') {
                sessionStorage.setItem('wiwa_' + input.name, input.checked ? 'true' : 'false');
            } else {
                sessionStorage.setItem('wiwa_' + input.name, input.value || '');
            }
        };

        const serverData = window.wiwaStep1Data || {};

        $$('input, select, textarea', form1).forEach(input => {
            if (!input.name || input.type === 'hidden') return;

            let saved = sessionStorage.getItem('wiwa_' + input.name);

            if (saved === null) {
                // Not in session storage, try server data (if arriving via 'Back' from Step 2)
                if (serverData[input.name] !== undefined) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = true; // If key exists in POST data, it was checked
                        saveInput(input); // Sync immediately to sessionStorage
                    } else {
                        input.value = serverData[input.name];
                        saveInput(input);
                        if (input.tagName === 'SELECT' && window.jQuery) {
                            jQuery(input).trigger('change.select2');
                        }
                    }
                }
            } else {
                // Restore from SessionStorage
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = (saved === 'true');
                } else {
                    input.value = saved;
                    if (input.tagName === 'SELECT' && window.jQuery) {
                        jQuery(input).trigger('change.select2');
                    }
                }
            }

            // Save on change/blur/input
            input.addEventListener('blur', () => saveInput(input));
            input.addEventListener('change', () => saveInput(input));
            if (input.tagName !== 'SELECT' && input.type !== 'checkbox' && input.type !== 'radio') {
                input.addEventListener('input', () => saveInput(input));
            }
        });
    }

});
