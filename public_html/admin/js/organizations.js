/**
 * Organizations JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createOrgBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Edit buttons
    const editButtons = document.querySelectorAll('[data-org-edit]');
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const orgData = this.getAttribute('data-org-edit');
            const org = JSON.parse(orgData);
            openEditModal(org);
        });
    });

    // QR buttons
    const qrButtons = document.querySelectorAll('[data-org-qr]');
    qrButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const orgData = JSON.parse(this.getAttribute('data-org-qr'));
            showOrgQR(orgData);
        });
    });
});

function openCreateModal() {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="org-labels"]').getAttribute('content'));

    const content = `
        <form method="POST" id="orgForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="create">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.id} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_id" name="id" required pattern="[A-Z]{2}[0-9\\-]+" placeholder="SE556123-4567" maxlength="20" style="width: 100%;">
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.id_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.name} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_name" name="name" required maxlength="100" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.type}</strong></td>
                    <td style="padding: 0.5rem;">
                        <select id="modal_org_type" name="org_type" style="width: 100%;">
                            <option value="customer">${labels.type_customer}</option>
                            <option value="supplier">${labels.type_supplier}</option>
                            <option value="laundry">${labels.type_laundry}</option>
                            <option value="system">${labels.type_system}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.article_schema}</strong></td>
                    <td style="padding: 0.5rem;">
                        <div id="article-fields-list-create"></div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" id="newFieldCreate" placeholder="${labels.article_schema_placeholder}" style="flex: 1; padding: 0.5rem; border: 1px solid #0f3460; border-radius: 4px; background: #16213e; color: #eee;">
                            <button type="button" class="btn btn-small" id="addFieldBtnCreate">${labels.article_schema_add}</button>
                        </div>
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.article_schema_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.address}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_address" name="address" maxlength="255" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.postal_code}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_postal_code" name="postal_code" maxlength="20" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.city}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_city" name="city" maxlength="100" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.country}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_country" name="country" pattern="[A-Z]{2}" maxlength="2" value="SE" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.phone}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="tel" id="modal_phone" name="phone" maxlength="50" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.email}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="email" id="modal_email" name="email" maxlength="255" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.is_active}</strong></td>
                    <td style="padding: 0.5rem;">
                        <label>
                            <input type="checkbox" name="is_active" checked>
                            ${labels.is_active}
                        </label>
                    </td>
                </tr>
            </table>
        </form>
    `;

    Modal.custom('info', labels.modal_create, content, {
        html: true,
        hideClose: false,
        width: '1200px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    });

    // Add event listeners after modal opens
    setTimeout(() => {
        const addFieldBtn = document.getElementById('addFieldBtnCreate');
        const newFieldInput = document.getElementById('newFieldCreate');
        if (addFieldBtn && newFieldInput) {
            addFieldBtn.addEventListener('click', () => {
                const value = newFieldInput.value.trim();
                if (value) {
                    addArticleField('create', labels, value);
                    newFieldInput.value = '';
                    newFieldInput.focus();
                }
            });

            // Also allow Enter key to add field
            newFieldInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    addFieldBtn.click();
                }
            });
        }

        // Replace primary button to remove modal's close handler
        const oldPrimaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
        if (oldPrimaryBtn) {
            const newPrimaryBtn = oldPrimaryBtn.cloneNode(true);
            oldPrimaryBtn.parentNode.replaceChild(newPrimaryBtn, oldPrimaryBtn);
            newPrimaryBtn.addEventListener('click', () => {
                const form = document.getElementById('orgForm');
                if (form) {
                    form.submit();
                }
            });
        }
    }, 100);
}

function addArticleField(mode, labels, value = '') {
    const listId = mode === 'create' ? 'article-fields-list-create' : 'article-fields-list-edit';
    const list = document.getElementById(listId);
    const index = list.children.length;

    const fieldDiv = document.createElement('div');
    fieldDiv.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;';
    fieldDiv.draggable = true;
    fieldDiv.classList.add('article-field-item');
    fieldDiv.innerHTML = `
        <span class="drag-handle" style="cursor: grab; padding: 0.5rem; color: #888;">☰</span>
        <input type="hidden" name="article_fields[${index}][sort_order]" value="${index}">
        <input type="text" name="article_fields[${index}][label]" placeholder="${labels.article_schema_placeholder}" required value="${value}" style="flex: 1; padding: 0.5rem; border: 1px solid #0f3460; border-radius: 4px; background: #16213e; color: #eee;">
        <button type="button" class="btn btn-small btn-danger remove-field-btn">${labels.article_schema_remove}</button>
    `;

    list.appendChild(fieldDiv);

    // Add remove listener
    const removeBtn = fieldDiv.querySelector('.remove-field-btn');
    removeBtn.addEventListener('click', () => {
        fieldDiv.remove();
        reindexFields(listId);
    });

    // Add drag-and-drop listeners
    setupDragListeners(fieldDiv, listId);
}

function setupDragListeners(fieldDiv, listId) {
    const list = document.getElementById(listId);

    fieldDiv.addEventListener('dragstart', (e) => {
        fieldDiv.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    fieldDiv.addEventListener('dragend', () => {
        fieldDiv.classList.remove('dragging');
        reindexFields(listId);
    });

    fieldDiv.addEventListener('dragover', (e) => {
        e.preventDefault();
        const dragging = list.querySelector('.dragging');
        if (dragging && dragging !== fieldDiv) {
            const rect = fieldDiv.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            if (e.clientY < midY) {
                list.insertBefore(dragging, fieldDiv);
            } else {
                list.insertBefore(dragging, fieldDiv.nextSibling);
            }
        }
    });
}

function reindexFields(listId) {
    const list = document.getElementById(listId);
    Array.from(list.children).forEach((fieldDiv, index) => {
        const labelInput = fieldDiv.querySelector('input[type="text"]');
        const sortInput = fieldDiv.querySelector('input[type="hidden"]');
        if (labelInput) {
            labelInput.name = `article_fields[${index}][label]`;
        }
        if (sortInput) {
            sortInput.name = `article_fields[${index}][sort_order]`;
            sortInput.value = index;
        }
    });
}

function openEditModal(org) {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="org-labels"]').getAttribute('content'));

    const content = `
        <form method="POST" id="orgForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="${org.id}">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.id}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${org.id}" disabled style="width: 100%; background: #2a2a2a; color: #888;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.name} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_name" name="name" required maxlength="100" value="${org.name || ''}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.type}</strong></td>
                    <td style="padding: 0.5rem;">
                        <select id="modal_org_type" name="org_type" style="width: 100%;">
                            <option value="customer" ${org.org_type === 'customer' ? 'selected' : ''}>${labels.type_customer}</option>
                            <option value="supplier" ${org.org_type === 'supplier' ? 'selected' : ''}>${labels.type_supplier}</option>
                            <option value="laundry" ${org.org_type === 'laundry' ? 'selected' : ''}>${labels.type_laundry}</option>
                            <option value="system" ${org.org_type === 'system' ? 'selected' : ''}>${labels.type_system}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.article_schema}</strong></td>
                    <td style="padding: 0.5rem;">
                        <div id="article-fields-list-edit"></div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" id="newFieldEdit" placeholder="${labels.article_schema_placeholder}" style="flex: 1; padding: 0.5rem; border: 1px solid #0f3460; border-radius: 4px; background: #16213e; color: #eee;">
                            <button type="button" class="btn btn-small" id="addFieldBtnEdit">${labels.article_schema_add}</button>
                        </div>
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.article_schema_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.address}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_address" name="address" maxlength="255" value="${org.address || ''}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.postal_code}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_postal_code" name="postal_code" maxlength="20" value="${org.postal_code || ''}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.city}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_city" name="city" maxlength="100" value="${org.city || ''}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.country}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_country" name="country" pattern="[A-Z]{2}" maxlength="2" value="${org.country || 'SE'}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.phone}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="tel" id="modal_phone" name="phone" maxlength="50" value="${org.phone || ''}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.email}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="email" id="modal_email" name="email" maxlength="255" value="${org.email || ''}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.is_active}</strong></td>
                    <td style="padding: 0.5rem;">
                        <label>
                            <input type="checkbox" name="is_active" ${org.is_active ? 'checked' : ''}>
                            ${labels.is_active}
                        </label>
                    </td>
                </tr>
            </table>
        </form>
    `;

    Modal.custom('info', labels.modal_edit, content, {
        html: true,
        hideClose: false,
        width: '1200px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: labels.update, class: 'primary', value: 'submit' }
        ]
    });

    // Add event listeners after modal opens
    setTimeout(() => {
        const addFieldBtn = document.getElementById('addFieldBtnEdit');
        const newFieldInput = document.getElementById('newFieldEdit');
        if (addFieldBtn && newFieldInput) {
            addFieldBtn.addEventListener('click', () => {
                const value = newFieldInput.value.trim();
                if (value) {
                    addArticleField('edit', labels, value);
                    newFieldInput.value = '';
                    newFieldInput.focus();
                }
            });

            // Also allow Enter key to add field
            newFieldInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    addFieldBtn.click();
                }
            });
        }

        // Replace primary button to remove modal's close handler
        const oldPrimaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
        if (oldPrimaryBtn) {
            const newPrimaryBtn = oldPrimaryBtn.cloneNode(true);
            oldPrimaryBtn.parentNode.replaceChild(newPrimaryBtn, oldPrimaryBtn);
            newPrimaryBtn.addEventListener('click', () => {
                const form = document.getElementById('orgForm');
                if (form) {
                    form.submit();
                }
            });
        }

        // Populate existing fields
        let articleSchema = [];
        try {
            if (org.article_schema) {
                articleSchema = typeof org.article_schema === 'string'
                    ? JSON.parse(org.article_schema)
                    : org.article_schema;
            }
        } catch (e) {
            console.error('Failed to parse article_schema:', e);
        }

        if (articleSchema && articleSchema.length > 0) {
            articleSchema.forEach((field) => {
                if (field.label) {
                    addArticleField('edit', labels, field.label);
                }
            });
        }
    }, 100);
}

function showOrgQR(org) {
    QR.show({
        data: {
            type: 'rfid_register',
            org_id: org.id,
            org_name: org.name
        },
        title: org.name,
        subtitle: 'Org: ' + org.id + '\nTyp: Registrera RFID',
        filename: 'QR_RFID_Register_' + org.id
    });
}
