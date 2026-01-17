/**
 * Partner Portal - Repetitive JavaScript
 * Hantering av repetitiva händelser
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createRepetitiveBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Edit buttons
    document.querySelectorAll('[data-repetitive-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const eventData = this.getAttribute('data-repetitive-edit');
            const event = JSON.parse(eventData);
            openEditModal(event);
        });
    });

    // Delete buttons
    document.querySelectorAll('[data-repetitive-delete]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-repetitive-delete');
            const label = this.getAttribute('data-label');
            openDeleteModal(eventId, label);
        });
    });

    // Table search
    initRepetitiveTableSearch('table-search', '#repetitive-table');

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
function initRepetitiveTableSearch(inputId, tableSelector) {
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

function openCreateModal() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="repetitive-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="repetitive-data"]').getAttribute('content'));

    // Bygg unit options
    let unitOptions = `<option value="">${escapeHtml(labels.select_unit)}</option>`;
    data.units.forEach(function(unit) {
        unitOptions += `<option value="${escapeHtml(unit.id)}">${escapeHtml(unit.name)}</option>`;
    });

    const content = `
        <form method="POST" id="repetitiveForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label for="label">${escapeHtml(labels.label)} *</label>
                <input type="text" name="label" id="label" required>
            </div>

            <div class="form-group">
                <label for="unit_id">${escapeHtml(labels.unit)}</label>
                <select name="unit_id" id="unit_id">
                    ${unitOptions}
                </select>
            </div>

            <div class="form-group">
                <label for="notes">${escapeHtml(labels.notes)}</label>
                <textarea name="notes" id="notes" rows="3"></textarea>
            </div>
        </form>
    `;

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
            const form = document.getElementById('repetitiveForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openCreateModal();
            }
        }
    });
}

function openEditModal(event) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="repetitive-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="repetitive-data"]').getAttribute('content'));

    // Bygg unit options
    let unitOptions = `<option value="">${escapeHtml(labels.select_unit)}</option>`;
    data.units.forEach(function(unit) {
        const selected = (event.unit_id && unit.id == event.unit_id) ? ' selected' : '';
        unitOptions += `<option value="${escapeHtml(unit.id)}"${selected}>${escapeHtml(unit.name)}</option>`;
    });

    const content = `
        <form method="POST" id="repetitiveEditForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="event_id" value="${event.id}">

            <div class="form-group">
                <label for="edit_label">${escapeHtml(labels.label)} *</label>
                <input type="text" name="label" id="edit_label" value="${escapeHtml(event.label || '')}" required>
            </div>

            <div class="form-group">
                <label for="edit_unit_id">${escapeHtml(labels.unit)}</label>
                <select name="unit_id" id="edit_unit_id">
                    ${unitOptions}
                </select>
            </div>

            <div class="form-group">
                <label for="edit_notes">${escapeHtml(labels.notes)}</label>
                <textarea name="notes" id="edit_notes" rows="3">${escapeHtml(event.notes || '')}</textarea>
            </div>
        </form>
    `;

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
            const form = document.getElementById('repetitiveEditForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openEditModal(event);
            }
        }
    });
}

async function openDeleteModal(eventId, label) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="repetitive-labels"]').getAttribute('content'));

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
            <input type="hidden" name="event_id" value="${eventId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
