/**
 * Users - Modal-baserad användarhantering med AJAX
 */

document.addEventListener('DOMContentLoaded', function() {
    // Skapa ny användare
    const createBtn = document.getElementById('createUserBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Redigera användare
    document.querySelectorAll('[data-user-edit]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const userData = JSON.parse(this.getAttribute('data-user-edit'));
            openEditModal(userData);
        });
    });

    // Radera användare
    document.querySelectorAll('[data-user-delete]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const userData = JSON.parse(this.getAttribute('data-user-delete'));
            openDeleteModal(userData);
        });
    });
});

function openCreateModal() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const labels = JSON.parse(document.querySelector('meta[name="user-labels"]').content);
    const organizations = JSON.parse(document.querySelector('meta[name="organizations-data"]').content);
    const filterOrgMeta = document.querySelector('meta[name="filter-org"]');
    const filterOrg = filterOrgMeta ? filterOrgMeta.content : null;

    let orgOptions = `<option value="">${labels.select}...</option>`;
    organizations.forEach(function(org) {
        const selected = filterOrg === org.id ? 'selected' : '';
        orgOptions += `<option value="${escapeHtml(org.id)}" ${selected}>${escapeHtml(org.name)} (${escapeHtml(org.id)})</option>`;
    });

    const content = `
        <div id="modal_message" style="display: none; padding: 0.75rem; margin-bottom: 1rem; border-radius: 4px;"></div>
        <form id="userForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="create">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.role} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <select id="modal_role" name="role" style="width: 100%;">
                            <option value="user">${labels.role_user}</option>
                            <option value="org_admin">${labels.role_org_admin}</option>
                            <option value="admin">${labels.role_admin}</option>
                        </select>
                    </td>
                </tr>
                <tr id="org_field_row">
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.organization} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <select id="modal_organization" name="organization_id" style="width: 100%;">
                            ${orgOptions}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.name} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_name" name="name" required maxlength="100" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.email} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="email" id="modal_email" name="email" required maxlength="255" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.password} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="password" id="modal_password" name="password" required minlength="8" style="width: 100%;">
                    </td>
                </tr>
            </table>
        </form>
    `;

    Modal.custom('info', labels.modal_create, content, {
        html: true,
        hideClose: false,
        width: '600px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.create, class: 'primary', value: 'submit' }
        ]
    });

    setTimeout(() => {
        setupRoleToggle();
        setupAjaxFormSubmit();
        document.getElementById('modal_name').focus();
    }, 100);
}

function openEditModal(user) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const labels = JSON.parse(document.querySelector('meta[name="user-labels"]').content);
    const organizations = JSON.parse(document.querySelector('meta[name="organizations-data"]').content);

    let orgOptions = `<option value="">${labels.select}...</option>`;
    organizations.forEach(function(org) {
        const selected = user.organization_id === org.id ? 'selected' : '';
        orgOptions += `<option value="${escapeHtml(org.id)}" ${selected}>${escapeHtml(org.name)} (${escapeHtml(org.id)})</option>`;
    });

    const showOrgField = user.role !== 'admin';

    const content = `
        <div id="modal_message" style="display: none; padding: 0.75rem; margin-bottom: 1rem; border-radius: 4px;"></div>
        <form id="userForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="${user.id}">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>ID</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${user.id}" disabled style="width: 100%; background: #2a2a2a; color: #888;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.role} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <select id="modal_role" name="role" style="width: 100%;">
                            <option value="user" ${user.role === 'user' ? 'selected' : ''}>${labels.role_user}</option>
                            <option value="org_admin" ${user.role === 'org_admin' ? 'selected' : ''}>${labels.role_org_admin}</option>
                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>${labels.role_admin}</option>
                        </select>
                    </td>
                </tr>
                <tr id="org_field_row" style="display: ${showOrgField ? 'table-row' : 'none'};">
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.organization} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <select id="modal_organization" name="organization_id" style="width: 100%;">
                            ${orgOptions}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.name} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_name" name="name" required maxlength="100" value="${escapeHtml(user.name)}" style="width: 100%;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.email} *</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="email" id="modal_email" name="email" required maxlength="255" value="${escapeHtml(user.email)}" style="width: 100%;">
                    </td>
                </tr>
            </table>
        </form>

        <hr style="margin: 1.5rem 0; border-color: #333;">

        <form id="passwordForm">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="update_password">
            <input type="hidden" name="id" value="${user.id}">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.new_password}</strong></td>
                    <td style="padding: 0.5rem;">
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="password" id="modal_new_password" name="password" minlength="8" style="flex: 1;">
                            <button type="submit" class="btn btn-secondary">${labels.change_password}</button>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    `;

    Modal.custom('info', labels.modal_edit, content, {
        html: true,
        hideClose: false,
        width: '600px',
        buttons: [
            { text: labels.cancel, class: 'cancel', value: false },
            { text: labels.update, class: 'primary', value: 'submit' }
        ]
    });

    setTimeout(() => {
        setupRoleToggle();
        setupAjaxFormSubmit();
        setupAjaxPasswordForm();
        document.getElementById('modal_name').focus();
    }, 100);
}

function setupRoleToggle() {
    const roleSelect = document.getElementById('modal_role');
    const orgFieldRow = document.getElementById('org_field_row');

    if (roleSelect && orgFieldRow) {
        const updateOrgVisibility = () => {
            orgFieldRow.style.display = roleSelect.value === 'admin' ? 'none' : 'table-row';
        };
        roleSelect.addEventListener('change', updateOrgVisibility);
        updateOrgVisibility();
    }
}

function showModalMessage(message, isError) {
    const msgEl = document.getElementById('modal_message');
    if (msgEl) {
        msgEl.textContent = message;
        msgEl.style.display = 'block';
        msgEl.style.background = isError ? '#dc3545' : '#28a745';
        msgEl.style.color = '#fff';
    }
}

function setupAjaxFormSubmit() {
    const oldPrimaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
    if (oldPrimaryBtn) {
        const newPrimaryBtn = oldPrimaryBtn.cloneNode(true);
        oldPrimaryBtn.parentNode.replaceChild(newPrimaryBtn, oldPrimaryBtn);
        newPrimaryBtn.addEventListener('click', async () => {
            const form = document.getElementById('userForm');
            if (!form) return;

            // Validera att user/org_admin har organisation
            const role = document.getElementById('modal_role').value;
            const orgId = document.getElementById('modal_organization')?.value;
            if (role !== 'admin' && !orgId) {
                const labels = JSON.parse(document.querySelector('meta[name="user-labels"]').content);
                showModalMessage(labels.requires_org, true);
                return;
            }

            // Skicka via AJAX
            const formData = new FormData(form);

            try {
                newPrimaryBtn.disabled = true;
                newPrimaryBtn.textContent = '...';

                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    Modal.close();
                    location.reload();
                } else {
                    showModalMessage(result.message, true);
                    newPrimaryBtn.disabled = false;
                    const labels = JSON.parse(document.querySelector('meta[name="user-labels"]').content);
                    newPrimaryBtn.textContent = form.querySelector('input[name="action"]').value === 'create'
                        ? labels.create
                        : labels.update;
                }
            } catch (error) {
                showModalMessage('Ett fel uppstod. Försök igen.', true);
                newPrimaryBtn.disabled = false;
            }
        });
    }
}

function setupAjaxPasswordForm() {
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const password = document.getElementById('modal_new_password').value;
            if (password.length === 0) {
                return;
            }
            if (password.length < 8) {
                showModalMessage('Lösenordet måste vara minst 8 tecken.', true);
                return;
            }

            const formData = new FormData(passwordForm);
            const submitBtn = passwordForm.querySelector('button[type="submit"]');

            try {
                submitBtn.disabled = true;
                submitBtn.textContent = '...';

                const response = await fetch('users.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                showModalMessage(result.message, !result.success);

                if (result.success) {
                    document.getElementById('modal_new_password').value = '';
                }

                submitBtn.disabled = false;
                const labels = JSON.parse(document.querySelector('meta[name="user-labels"]').content);
                submitBtn.textContent = labels.change_password;
            } catch (error) {
                showModalMessage('Ett fel uppstod. Försök igen.', true);
                submitBtn.disabled = false;
            }
        });
    }
}

async function openDeleteModal(user) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const labels = JSON.parse(document.querySelector('meta[name="user-labels"]').content);

    const message = labels.modal_delete_message.replace('{name}', escapeHtml(user.name));

    const confirmed = await Modal.confirm(labels.modal_delete, message, {
        html: true,
        confirmText: labels.delete,
        cancelText: labels.cancel,
        danger: true
    });

    if (confirmed) {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('action', 'delete');
        formData.append('id', user.id);

        try {
            const response = await fetch('users.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                Modal.error('Fel', result.message);
            }
        } catch (error) {
            Modal.error('Fel', 'Ett fel uppstod. Försök igen.');
        }
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
