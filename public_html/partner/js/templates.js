/**
 * Partner Portal - Templates JavaScript
 * Hantering av händelsemallar
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createTemplateBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Import button
    const importBtn = document.getElementById('importTemplateBtn');
    if (importBtn) {
        importBtn.addEventListener('click', openImportModal);
    }

    // Edit buttons
    document.querySelectorAll('[data-template-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const templateData = this.getAttribute('data-template-edit');
            const template = JSON.parse(templateData);
            openEditModal(template);
        });
    });

    // Delete buttons
    document.querySelectorAll('[data-template-delete]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-delete');
            const label = this.getAttribute('data-label');
            openDeleteModal(templateId, label);
        });
    });

    // Table search
    initTemplateTableSearch('table-search', '#templates-table');

    // Close modal on overlay click
    const modalOverlay = document.getElementById('modal-overlay');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                modalOverlay.classList.add('hidden');
            }
        });
    }
});

/**
 * Initialize table search functionality
 */
function initTemplateTableSearch(inputId, tableSelector) {
    const input = document.getElementById(inputId);
    const table = document.querySelector(tableSelector);
    const clearBtn = document.querySelector('.search-clear');

    if (!input || !table) return;

    if (clearBtn) {
        clearBtn.style.display = 'none';
    }

    input.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });

        if (clearBtn) {
            clearBtn.style.display = this.value ? 'block' : 'none';
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            input.value = '';
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function buildFormContent(labels, data, template = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const isEdit = template !== null;
    const formId = isEdit ? 'templateEditForm' : 'templateForm';
    const action = isEdit ? 'update' : 'create';

    // Bygg event type options
    let eventTypeOptions = `<option value="">${escapeHtml(labels.select_event_type)}</option>`;
    data.eventTypes.forEach(function(et) {
        const selected = (template && template.event_type_id == et.id) ? ' selected' : '';
        eventTypeOptions += `<option value="${et.id}" data-is-transfer="${et.is_transfer}"${selected}>${escapeHtml(et.name)}</option>`;
    });

    // Bygg unit options
    let unitOptions = `<option value="">${escapeHtml(labels.select_unit)}</option>`;
    data.units.forEach(function(unit) {
        const selected = (template && template.unit_id == unit.id) ? ' selected' : '';
        unitOptions += `<option value="${unit.id}"${selected}>${escapeHtml(unit.name)}</option>`;
    });

    // Bygg target unit options
    let targetUnitOptions = `<option value="">${escapeHtml(labels.select_target_unit)}</option>`;
    data.units.forEach(function(unit) {
        const selected = (template && template.target_unit_id == unit.id) ? ' selected' : '';
        targetUnitOptions += `<option value="${unit.id}"${selected}>${escapeHtml(unit.name)}</option>`;
    });

    const isReusableChecked = template ? (template.is_reusable ? ' checked' : '') : ' checked';

    let hiddenFields = `
        <input type="hidden" name="csrf_token" value="${csrfToken}">
        <input type="hidden" name="action" value="${action}">
    `;
    if (isEdit) {
        hiddenFields += `<input type="hidden" name="template_id" value="${template.id}">`;
    }

    return `
        <form method="POST" id="${formId}">
            ${hiddenFields}

            <div class="form-group">
                <label for="${isEdit ? 'edit_' : ''}label">${escapeHtml(labels.label)} *</label>
                <input type="text" name="label" id="${isEdit ? 'edit_' : ''}label" value="${escapeHtml(template?.label || '')}" required>
            </div>

            <div class="form-group">
                <label for="${isEdit ? 'edit_' : ''}event_type_id">${escapeHtml(labels.event_type)} *</label>
                <select name="event_type_id" id="${isEdit ? 'edit_' : ''}event_type_id" required onchange="toggleTargetUnit(this)">
                    ${eventTypeOptions}
                </select>
            </div>

            <div class="form-group">
                <label for="${isEdit ? 'edit_' : ''}unit_id">${escapeHtml(labels.unit)}</label>
                <select name="unit_id" id="${isEdit ? 'edit_' : ''}unit_id">
                    ${unitOptions}
                </select>
            </div>

            <div class="form-group" id="${isEdit ? 'edit_' : ''}target_unit_group" style="display: none;">
                <label for="${isEdit ? 'edit_' : ''}target_unit_id">${escapeHtml(labels.target_unit)}</label>
                <select name="target_unit_id" id="${isEdit ? 'edit_' : ''}target_unit_id">
                    ${targetUnitOptions}
                </select>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_reusable" id="${isEdit ? 'edit_' : ''}is_reusable" value="1"${isReusableChecked}>
                    ${escapeHtml(labels.is_reusable)}
                </label>
                <small class="form-help">${escapeHtml(labels.is_reusable_help)}</small>
            </div>

            <div class="form-group">
                <label for="${isEdit ? 'edit_' : ''}notes">${escapeHtml(labels.notes)}</label>
                <textarea name="notes" id="${isEdit ? 'edit_' : ''}notes" rows="3">${escapeHtml(template?.notes || '')}</textarea>
            </div>
        </form>
    `;
}

// Global function to toggle target unit visibility
window.toggleTargetUnit = function(select) {
    const isEdit = select.id.startsWith('edit_');
    const prefix = isEdit ? 'edit_' : '';
    const targetGroup = document.getElementById(prefix + 'target_unit_group');
    const selectedOption = select.options[select.selectedIndex];
    const isTransfer = selectedOption?.getAttribute('data-is-transfer') === 'true';

    if (targetGroup) {
        targetGroup.style.display = isTransfer ? 'block' : 'none';
    }
};

function openCreateModal() {
    const labels = JSON.parse(document.querySelector('meta[name="templates-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="templates-data"]').getAttribute('content'));

    const content = buildFormContent(labels, data);

    Modal.custom('info', labels.modal_create, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('templateForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openCreateModal();
            }
        }
    });

    // Check initial state of event type select
    setTimeout(function() {
        const select = document.getElementById('event_type_id');
        if (select) {
            toggleTargetUnit(select);
        }
    }, 100);
}

function openEditModal(template) {
    const labels = JSON.parse(document.querySelector('meta[name="templates-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="templates-data"]').getAttribute('content'));

    const content = buildFormContent(labels, data, template);

    Modal.custom('info', labels.modal_edit, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.update, class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('templateEditForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openEditModal(template);
            }
        }
    });

    // Check initial state of event type select
    setTimeout(function() {
        const select = document.getElementById('edit_event_type_id');
        if (select) {
            toggleTargetUnit(select);
        }
    }, 100);
}

async function openDeleteModal(templateId, label) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="templates-labels"]').getAttribute('content'));

    const message = `${escapeHtml(labels.confirm_delete)}<br><strong>${escapeHtml(label)}</strong>`;

    const confirmed = await Modal.confirm(labels.modal_delete, message, {
        html: true,
        confirmText: labels.delete,
        cancelText: labels.cancel,
        danger: true
    });

    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="template_id" value="${templateId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function openImportModal() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="templates-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="templates-data"]').getAttribute('content'));

    // Bygg lista över förväntade kolumner
    let columnsHtml = '<ul>';
    columnsHtml += `<li><strong>${escapeHtml(labels.label)}</strong> (${escapeHtml(labels.import_label_hint)})</li>`;
    columnsHtml += `<li><strong>${escapeHtml(labels.event_type)}</strong> (${escapeHtml(labels.import_event_type_hint)})</li>`;
    columnsHtml += `<li>${escapeHtml(labels.unit)} (${escapeHtml(labels.import_unit_hint)})</li>`;
    columnsHtml += `<li>${escapeHtml(labels.target_unit)} (${escapeHtml(labels.import_target_unit_hint)})</li>`;
    columnsHtml += `<li>${escapeHtml(labels.is_reusable)} (${escapeHtml(labels.import_reusable_hint)})</li>`;
    columnsHtml += `<li>${escapeHtml(labels.notes)}</li>`;
    columnsHtml += '</ul>';

    // Visa tillgängliga händelsetyper
    let eventTypesHtml = '<p class="text-muted"><small>';
    eventTypesHtml += data.eventTypes.map(et => `${escapeHtml(et.code)} - ${escapeHtml(et.name)}`).join(', ');
    eventTypesHtml += '</small></p>';

    const content = `
        <form method="POST" id="importForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="import">

            <div class="form-group">
                <label for="modal_csv_file">${escapeHtml(labels.import_select_file)} *</label>
                <input type="file" id="modal_csv_file" name="csv_file" accept=".csv" required>
                <small class="form-help">${escapeHtml(labels.import_file_hint)}</small>
            </div>

            <div class="import-info">
                <strong>${escapeHtml(labels.import_columns)}:</strong>
                ${columnsHtml}
                ${eventTypesHtml}
            </div>
        </form>
    `;

    Modal.custom('info', labels.modal_import, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.import, class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('importForm');
            const fileInput = document.getElementById('modal_csv_file');

            if (form && fileInput && fileInput.files.length > 0) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openImportModal();
            }
        }
    });
}
