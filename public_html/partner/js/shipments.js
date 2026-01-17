/**
 * Partner Portal - Shipments JavaScript
 * Hantering av försändelser (utgående och inkommande)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createShipmentBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Import button
    const importBtn = document.getElementById('importShipmentBtn');
    if (importBtn) {
        importBtn.addEventListener('click', openImportModal);
    }

    // Edit buttons
    document.querySelectorAll('[data-shipment-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const shipmentData = this.getAttribute('data-shipment-edit');
            const shipment = JSON.parse(shipmentData);
            openEditModal(shipment);
        });
    });

    // Delete buttons
    document.querySelectorAll('[data-shipment-delete]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const shipmentId = this.getAttribute('data-shipment-delete');
            const label = this.getAttribute('data-label');
            openDeleteModal(shipmentId, label);
        });
    });

    // Table search
    initTableSearch('table-search', '#shipments-table');

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
function initTableSearch(inputId, tableSelector) {
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
    const labels = JSON.parse(document.querySelector('meta[name="shipments-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="shipments-data"]').getAttribute('content'));

    if (data.tab === 'outgoing') {
        openCreateOutgoingModal(labels, data);
    } else {
        openCreateIncomingModal(labels, data);
    }
}

function openCreateOutgoingModal(labels, data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Customer options
    let customerOptions = `<option value="">${escapeHtml(labels.select_customer)}</option>`;
    data.customers.forEach(function(customer) {
        customerOptions += `<option value="${escapeHtml(customer.id)}">${escapeHtml(customer.name)}</option>`;
    });

    let customerWarning = '';
    if (data.customers.length === 0) {
        customerWarning = `<small class="form-warning">${escapeHtml(labels.no_customers)}</small>`;
    }

    // Unit options
    let unitOptions = `<option value="">${escapeHtml(labels.select_unit)}</option>`;
    data.units.forEach(function(unit) {
        unitOptions += `<option value="${unit.id}">${escapeHtml(unit.name)}</option>`;
    });

    const content = `
        <form method="POST" id="shipmentForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="create_outgoing">

            <div class="form-group">
                <label for="modal_to_org_id">${escapeHtml(labels.customer)} *</label>
                <select id="modal_to_org_id" name="to_org_id" required>
                    ${customerOptions}
                </select>
                ${customerWarning}
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="modal_sales_order_id">${escapeHtml(labels.sales_order_id)}</label>
                    <input type="text" id="modal_sales_order_id" name="sales_order_id" placeholder="SO-123456">
                    <small class="form-help">${escapeHtml(labels.sales_order_id_help)}</small>
                </div>

                <div class="form-group">
                    <label for="modal_purchase_order_id">${escapeHtml(labels.purchase_order_id)}</label>
                    <input type="text" id="modal_purchase_order_id" name="purchase_order_id" placeholder="PO-123456">
                    <small class="form-help">${escapeHtml(labels.purchase_order_id_help)}</small>
                </div>
            </div>

            <div class="form-group">
                <label for="modal_from_unit_id">${escapeHtml(labels.from_unit)}</label>
                <select id="modal_from_unit_id" name="from_unit_id">
                    ${unitOptions}
                </select>
            </div>

            <div class="form-group">
                <label for="modal_notes">${escapeHtml(labels.notes)}</label>
                <textarea id="modal_notes" name="notes" rows="2"></textarea>
            </div>
        </form>
    `;

    Modal.custom('info', labels.modal_create_outgoing, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('shipmentForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openCreateOutgoingModal(labels, data);
            }
        }
    });
}

function openCreateIncomingModal(labels, data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Supplier options
    let supplierOptions = `<option value="">${escapeHtml(labels.select_supplier)}</option>`;
    data.suppliers.forEach(function(supplier) {
        supplierOptions += `<option value="${escapeHtml(supplier.id)}">${escapeHtml(supplier.name)}</option>`;
    });

    let supplierWarning = '';
    if (data.suppliers.length === 0) {
        supplierWarning = `<small class="form-warning">${escapeHtml(labels.no_suppliers)}</small>`;
    }

    // Unit options
    let unitOptions = `<option value="">${escapeHtml(labels.select_unit)}</option>`;
    data.units.forEach(function(unit) {
        unitOptions += `<option value="${unit.id}">${escapeHtml(unit.name)}</option>`;
    });

    const content = `
        <form method="POST" id="shipmentForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="create_incoming">

            <div class="form-group">
                <label for="modal_from_org_id">${escapeHtml(labels.supplier)} *</label>
                <select id="modal_from_org_id" name="from_org_id" required>
                    ${supplierOptions}
                </select>
                ${supplierWarning}
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="modal_purchase_order_id">${escapeHtml(labels.purchase_order_id)}</label>
                    <input type="text" id="modal_purchase_order_id" name="purchase_order_id" placeholder="PO-123456">
                    <small class="form-help">${escapeHtml(labels.purchase_order_id_help)}</small>
                </div>

                <div class="form-group">
                    <label for="modal_sales_order_id">${escapeHtml(labels.sales_order_id)}</label>
                    <input type="text" id="modal_sales_order_id" name="sales_order_id" placeholder="SO-123456">
                    <small class="form-help">${escapeHtml(labels.sales_order_id_help)}</small>
                </div>
            </div>

            <div class="form-group">
                <label for="modal_to_unit_id">${escapeHtml(labels.to_unit)}</label>
                <select id="modal_to_unit_id" name="to_unit_id">
                    ${unitOptions}
                </select>
            </div>

            <div class="form-group">
                <label for="modal_notes">${escapeHtml(labels.notes)}</label>
                <textarea id="modal_notes" name="notes" rows="2"></textarea>
            </div>
        </form>
    `;

    Modal.custom('info', labels.modal_create_incoming, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('shipmentForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openCreateIncomingModal(labels, data);
            }
        }
    });
}

function openEditModal(shipment) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="shipments-labels"]').getAttribute('content'));

    const content = `
        <form method="POST" id="shipmentEditForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="shipment_id" value="${shipment.id}">

            <div class="form-group">
                <label>${escapeHtml(labels.qr_code)}</label>
                <input type="text" value="${escapeHtml(shipment.qr_code)}" disabled>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_sales_order_id">${escapeHtml(labels.sales_order_id)}</label>
                    <input type="text" id="edit_sales_order_id" name="sales_order_id" value="${escapeHtml(shipment.sales_order_id || '')}" placeholder="SO-123456">
                </div>

                <div class="form-group">
                    <label for="edit_purchase_order_id">${escapeHtml(labels.purchase_order_id)}</label>
                    <input type="text" id="edit_purchase_order_id" name="purchase_order_id" value="${escapeHtml(shipment.purchase_order_id || '')}" placeholder="PO-123456">
                </div>
            </div>

            <div class="form-group">
                <label for="edit_notes">${escapeHtml(labels.notes)}</label>
                <textarea id="edit_notes" name="notes" rows="2">${escapeHtml(shipment.notes || '')}</textarea>
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
            const form = document.getElementById('shipmentEditForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openEditModal(shipment);
            }
        }
    });
}

async function openDeleteModal(shipmentId, label) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="shipments-labels"]').getAttribute('content'));

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
            <input type="hidden" name="shipment_id" value="${shipmentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function openImportModal() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="shipments-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="shipments-data"]').getAttribute('content'));

    const partnerLabel = data.tab === 'outgoing' ? labels.customer : labels.supplier;

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
                <ul>
                    <li><strong>${escapeHtml(partnerLabel)}</strong> (${escapeHtml(labels.import_partner_hint)})</li>
                    <li>${escapeHtml(labels.sales_order_id)}</li>
                    <li>${escapeHtml(labels.purchase_order_id)}</li>
                    <li>${data.tab === 'outgoing' ? escapeHtml(labels.from_unit) : escapeHtml(labels.to_unit)}</li>
                    <li>${escapeHtml(labels.notes)}</li>
                </ul>
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
