/**
 * Wiwa Admin Fields Manager JS v1.0
 * Interactive field management
 */
jQuery(document).ready(function ($) {
    'use strict';

    // ===== Sortable Fields =====
    function initSortable() {
        $('.wiwa-fields-table tbody').sortable({
            handle: '.col-handle',
            placeholder: 'ui-sortable-placeholder',
            helper: function (e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function (index) {
                    $(this).width($originals.eq(index).width());
                });
                return $helper;
            },
            update: function () {
                // Update order inputs
                $('.wiwa-fields-table tbody tr').each(function (index) {
                    $(this).find('.wiwa-field-order').val(index);
                });

                // Trigger change to enable save
                $('#wiwa-fields-form').trigger('change');
            }
        });
    }

    initSortable();

    // ===== Type Change Handler =====
    $(document).on('change', '.wiwa-field-select[name*="[type]"]', function () {
        var $select = $(this);
        var type = $select.val();
        var $row = $select.closest('tr');

        // Update type badge if exists
        updateTypeBadge($row, type);

        // Show/hide options based on type
        if (type === 'select') {
            // Could show options editor
        }
    });

    function updateTypeBadge($row, type) {
        var $badge = $row.find('.wiwa-type-badge');
        if ($badge.length) {
            $badge.removeClass(function (index, className) {
                return (className.match(/(^|\s)type-\S+/g) || []).join(' ');
            }).addClass('type-' + type);
        }
    }

    // Generate hidden inputs for select options
    function generateOptionsInputs(group, key, options) {
        if (!options || typeof options !== 'object' || Object.keys(options).length === 0) {
            return '';
        }
        var inputs = '';
        $.each(options, function (optKey, optLabel) {
            inputs += '<input type="hidden" name="wiwa_fields[' + group + '][' + key + '][options][' + optKey + ']" value="' + optLabel.replace(/"/g, '&quot;') + '">';
        });
        return inputs;
    }

    // ===== Add Field Modal =====
    var $modal = null;

    function createModal() {
        if ($modal) return;

        var fieldTypes = window.wiwaFieldTypes || {};
        var typeOptions = '';

        $.each(fieldTypes, function (key, label) {
            var icon = getTypeIcon(key);
            typeOptions += '<option value="' + key + '">' + icon + ' ' + label + '</option>';
        });

        var modalHtml = `
            <div class="wiwa-modal-overlay" id="wiwa-add-field-modal" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); z-index: 999999; display: flex; align-items: center; justify-content: center;">
                <div class="wiwa-modal">
                    <div class="wiwa-modal-header">
                        <h3>Agregar Nuevo Campo</h3>
                        <button type="button" class="wiwa-modal-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="wiwa-modal-body">
                        <div class="wiwa-form-group">
                            <label>Identificador del Campo (key)</label>
                            <input type="text" id="new-field-key" class="wiwa-field-input" placeholder="ej: billing_custom_field">
                            <p class="description">Usar snake_case sin espacios</p>
                        </div>
                        <div class="wiwa-form-row">
                            <div class="wiwa-form-group">
                                <label>Etiqueta</label>
                                <input type="text" id="new-field-label" class="wiwa-field-input" placeholder="ej: Campo Personalizado">
                            </div>
                            <div class="wiwa-form-group">
                                <label>Tipo</label>
                                <select id="new-field-type" class="wiwa-field-select">
                                    ${typeOptions}
                                </select>
                            </div>
                        </div>
                        <div class="wiwa-form-row">
                            <div class="wiwa-form-group">
                                <label>Placeholder</label>
                                <input type="text" id="new-field-placeholder" class="wiwa-field-input" placeholder="Texto de ayuda">
                            </div>
                            <div class="wiwa-form-group">
                                <label>Posición</label>
                                <select id="new-field-position" class="wiwa-field-select">
                                    <option value="full">Ancho completo</option>
                                    <option value="left">Izquierda (50%)</option>
                                    <option value="right">Derecha (50%)</option>
                                </select>
                            </div>
                        </div>
                        <div class="wiwa-form-group wiwa-options-group" id="options-group" style="display:none;">
                            <label>Opciones del Selector</label>
                            <textarea id="new-field-options" class="wiwa-field-input" rows="4" placeholder="Opción 1|Opción 2|Opción 3"></textarea>
                            <p class="description">Separa las opciones con | (pipe). Ej: Pequeño|Mediano|Grande</p>
                        </div>
                        <div class="wiwa-form-row">
                            <div class="wiwa-form-group">
                                <label>
                                    <input type="checkbox" id="new-field-required" checked> Requerido
                                </label>
                            </div>
                            <div class="wiwa-form-group">
                                <label>
                                    <input type="checkbox" id="new-field-enabled" checked> Activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="wiwa-modal-footer">
                        <button type="button" class="button wiwa-modal-cancel">Cancelar</button>
                        <button type="button" class="button button-primary wiwa-modal-add">Agregar Campo</button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $modal = $('#wiwa-add-field-modal');
    }

    function getTypeIcon(type) {
        var icons = {
            'text': '📝',
            'email': '📧',
            'tel': '📞',
            'number': '#️⃣',
            'date': '📅',
            'select': '☰',
            'textarea': '📄',
            'country': '🌍',
            'phone_code': '📱',
            'phone': '📞',
            'document_type': '🪪',
            'document': '📋'
        };
        return icons[type] || '📝';
    }

    function openModal(group) {
        createModal();
        $modal.data('group', group);
        $modal.addClass('active');
        $('#new-field-key').val('').focus();
        $('#new-field-label').val('');
        $('#new-field-placeholder').val('');
        $('#new-field-type').val('text');
        $('#new-field-position').val('full');
        $('#new-field-required').prop('checked', true);
        $('#new-field-enabled').prop('checked', true);
    }

    function closeModal() {
        if ($modal) {
            $modal.removeClass('active');
        }
    }

    // Open modal
    $(document).on('click', '.wiwa-add-field-btn', function () {
        var group = $(this).data('group');
        openModal(group);
    });

    // Close modal
    $(document).on('click', '.wiwa-modal-close, .wiwa-modal-cancel, .wiwa-modal-overlay', function (e) {
        if (e.target === this || $(this).hasClass('wiwa-modal-close') || $(this).hasClass('wiwa-modal-cancel')) {
            closeModal();
        }
    });

    // Prevent modal close on modal body click
    $(document).on('click', '.wiwa-modal', function (e) {
        e.stopPropagation();
    });

    // Show/hide options field based on type selection
    $(document).on('change', '#new-field-type', function () {
        var type = $(this).val();
        if (type === 'select') {
            $('#options-group').slideDown(200);
        } else {
            $('#options-group').slideUp(200);
        }
    });

    // Add field
    $(document).on('click', '.wiwa-modal-add', function () {
        var group = $modal.data('group');
        var key = $('#new-field-key').val().trim().replace(/\s+/g, '_').toLowerCase();
        var label = $('#new-field-label').val().trim();
        var type = $('#new-field-type').val();
        var placeholder = $('#new-field-placeholder').val().trim();
        var position = $('#new-field-position').val();
        var required = $('#new-field-required').is(':checked');
        var enabled = $('#new-field-enabled').is(':checked');

        // Get options for select type
        var options = {};
        if (type === 'select') {
            var optionsText = $('#new-field-options').val().trim();
            if (optionsText) {
                var optionsList = optionsText.split('|');
                optionsList.forEach(function (opt, idx) {
                    opt = opt.trim();
                    if (opt) {
                        var optKey = opt.toLowerCase().replace(/[^a-z0-9]+/g, '_');
                        options[optKey] = opt;
                    }
                });
            }
        }

        if (!key) {
            alert('Por favor ingresa un identificador para el campo');
            $('#new-field-key').focus();
            return;
        }

        if (!label) {
            label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        // Check if key exists
        if ($('[data-field-key="' + key + '"]').length) {
            alert('Ya existe un campo con ese identificador');
            return;
        }

        // Add row to table
        addFieldRow(group, key, {
            label: label,
            type: type,
            placeholder: placeholder,
            position: position,
            required: required,
            enabled: enabled,
            options: options
        });

        closeModal();
        initSortable();
    });

    function addFieldRow(group, key, field) {
        var $table = $('#' + group + '-fields-table tbody');
        var typeIcon = getTypeIcon(field.type);

        var rowHtml = `
            <tr data-field-key="${key}">
                <td class="col-handle"><span class="dashicons dashicons-move"></span></td>
                <td class="col-key"><code>${key}</code></td>
                <td class="col-label">
                    <input type="text" name="wiwa_fields[${group}][${key}][label]" value="${field.label}" class="wiwa-field-input">
                </td>
                <td class="col-placeholder">
                    <input type="text" name="wiwa_fields[${group}][${key}][placeholder]" value="${field.placeholder || ''}" class="wiwa-field-input" placeholder="Placeholder...">
                </td>
                <td class="col-type">
                    <span class="wiwa-type-badge type-${field.type}">${typeIcon} ${field.type}</span>
                    <input type="hidden" name="wiwa_fields[${group}][${key}][type]" value="${field.type}">
                    ${generateOptionsInputs(group, key, field.options)}
                </td>
                <td class="col-position">
                    <select name="wiwa_fields[${group}][${key}][position]" class="wiwa-field-select wiwa-position-select">
                        <option value="full" ${field.position === 'full' ? 'selected' : ''}>100%</option>
                        <option value="left" ${field.position === 'left' ? 'selected' : ''}>Izq</option>
                        <option value="right" ${field.position === 'right' ? 'selected' : ''}>Der</option>
                    </select>
                </td>
                <td class="col-required">
                    <label class="wiwa-toggle-small">
                        <input type="checkbox" name="wiwa_fields[${group}][${key}][required]" value="1" ${field.required ? 'checked' : ''}>
                        <span class="wiwa-toggle-slider-small"></span>
                    </label>
                </td>
                <td class="col-enabled">
                    <label class="wiwa-toggle-small">
                        <input type="checkbox" name="wiwa_fields[${group}][${key}][enabled]" value="1" ${field.enabled ? 'checked' : ''}>
                        <span class="wiwa-toggle-slider-small"></span>
                    </label>
                </td>
                <td class="col-actions">
                    <button type="button" class="button button-small wiwa-delete-field" data-key="${key}" data-group="${group}" title="Eliminar">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        `;

        $table.append(rowHtml);
    }

    // ===== Delete Field =====
    $(document).on('click', '.wiwa-delete-field', function () {
        var $btn = $(this);
        var key = $btn.data('key');

        if (confirm('¿Eliminar el campo "' + key + '"?')) {
            $btn.closest('tr').fadeOut(300, function () {
                $(this).remove();
            });
        }
    });

    // ===== Form Submit with AJAX =====
    $('#wiwa-fields-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('.wiwa-save-fields-btn');
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Guardando...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=wiwa_save_fields',
            success: function (response) {
                if (response.success) {
                    $btn.html('<span class="dashicons dashicons-yes"></span> ¡Guardado!');
                    setTimeout(function () {
                        $btn.html(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    alert('Error: ' + (response.data.message || 'Error al guardar'));
                    $btn.html(originalText).prop('disabled', false);
                }
            },
            error: function () {
                alert('Error de conexión');
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Spin animation for loading
    $('<style>.spin { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>').appendTo('head');
});
