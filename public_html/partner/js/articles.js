/**
 * Partner Portal - Articles JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create button
    const createBtn = document.getElementById('createArticleBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateModal);
    }

    // Import button
    const importBtn = document.getElementById('importArticleBtn');
    if (importBtn) {
        importBtn.addEventListener('click', openImportModal);
    }

    // Edit buttons
    const editButtons = document.querySelectorAll('[data-article-edit]');
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const articleData = this.getAttribute('data-article-edit');
            const article = JSON.parse(articleData);
            openEditModal(article);
        });
    });

    // Delete forms confirmation
    const deleteForms = document.querySelectorAll('form[data-confirm]');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const message = form.getAttribute('data-confirm');

            if (typeof Modal !== 'undefined') {
                const confirmed = await Modal.confirm('Bekräfta', message, { danger: true });
                if (confirmed) {
                    form.removeAttribute('data-confirm');
                    form.submit();
                }
            } else {
                if (confirm(message)) {
                    form.submit();
                }
            }
        });
    });

    // Table search
    initTableSearch('table-search', '#articles-table');
});

/**
 * Initialize table search functionality
 */
function initTableSearch(inputId, tableSelector) {
    const input = document.getElementById(inputId);
    const table = document.querySelector(tableSelector);
    const clearBtn = document.querySelector('.search-clear');

    if (!input || !table) return;

    // Dölj X-knappen initialt
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

        // Visa/dölj X-knappen
        if (clearBtn) {
            clearBtn.style.display = this.value ? 'block' : 'none';
        }
    });

    // Rensa-knapp
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

// Skapa en säker fältnyckel från label
function fieldKey(label) {
    return label.toLowerCase()
        .replace(/å/g, 'a').replace(/ä/g, 'a').replace(/ö/g, 'o')
        .replace(/[^a-z0-9]/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_|_$/g, '');
}

function openCreateModal() {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="article-labels"]').getAttribute('content'));
    const articleFields = JSON.parse(document.querySelector('meta[name="article-fields"]').getAttribute('content') || '[]');

    // Bygg fältens HTML
    let fieldsHtml = '';
    if (articleFields.length === 0) {
        fieldsHtml = `
            <tr>
                <td colspan="2" style="padding: 0.5rem;">
                    <div style="background: #fff3cd; color: #856404; padding: 0.75rem; border-radius: 4px;">
                        ${labels.no_fields} <a href="settings.php">${labels.no_fields_link}</a>
                    </div>
                </td>
            </tr>
        `;
    } else {
        articleFields.forEach((field) => {
            const key = fieldKey(field.label);
            fieldsHtml += `
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${escapeHtml(field.label)}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" name="${key}" style="width: 100%;">
                    </td>
                </tr>
            `;
        });
    }

    const content = `
        <form method="POST" id="articleForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="create">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.sku}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_sku" name="sku" maxlength="100" style="width: 100%;" placeholder="ART-0001">
                        <small style="color: #666; display: block; margin-top: 0.25rem;">${labels.sku_auto}</small>
                    </td>
                </tr>
                ${fieldsHtml}
            </table>
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
    });

    setTimeout(() => {
        const oldPrimaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
        if (oldPrimaryBtn) {
            const newPrimaryBtn = oldPrimaryBtn.cloneNode(true);
            oldPrimaryBtn.parentNode.replaceChild(newPrimaryBtn, oldPrimaryBtn);
            newPrimaryBtn.addEventListener('click', () => {
                const form = document.getElementById('articleForm');
                if (form) {
                    form.submit();
                }
            });
        }
    }, 100);
}

function openEditModal(article) {
    const csrfField = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="article-labels"]').getAttribute('content'));
    const articleFields = JSON.parse(document.querySelector('meta[name="article-fields"]').getAttribute('content') || '[]');

    // Parsa artikelns data-fält
    let articleData = {};
    try {
        articleData = JSON.parse(article.data || '{}');
    } catch (e) {
        articleData = {};
    }

    // Bygg fältens HTML med befintliga värden
    let fieldsHtml = '';
    articleFields.forEach((field) => {
        const key = fieldKey(field.label);
        const fieldValue = articleData[key] || '';
        fieldsHtml += `
            <tr>
                <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${escapeHtml(field.label)}</strong></td>
                <td style="padding: 0.5rem;">
                    <input type="text" name="${key}" value="${escapeHtml(fieldValue)}" style="width: 100%;">
                </td>
            </tr>
        `;
    });

    const content = `
        <form method="POST" id="articleForm">
            <input type="hidden" name="csrf_token" value="${csrfField}">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="${article.id}">

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.sku}</strong></td>
                    <td style="padding: 0.5rem;">
                        <input type="text" id="modal_sku" name="sku" readonly value="${escapeHtml(article.sku)}" style="width: 100%; background: #f5f5f5;">
                    </td>
                </tr>
                ${fieldsHtml}
                <tr>
                    <td style="padding: 0.5rem; width: 30%; vertical-align: top;"><strong>${labels.is_active}</strong></td>
                    <td style="padding: 0.5rem;">
                        <label>
                            <input type="checkbox" name="is_active" ${article.is_active ? 'checked' : ''}>
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
        width: '500px',
        buttons: [
            { text: labels.cancel || 'Avbryt', class: 'cancel', value: false },
            { text: labels.update, class: 'primary', value: 'submit' }
        ]
    });

    setTimeout(() => {
        const oldPrimaryBtn = document.querySelector('.modal-footer .modal-btn.primary');
        if (oldPrimaryBtn) {
            const newPrimaryBtn = oldPrimaryBtn.cloneNode(true);
            oldPrimaryBtn.parentNode.replaceChild(newPrimaryBtn, oldPrimaryBtn);
            newPrimaryBtn.addEventListener('click', () => {
                const form = document.getElementById('articleForm');
                if (form && form.checkValidity()) {
                    form.submit();
                } else if (form) {
                    form.reportValidity();
                }
            });
        }
    }, 100);
}

function openImportModal() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const labels = JSON.parse(document.querySelector('meta[name="article-labels"]').getAttribute('content'));
    const articleFields = JSON.parse(document.querySelector('meta[name="article-fields"]').getAttribute('content') || '[]');

    // Bygg lista över förväntade kolumner
    let columnsHtml = '<ul>';
    columnsHtml += `<li><strong>SKU</strong> (${escapeHtml(labels.import_sku_hint)})</li>`;
    articleFields.forEach(function(field) {
        columnsHtml += `<li>${escapeHtml(field.label)}</li>`;
    });
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

