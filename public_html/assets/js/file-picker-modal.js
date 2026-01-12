/**
 * File Picker Modal - Återanvändbar filväljare
 *
 * Användning:
 *
 * 1. Inkludera CSS och JS:
 *    <link rel="stylesheet" href="assets/css/file-picker-modal.css">
 *    <script src="assets/js/file-picker-modal.js"></script>
 *
 * 2. Skapa en knapp/input som öppnar modalen:
 *    <button onclick="openFilePicker({ onSelect: handleFileSelect })">Välj fil</button>
 *
 * 3. Hantera vald fil:
 *    function handleFileSelect(file) {
 *        console.log('Vald fil:', file);
 *        // file innehåller: { id, original_name, mime_type, file_size, file_path, url }
 *    }
 */

class FilePicker {
    constructor() {
        this.modal = null;
        this.callback = null;
        this.options = {
            type: 'all', // 'all', 'image', 'document'
            multiple: false,
            folder: null,
            mode: 'file' // 'file' or 'folder'
        };
        this.selectedFiles = [];
        this.selectedFolders = [];
        this.currentFolder = '';
        this.init();
    }

    init() {
        // Skapa modal HTML om den inte finns
        if (!document.getElementById('file-picker-modal')) {
            this.createModalHTML();
        }
        this.modal = document.getElementById('file-picker-modal');
        this.attachEventListeners();
    }

    createModalHTML() {
        const modalHTML = `
            <div id="file-picker-modal" class="fp-modal">
                <div class="fp-modal-overlay"></div>
                <div class="fp-modal-container">
                    <div class="fp-modal-header">
                        <h2>Välj fil</h2>
                        <button class="fp-close-btn" aria-label="Stäng">&times;</button>
                    </div>

                    <div class="fp-modal-toolbar">
                        <div class="fp-breadcrumb">
                            <a href="#" data-folder="">🏠 Rot</a>
                        </div>
                        <div class="fp-view-toggle">
                            <button class="fp-view-btn active" data-view="grid" title="Rutnät">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <rect x="2" y="2" width="7" height="7" rx="1"/>
                                    <rect x="11" y="2" width="7" height="7" rx="1"/>
                                    <rect x="2" y="11" width="7" height="7" rx="1"/>
                                    <rect x="11" y="11" width="7" height="7" rx="1"/>
                                </svg>
                            </button>
                            <button class="fp-view-btn" data-view="list" title="Lista">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <rect x="2" y="3" width="16" height="2" rx="1"/>
                                    <rect x="2" y="9" width="16" height="2" rx="1"/>
                                    <rect x="2" y="15" width="16" height="2" rx="1"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="fp-modal-body">
                        <div class="fp-loading" style="display: none;">
                            <div class="fp-spinner"></div>
                            <p>Laddar filer...</p>
                        </div>
                        <div class="fp-content"></div>
                    </div>

                    <div class="fp-modal-footer">
                        <div class="fp-selected-info">
                            <span class="fp-selected-count">Ingen fil vald</span>
                        </div>
                        <div class="fp-actions">
                            <button class="fp-btn fp-btn-cancel">Avbryt</button>
                            <button class="fp-btn fp-btn-select" disabled>Välj fil</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    attachEventListeners() {
        // Stäng-knappar
        const closeBtn = this.modal.querySelector('.fp-close-btn');
        const cancelBtn = this.modal.querySelector('.fp-btn-cancel');
        const overlay = this.modal.querySelector('.fp-modal-overlay');

        closeBtn.addEventListener('click', () => this.close());
        cancelBtn.addEventListener('click', () => this.close());
        overlay.addEventListener('click', () => this.close());

        // Välj-knapp
        const selectBtn = this.modal.querySelector('.fp-btn-select');
        selectBtn.addEventListener('click', () => this.selectFiles());

        // ESC-tangent
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.close();
            }
        });

        // View toggle
        this.modal.querySelectorAll('.fp-view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.currentTarget.dataset.view;
                this.setView(view);
            });
        });
    }

    async open(options = {}) {
        this.options = { ...this.options, ...options };
        this.callback = options.onSelect || null;
        this.selectedFiles = [];
        this.selectedFolders = [];
        this.currentFolder = this.options.folder || '';

        // Uppdatera rubrik baserat på mode
        const header = this.modal.querySelector('.fp-modal-header h2');
        header.textContent = this.options.mode === 'folder' ? 'Välj mapp' : 'Välj fil';

        // Uppdatera knapptext
        const selectBtn = this.modal.querySelector('.fp-btn-select');
        selectBtn.textContent = this.options.mode === 'folder' ? 'Välj mapp' : 'Välj fil';

        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        await this.loadFiles();
    }

    close() {
        this.modal.classList.remove('active');
        document.body.style.overflow = '';
        this.selectedFiles = [];
        this.selectedFolders = [];
        this.updateSelectButton();
    }

    async loadFiles() {
        const content = this.modal.querySelector('.fp-content');
        const loading = this.modal.querySelector('.fp-loading');

        loading.style.display = 'flex';
        content.innerHTML = '';

        try {
            const response = await fetch(`file-picker-api.php?folder=${encodeURIComponent(this.currentFolder)}&type=${this.options.type}`);
            const data = await response.json();

            if (data.success) {
                this.renderFiles(data.files, data.folders);
                this.updateBreadcrumb();
            } else {
                content.innerHTML = `<div class="fp-error">Fel: ${data.error}</div>`;
            }
        } catch (error) {
            content.innerHTML = `<div class="fp-error">Kunde inte ladda filer: ${error.message}</div>`;
        } finally {
            loading.style.display = 'none';
        }
    }

    renderFiles(files, folders) {
        const content = this.modal.querySelector('.fp-content');
        const view = this.modal.querySelector('.fp-view-btn.active').dataset.view;

        let html = '';

        // Visa mappar (endast i root och grid-vy)
        if (folders && folders.length > 0 && !this.currentFolder && view === 'grid') {
            html += '<div class="fp-folders">';
            folders.forEach(folder => {
                const isSelected = this.selectedFolders.includes(folder);
                html += `
                    <div class="fp-folder-item ${isSelected ? 'selected' : ''}" data-folder="${folder}" style="border-radius: 16px;">
                        <div class="fp-folder-icon">📁</div>
                        <div class="fp-folder-name">${this.escapeHtml(folder)}</div>
                        ${this.options.mode === 'folder' ? '<div class="fp-file-check">✓</div>' : ''}
                    </div>
                `;
            });
            html += '</div>';
        }

        // Visa filer (endast i fil-mode)
        if (files && files.length > 0 && this.options.mode === 'file') {
            if (view === 'grid') {
                html += '<div class="fp-files-grid">';
                files.forEach(file => {
                    html += this.renderFileCard(file);
                });
                html += '</div>';
            } else {
                html += '<div class="fp-files-list">';
                html += '<table class="fp-table">';
                html += `
                    <thead>
                        <tr>
                            <th></th>
                            <th>Namn</th>
                            <th>Storlek</th>
                            <th>Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                `;
                files.forEach(file => {
                    html += this.renderFileRow(file);
                });
                html += '</tbody></table></div>';
            }
        } else if (!folders || folders.length === 0) {
            html = '<div class="fp-empty">📭 Inga filer i denna mapp</div>';
        }

        content.innerHTML = html;

        // Lägg till click-handlers
        this.attachFileListeners();
    }

    renderFileCard(file) {
        const isImage = file.mime_type.startsWith('image/');
        const icon = this.getFileIcon(file.mime_type);
        const isSelected = this.selectedFiles.some(f => f.id === file.id);

        return `
            <div class="fp-file-item ${isSelected ? 'selected' : ''}" data-file-id="${file.id}">
                <div class="fp-file-preview">
                    ${isImage
                        ? `<img src="serve.php?id=${file.id}" alt="${this.escapeHtml(file.original_name)}">`
                        : `<div class="fp-file-icon">${icon}</div>`
                    }
                </div>
                <div class="fp-file-name" title="${this.escapeHtml(file.original_name)}">
                    ${this.escapeHtml(this.truncateFilename(file.original_name, 20))}
                </div>
                <div class="fp-file-size">${this.formatFileSize(file.file_size)}</div>
                <div class="fp-file-check">✓</div>
            </div>
        `;
    }

    renderFileRow(file) {
        const isImage = file.mime_type.startsWith('image/');
        const icon = this.getFileIcon(file.mime_type);
        const isSelected = this.selectedFiles.some(f => f.id === file.id);

        return `
            <tr class="fp-file-row ${isSelected ? 'selected' : ''}" data-file-id="${file.id}">
                <td class="fp-file-preview-cell">
                    ${isImage
                        ? `<img src="serve.php?id=${file.id}" alt="" class="fp-file-thumb">`
                        : `<span class="fp-file-icon-small">${icon}</span>`
                    }
                </td>
                <td>${this.escapeHtml(file.original_name)}</td>
                <td>${this.formatFileSize(file.file_size)}</td>
                <td>${this.formatDate(file.created_at)}</td>
            </tr>
        `;
    }

    attachFileListeners() {
        // Folder clicks
        this.modal.querySelectorAll('.fp-folder-item').forEach(folder => {
            folder.addEventListener('click', () => {
                const folderName = folder.dataset.folder;

                // Om vi är i mappväljare-läge, välj mappen
                if (this.options.mode === 'folder') {
                    this.toggleFolderSelection(folderName);
                } else {
                    // Annars navigera in i mappen
                    this.currentFolder = folderName;
                    this.loadFiles();
                }
            });
        });

        // File clicks
        this.modal.querySelectorAll('.fp-file-item, .fp-file-row').forEach(item => {
            item.addEventListener('click', async () => {
                const fileId = parseInt(item.dataset.fileId);
                await this.toggleFileSelection(fileId);
            });
        });

        // Breadcrumb clicks
        this.modal.querySelectorAll('.fp-breadcrumb a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.currentFolder = link.dataset.folder;
                this.loadFiles();
            });
        });
    }

    toggleFolderSelection(folderName) {
        const index = this.selectedFolders.indexOf(folderName);

        if (index > -1) {
            // Avmarkera
            this.selectedFolders.splice(index, 1);
        } else {
            // Markera
            if (!this.options.multiple) {
                this.selectedFolders = [];
            }
            this.selectedFolders.push(folderName);
        }

        this.updateFolderSelection();
        this.updateSelectButton();
    }

    updateFolderSelection() {
        this.modal.querySelectorAll('.fp-folder-item').forEach(item => {
            const folderName = item.dataset.folder;
            const isSelected = this.selectedFolders.includes(folderName);
            item.classList.toggle('selected', isSelected);
        });
    }

    async toggleFileSelection(fileId) {
        const index = this.selectedFiles.findIndex(f => f.id === fileId);

        if (index > -1) {
            // Avmarkera
            this.selectedFiles.splice(index, 1);
        } else {
            // Markera
            if (!this.options.multiple) {
                this.selectedFiles = [];
            }

            // Hämta fildata
            try {
                const response = await fetch(`file-picker-api.php?id=${fileId}`);
                const data = await response.json();
                if (data.success) {
                    this.selectedFiles.push(data.file);
                }
            } catch (error) {
                console.error('Kunde inte hämta fildata:', error);
                return;
            }
        }

        this.updateSelection();
        this.updateSelectButton();
    }

    updateSelection() {
        this.modal.querySelectorAll('.fp-file-item, .fp-file-row').forEach(item => {
            const fileId = parseInt(item.dataset.fileId);
            const isSelected = this.selectedFiles.some(f => f.id === fileId);
            item.classList.toggle('selected', isSelected);
        });
    }

    updateSelectButton() {
        const selectBtn = this.modal.querySelector('.fp-btn-select');
        const selectedInfo = this.modal.querySelector('.fp-selected-count');

        if (this.options.mode === 'folder') {
            // Mappväljare
            if (this.selectedFolders.length > 0) {
                selectBtn.disabled = false;
                const count = this.selectedFolders.length;
                selectedInfo.textContent = count === 1
                    ? `1 mapp vald: ${this.selectedFolders[0]}`
                    : `${count} mappar valda`;
            } else {
                selectBtn.disabled = true;
                selectedInfo.textContent = 'Ingen mapp vald';
            }
        } else {
            // Filväljare
            if (this.selectedFiles.length > 0) {
                selectBtn.disabled = false;
                const count = this.selectedFiles.length;
                selectedInfo.textContent = count === 1
                    ? `1 fil vald: ${this.selectedFiles[0].original_name}`
                    : `${count} filer valda`;
            } else {
                selectBtn.disabled = true;
                selectedInfo.textContent = 'Ingen fil vald';
            }
        }
    }

    updateBreadcrumb() {
        const breadcrumb = this.modal.querySelector('.fp-breadcrumb');
        let html = '<a href="#" data-folder="">🏠 Rot</a>';

        if (this.currentFolder) {
            html += ` <span>/</span> <span>${this.escapeHtml(this.currentFolder)}</span>`;
        }

        breadcrumb.innerHTML = html;

        // Re-attach listeners
        breadcrumb.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.currentFolder = link.dataset.folder;
                this.loadFiles();
            });
        });
    }

    setView(view) {
        this.modal.querySelectorAll('.fp-view-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        this.loadFiles();
    }

    selectFiles() {
        if (!this.callback) {
            this.close();
            return;
        }

        if (this.options.mode === 'folder') {
            // Returnera mappar
            if (this.selectedFolders.length > 0) {
                if (this.options.multiple) {
                    this.callback(this.selectedFolders);
                } else {
                    this.callback(this.selectedFolders[0]);
                }
            }
        } else {
            // Returnera filer
            if (this.selectedFiles.length > 0) {
                if (this.options.multiple) {
                    this.callback(this.selectedFiles);
                } else {
                    this.callback(this.selectedFiles[0]);
                }
            }
        }
        this.close();
    }

    // Hjälpfunktioner
    getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) return '🖼️';
        if (mimeType === 'application/pdf') return '📄';
        if (mimeType.startsWith('text/')) return '📝';
        return '📎';
    }

    formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('sv-SE') + ' ' + date.toLocaleTimeString('sv-SE', { hour: '2-digit', minute: '2-digit' });
    }

    truncateFilename(filename, maxLength) {
        if (filename.length <= maxLength) return filename;
        const ext = filename.split('.').pop();
        const name = filename.substring(0, filename.length - ext.length - 1);
        const truncated = name.substring(0, maxLength - ext.length - 4) + '...';
        return truncated + '.' + ext;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instans
let filePickerInstance = null;

// Global funktion för att öppna filväljaren
function openFilePicker(options = {}) {
    if (!filePickerInstance) {
        filePickerInstance = new FilePicker();
    }
    filePickerInstance.open(options);
}

// Auto-init när DOM är redo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        filePickerInstance = new FilePicker();
    });
} else {
    filePickerInstance = new FilePicker();
}
