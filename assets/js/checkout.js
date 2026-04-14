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

    // ==================== BILLING → PASSENGER COPY SYSTEM ====================

    /**
     * Core mapping: guest field key fragments → billing field name
     * Handles both default keys (guest_first_name) and OVA keys (first_name)
     */
    var GUEST_TO_BILLING = {
        'first_name':   'billing_first_name',
        'last_name':    'billing_last_name',
        'email':        'billing_email',
        'phone':        'billing_phone',
        'nationality':  'billing_country',
        'country':      'billing_country',
        'passport':     'billing_document',
        'document':     'billing_document',
        'nombre':       'billing_first_name',
        'apellido':     'billing_last_name',
        'apellidos':    'billing_last_name',
        'correo':       'billing_email',
        'telefono':     'billing_phone',
        'nacionalidad': 'billing_country',
        'documento':    'billing_document',
        'id':           'billing_document'
    };

    /**
     * Parse a guest field name to extract its core key, index, and suffix.
     * Handles patterns:
     *   guest_first_name_101          → { key: 'first_name', idx: '101', suffix: '' }
     *   guest_guest_passport_101      → { key: 'passport', idx: '101', suffix: '' }
     *   guest_guest_passport_101_type → { key: 'passport', idx: '101', suffix: '_type' }
     *   guest_guest_phone_101_code    → { key: 'phone', idx: '101', suffix: '_code' }
     */
    function parseGuestFieldName(name) {
        if (!name || !name.startsWith('guest_')) return null;

        // Known suffixes that come AFTER the index
        var suffixes = ['_type', '_code'];
        var suffix = '';

        for (var i = 0; i < suffixes.length; i++) {
            if (name.endsWith(suffixes[i])) {
                suffix = suffixes[i];
                name = name.slice(0, -suffix.length);
                break;
            }
        }

        // Extract the index (last numeric segment after the last underscore)
        var lastUnderscore = name.lastIndexOf('_');
        if (lastUnderscore === -1) return null;

        var idx = name.substring(lastUnderscore + 1);
        if (!/^\d+$/.test(idx)) return null;

        // Everything between 'guest_' and '_{idx}' is the field key
        var keyPart = name.substring(6, lastUnderscore); // 6 = 'guest_'.length

        // Strip a second 'guest_' prefix if present (default fields: guest_guest_first_name)
        if (keyPart.startsWith('guest_')) {
            keyPart = keyPart.substring(6);
        }

        return { key: keyPart, idx: idx, suffix: suffix };
    }

    /**
     * Find the billing source element for a given billing field name.
     * Handles both compound (inside combined-input-group) and standalone fields.
     */
    function findBillingSource(billingName) {
        // Direct match by name attribute
        var el = document.querySelector('[name="' + billingName + '"]');
        if (el) return el;

        // Fallback: try without "billing_" prefix variations
        return null;
    }

    /**
     * Copy billing data → a specific passenger block (by DOM element).
     * Returns the count of fields successfully copied.
     */
    function copyBillingToBlock(passengerBlock) {
        var fields = passengerBlock.querySelectorAll('input, select, textarea');
        var copied = 0;

        console.debug('[WiwaCopy] Processing block:', passengerBlock.dataset.guestIndex);

        fields.forEach(function (field) {
            if (!field.name || field.type === 'hidden') return;

            var parsed = parseGuestFieldName(field.name);
            if (!parsed) {
                console.debug('[WiwaCopy]   Skip (no parse):', field.name);
                return;
            }

            // Determine the billing source field name
            var billingKey = GUEST_TO_BILLING[parsed.key];
            if (!billingKey) {
                console.debug('[WiwaCopy]   Skip (no mapping for key "' + parsed.key + '"):', field.name);
                return;
            }

            // For compound fields, append the suffix to billing key
            var billingName = billingKey + parsed.suffix;
            var src = findBillingSource(billingName);

            if (!src) {
                console.debug('[WiwaCopy]   Skip (billing not found "' + billingName + '"):', field.name);
                return;
            }

            // For text/email/tel inputs, skip if empty
            // For selects, skip ONLY if value is truly empty string AND it's the first option (placeholder)
            var srcVal = src.value;
            if (src.tagName === 'SELECT') {
                // Allow copying even placeholder values for document type selects
                // Only skip if value is empty AND we have a proper empty-string placeholder
                if (srcVal === '' && src.options.length > 0 && src.options[0].value === '') {
                    console.debug('[WiwaCopy]   Skip (select placeholder):', billingName, '→ val=""');
                    return;
                }
            } else {
                if (!srcVal) {
                    console.debug('[WiwaCopy]   Skip (empty value):', billingName);
                    return;
                }
            }

            // Copy value
            field.value = srcVal;
            console.debug('[WiwaCopy]   ✓ Copied:', billingName, '→', field.name, '=', srcVal);

            // Trigger Select2 update for selects
            if (field.tagName === 'SELECT' && window.jQuery) {
                jQuery(field).val(srcVal).trigger('change.select2');
            }

            // Trigger input event for validation listeners
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));

            // Clear validation errors
            field.classList.remove('error');
            var formField = field.closest('.form-field');
            if (formField) formField.classList.remove('has-error');

            copied++;
        });

        console.debug('[WiwaCopy] Total copied:', copied);
        return copied;
    }

    /**
     * Show a brief "✓ Copied!" feedback animation on a copy button
     */
    function showCopyFeedback(btn) {
        btn.classList.add('is-copied');
        setTimeout(function () {
            btn.classList.remove('is-copied');
        }, 2000);
    }

    // ── Per-Passenger "Use my data" buttons ──
    var copyButtons = $$('.wiwa-copy-billing-btn');
    copyButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var block = btn.closest('.passenger-block');
            if (!block) return;

            var count = copyBillingToBlock(block);
            if (count > 0) {
                showCopyFeedback(btn);
            }
        });
    });

    // ── Global "I am the main traveler" Toggle ──
    var sameAsBillingToggle = document.getElementById('wiwa_same_as_billing');
    if (sameAsBillingToggle) {
        var toggleCard = document.getElementById('wiwa-traveler-toggle');
        var feedbackEl = document.getElementById('traveler-toggle-feedback');

        function applyGlobalPreFill() {
            // Find all passenger blocks that are Passenger 1 (pax index ends in 1)
            var allBlocks = $$('.passenger-block[data-guest-index]');
            var totalFilled = 0;

            allBlocks.forEach(function (block) {
                var idx = block.dataset.guestIndex;
                // Pax 1 indices end in 01: 1, 101, 201, etc.
                if (parseInt(idx) % 100 === 1) {
                    totalFilled += copyBillingToBlock(block);
                }
            });

            // Show feedback
            if (feedbackEl && totalFilled > 0) {
                var msg = (window.wiwaCheckout && wiwaCheckout.strings && wiwaCheckout.strings.fieldsPreFilled)
                    ? wiwaCheckout.strings.fieldsPreFilled
                    : 'fields pre-filled';
                feedbackEl.textContent = '✓ ' + totalFilled + ' ' + msg;
                feedbackEl.classList.add('show');
                setTimeout(function () {
                    feedbackEl.classList.remove('show');
                }, 3000);
            }
        }

        sameAsBillingToggle.addEventListener('change', function () {
            if (this.checked) {
                applyGlobalPreFill();
                if (toggleCard) toggleCard.classList.add('is-active');
                sessionStorage.setItem('wiwa_same_as_billing', 'true');
            } else {
                if (toggleCard) toggleCard.classList.remove('is-active');
                sessionStorage.setItem('wiwa_same_as_billing', 'false');
                // Do NOT clear fields — non-destructive OFF
            }
        });

        // Real-time sync: when billing fields change while toggle is ON, re-fill Pax 1
        var billingInputs = $$('[name^="billing_"]');
        billingInputs.forEach(function (input) {
            var handler = function () {
                if (!sameAsBillingToggle.checked) return;
                applyGlobalPreFill();
            };
            input.addEventListener('input', handler);
            input.addEventListener('change', handler);
            if (input.tagName === 'SELECT' && window.jQuery) {
                jQuery(input).on('change', handler);
            }
        });

        // Restore persisted toggle state
        if (sessionStorage.getItem('wiwa_same_as_billing') === 'true') {
            sameAsBillingToggle.checked = true;
            if (toggleCard) toggleCard.classList.add('is-active');
            setTimeout(function () {
                applyGlobalPreFill();
            }, 400);
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
