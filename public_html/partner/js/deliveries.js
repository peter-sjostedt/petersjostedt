/**
 * Partner Portal - Deliveries JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createDeliveryBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateDeliveryModal);
    }

    // Edit buttons
    document.querySelectorAll('[data-delivery-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const deliveryData = this.getAttribute('data-delivery-edit');
            const delivery = JSON.parse(deliveryData);
            openEditDeliveryModal(delivery);
        });
    });

    // Delete buttons
    document.querySelectorAll('[data-delivery-delete]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-delivery-delete');
            const label = this.getAttribute('data-label');
            openDeleteDeliveryModal(eventId, label);
        });
    });

    // Table search
    initDeliveryTableSearch('table-search', '#deliveries-table');

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
function initDeliveryTableSearch(inputId, tableSelector) {
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

function openCreateDeliveryModal() {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="delivery-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="delivery-data"]').getAttribute('content'));

    let supplierOptions = `<option value="">${escapeHtml(labels.select_supplier)}</option>`;
    data.suppliers.forEach(function(supplier) {
        supplierOptions += `<option value="${escapeHtml(supplier.id)}">${escapeHtml(supplier.name)} (${escapeHtml(supplier.id)})</option>`;
    });

    let supplierWarning = '';
    if (data.suppliers.length === 0) {
        supplierWarning = `<small style="color: #856404; display: block; margin-top: 0.25rem;">${escapeHtml(labels.no_suppliers)}</small>`;
    }

    const content = `
        <form method="POST" id="deliveryForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label for="modal_supplier_id">${escapeHtml(labels.supplier)} *</label>
                <select id="modal_supplier_id" name="supplier_id" required>
                    ${supplierOptions}
                </select>
                ${supplierWarning}
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="modal_purchase_order_id">${escapeHtml(labels.purchase_order_id)}</label>
                    <input type="text" id="modal_purchase_order_id" name="purchase_order_id" placeholder="PO-2026-0001">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">${escapeHtml(labels.purchase_order_id_help)}</small>
                </div>

                <div class="form-group">
                    <label for="modal_supplier_order_id">${escapeHtml(labels.supplier_order_id)}</label>
                    <input type="text" id="modal_supplier_order_id" name="supplier_order_id" placeholder="SO-123456">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">${escapeHtml(labels.supplier_order_id_help)}</small>
                </div>
            </div>

            <div class="form-group">
                <label for="modal_delivery_id">${escapeHtml(labels.delivery_id)} *</label>
                <input type="text" id="modal_delivery_id" name="delivery_id" value="${escapeHtml(data.suggestedId)}" required>
                <small style="color: #666; display: block; margin-top: 0.25rem;">${escapeHtml(labels.delivery_id_help)}</small>
            </div>
        </form>
    `;

    Modal.custom('info', labels.modal_create, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('deliveryForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openCreateDeliveryModal();
            }
        }
    });
}

function openEditDeliveryModal(delivery) {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="delivery-labels"]').getAttribute('content'));
    const data = JSON.parse(document.querySelector('meta[name="delivery-data"]').getAttribute('content'));

    let supplierOptions = `<option value="">${escapeHtml(labels.select_supplier)}</option>`;
    data.suppliers.forEach(function(supplier) {
        const selected = (supplier.id === delivery.supplier) ? ' selected' : '';
        supplierOptions += `<option value="${escapeHtml(supplier.id)}"${selected}>${escapeHtml(supplier.name)} (${escapeHtml(supplier.id)})</option>`;
    });

    const content = `
        <form method="POST" id="deliveryEditForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="event_id" value="${delivery.id}">

            <div class="form-group">
                <label for="edit_supplier_id">${escapeHtml(labels.supplier)} *</label>
                <select id="edit_supplier_id" name="supplier_id" required>
                    ${supplierOptions}
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_purchase_order_id">${escapeHtml(labels.purchase_order_id)}</label>
                    <input type="text" id="edit_purchase_order_id" name="purchase_order_id" value="${escapeHtml(delivery.purchaseOrderId || '')}" placeholder="PO-2026-0001">
                </div>

                <div class="form-group">
                    <label for="edit_supplier_order_id">${escapeHtml(labels.supplier_order_id)}</label>
                    <input type="text" id="edit_supplier_order_id" name="supplier_order_id" value="${escapeHtml(delivery.supplierOrderId || '')}" placeholder="SO-123456">
                </div>
            </div>

            <div class="form-group">
                <label for="edit_delivery_id">${escapeHtml(labels.delivery_id)} *</label>
                <input type="text" id="edit_delivery_id" name="delivery_id" value="${escapeHtml(delivery.deliveryId)}" required>
            </div>
        </form>
    `;

    Modal.custom('info', labels.modal_edit, content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: labels.update || 'Uppdatera', class: 'primary', value: 'submit' }
        ]
    }).then(function(result) {
        if (result === 'submit') {
            const form = document.getElementById('deliveryEditForm');
            if (form && form.checkValidity()) {
                form.submit();
            } else if (form) {
                form.reportValidity();
                openEditDeliveryModal(delivery);
            }
        }
    });
}

async function openDeleteDeliveryModal(eventId, label) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="delivery-labels"]').getAttribute('content'));

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
