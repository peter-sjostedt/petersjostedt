/**
 * Partner Portal - Relations JavaScript
 * Hantering av organisationsrelationer (kunder och leverantörer)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createRelationBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Import button
    const importBtn = document.getElementById('importRelationBtn');
    if (importBtn) {
        importBtn.addEventListener('click', openImportModal);
    }

    // Delete buttons
    document.querySelectorAll('[data-relation-delete]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const relationId = this.getAttribute('data-relation-delete');
            const label = this.getAttribute('data-label');
            openDeleteModal(relationId, label);
        });
    });

    // Table search
    initRelationTableSearch('table-search', '#relations-table');

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
function initRelationTableSearch(inputId, tableSelector) {
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
    const labels = JSON.parse(document.querySelector('meta[name="relations-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="relations-data"]').getAttribute('content'));

    const isCustomers = data.tab === 'customers';
    const relationType = isCustomers ? 'customer' : 'supplier';
    const modalTitle = isCustomers ? labels.modal_create_customer : labels.modal_create_supplier;

    // Bygg organization options
    let orgOptions = `<option value="">${escapeHtml(labels.select_org)}</option>`;
    data.organizations.forEach(function(org) {
        orgOptions += `<option value="${escapeHtml(org.id)}">${escapeHtml(org.name)} (${escapeHtml(org.id)})</option>`;
    });

    const content = `
        <form method="POST" id="relationForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="relation_type" value="${relationType}">

            <div class="form-group">
                <label for="partner_org_id">${escapeHtml(labels.organization)} *</label>
                <select name="partner_org_id" id="partner_org_id" required>
                    ${orgOptions}
                </select>
            </div>
        </form>
    `;

    Modal.custom('info', modalTitle, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('relationForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openCreateModal();
            }
        }
    });
}

async function openDeleteModal(relationId, label) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="relations-labels"]').getAttribute('content'));

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
            <input type="hidden" name="relation_id" value="${relationId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function openImportModal() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="relations-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="relations-data"]').getAttribute('content'));

    const isCustomers = data.tab === 'customers';
    const typeLabel = isCustomers ? labels.type_customer : labels.type_supplier;

    // Bygg lista över förväntade kolumner
    let columnsHtml = '<ul>';
    columnsHtml += `<li><strong>org_id</strong> (${escapeHtml(labels.import_org_id_hint)})</li>`;
    columnsHtml += `<li><strong>name</strong> (${escapeHtml(labels.import_name_hint)})</li>`;
    columnsHtml += `<li><strong>Status</strong> (${escapeHtml(labels.import_status_hint)})</li>`;
    columnsHtml += '</ul>';

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
