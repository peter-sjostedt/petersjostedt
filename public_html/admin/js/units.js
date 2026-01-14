/**
 * Units JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createUnitBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Edit buttons
    const editButtons = document.querySelectorAll('[data-unit-edit]');
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const unitData = this.getAttribute('data-unit-edit');
            const unit = JSON.parse(unitData);
            openEditModal(unit);
        });
    });

    // Delete buttons
    const deleteButtons = document.querySelectorAll('[data-unit-delete]');
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const unitData = this.getAttribute('data-unit-delete');
            const unit = JSON.parse(unitData);
            openDeleteModal(unit);
        });
    });
});

function openCreateModal() {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="unit-labels"]').getAttribute('content'));
    const apiKey = generateApiKey();

    const content = `
        <form method="POST" id="unitForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="create">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.name} *</strong></td>
                    <td style="padding: 0.75rem 0;">
                        <input type="text" id="modal_name" name="name" required maxlength="100" style="width: 100%;">
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.name_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.password} *</strong></td>
                    <td style="padding: 0.75rem 0;">
                        <input type="password" id="modal_password" name="password" required minlength="8" style="width: 100%;">
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.password_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.api_key}</strong></td>
                    <td style="padding: 0.75rem 0;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" id="modal_api_key" name="api_key" value="${apiKey}" style="flex: 1; font-family: monospace; background: #16213e; color: #eee;">
                            <button type="button" class="btn btn-icon" id="copyApiKeyBtn" title="${labels.copy_api_key}">📋</button>
                            <button type="button" class="btn btn-icon" id="generateApiKeyBtn" title="${labels.generate_new_api_key}">🔄</button>
                        </div>
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.api_key_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.is_active}</strong></td>
                    <td style="padding: 0.75rem 0;">
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
        width: '800px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    });

    // Add event listeners after modal opens
    setTimeout(() => {
        const generateBtn = document.getElementById('generateApiKeyBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => {
                document.getElementById('modal_api_key').value = generateApiKey();
            });
        }

        const copyBtn = document.getElementById('copyApiKeyBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const apiKeyInput = document.getElementById('modal_api_key');
                apiKeyInput.select();
                navigator.clipboard.writeText(apiKeyInput.value).then(() => {
                    copyBtn.textContent = '✓';
                    setTimeout(() => {
                        copyBtn.textContent = '📋';
                    }, 2000);
                });
            });
        }

        // Submit form when primary button is clicked
        const primaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
        if (primaryBtn) {
            primaryBtn.addEventListener('click', () => {
                const form = document.getElementById('unitForm');
                if (form && form.checkValidity()) {
                    form.submit();
                } else {
                    form.reportValidity();
                }
            });
        }
    }, 100);
}

function openEditModal(unit) {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="unit-labels"]').getAttribute('content'));

    const content = `
        <form method="POST" id="unitForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="${unit.id}">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.name} *</strong></td>
                    <td style="padding: 0.75rem 0;">
                        <input type="text" id="modal_name" name="name" required maxlength="100" value="${unit.name || ''}" style="width: 100%;">
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.name_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.password}</strong></td>
                    <td style="padding: 0.75rem 0;">
                        <input type="password" id="modal_password" name="password" minlength="8" style="width: 100%;">
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.password_leave_blank}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.api_key}</strong></td>
                    <td style="padding: 0.75rem 0;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" id="modal_api_key" name="api_key" value="${unit.api_key || ''}" style="flex: 1; font-family: monospace; background: #16213e; color: #eee;">
                            <button type="button" class="btn btn-icon" id="copyApiKeyBtn" title="${labels.copy_api_key}">📋</button>
                            <button type="button" class="btn btn-icon" id="generateApiKeyBtn" title="${labels.generate_new_api_key}">🔄</button>
                        </div>
                        <small style="color: #aaa; display: block; margin-top: 0.25rem;">${labels.api_key_help}</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.75rem 0; width: 20%; vertical-align: top;"><strong>${labels.is_active}</strong></td>
                    <td style="padding: 0.75rem 0;">
                        <label>
                            <input type="checkbox" name="is_active" ${unit.is_active ? 'checked' : ''}>
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
        width: '800px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: labels.update, class: 'primary', value: 'submit' }
        ]
    });

    // Add event listeners after modal opens
    setTimeout(() => {
        const generateBtn = document.getElementById('generateApiKeyBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => {
                document.getElementById('modal_api_key').value = generateApiKey();
            });
        }

        const copyBtn = document.getElementById('copyApiKeyBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const apiKeyInput = document.getElementById('modal_api_key');
                apiKeyInput.select();
                navigator.clipboard.writeText(apiKeyInput.value).then(() => {
                    copyBtn.textContent = '✓';
                    setTimeout(() => {
                        copyBtn.textContent = '📋';
                    }, 2000);
                });
            });
        }

        // Submit form when primary button is clicked
        const primaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
        if (primaryBtn) {
            primaryBtn.addEventListener('click', () => {
                const form = document.getElementById('unitForm');
                if (form && form.checkValidity()) {
                    form.submit();
                } else {
                    form.reportValidity();
                }
            });
        }
    }, 100);
}

function openDeleteModal(unit) {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="unit-labels"]').getAttribute('content'));

    const urlParams = new URLSearchParams(window.location.search);
    const orgId = urlParams.get('org_id');

    const content = `
        <form method="POST" id="deleteForm" action="units.php?org_id=${encodeURIComponent(orgId)}">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${unit.id}">

            <p style="margin: 1rem 0;">Är du säker på att du vill radera enheten <strong>${unit.name}</strong>?</p>
            <p style="color: #e94560;">Denna åtgärd kan inte ångras.</p>
        </form>
    `;

    Modal.custom('warning', 'Radera enhet', content, {
        html: true,
        hideClose: false,
        width: '500px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: 'Radera', class: 'danger', value: 'delete' }
        ]
    });

    // Submit form when delete button is clicked
    setTimeout(() => {
        const deleteBtn = document.querySelector('.modal-footer .modal-btn.danger');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                const form = document.getElementById('deleteForm');
                if (form) {
                    form.submit();
                }
            });
        }
    }, 100);
}

function generateApiKey() {
    const array = new Uint8Array(32);
    crypto.getRandomValues(array);
    return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}
