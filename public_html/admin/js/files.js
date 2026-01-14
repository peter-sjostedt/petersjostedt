/**
 * Admin - Filhantering JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Modal state
    let currentAction = null;
    let currentData = {};

    // Modal elements
    const modals = {
        import: document.getElementById('import-modal'),
        delete: document.getElementById('delete-modal'),
        deleteOrphaned: document.getElementById('delete-orphaned-modal'),
        rename: document.getElementById('rename-modal')
    };

    // View toggle
    const gridView = document.querySelector('.files-grid');
    const tableView = document.querySelector('.files-table');
    const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');

    viewToggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;

            // Uppdatera aktiv knapp
            viewToggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Växla vy
            if (view === 'grid') {
                if (gridView) gridView.style.display = 'grid';
                if (tableView) tableView.style.display = 'none';
                localStorage.setItem('filesView', 'grid');
            } else {
                if (gridView) gridView.style.display = 'none';
                if (tableView) tableView.style.display = 'block';
                localStorage.setItem('filesView', 'table');
            }
        });
    });

    // Återställ sparad vy
    const savedView = localStorage.getItem('filesView');
    if (savedView === 'table') {
        const tableBtn = document.querySelector('.view-toggle-btn[data-view="table"]');
        if (tableBtn) tableBtn.click();
    }

    // Auto-submit filter form on change
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        const autoSubmitSelects = filterForm.querySelectorAll('.auto-submit');
        autoSubmitSelects.forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }

    // Helper: Öppna modal
    function openModal(modalId) {
        if (modals[modalId]) {
            modals[modalId].classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    // Helper: Stäng modal
    function closeModal(modalId) {
        if (modals[modalId]) {
            modals[modalId].classList.remove('active');
            document.body.style.overflow = '';
            currentAction = null;
            currentData = {};
        }
    }

    // Helper: Stäng alla modaler
    function closeAllModals() {
        Object.keys(modals).forEach(closeModal);
    }

    // Event delegation för filknappar
    document.body.addEventListener('click', function(e) {
        // Radera fil från DB (regular files och missing on disk)
        if (e.target.classList.contains('delete-file-btn') || e.target.classList.contains('delete-db-btn')) {
            e.preventDefault();
            const fileId = e.target.dataset.fileId;
            const fileName = e.target.dataset.fileName;

            currentAction = 'delete';
            currentData = { fileId };

            document.getElementById('delete-filename').textContent = fileName;
            openModal('delete');
        }

        // Radera orphaned fil
        if (e.target.classList.contains('delete-orphaned-btn')) {
            e.preventDefault();
            const filePath = e.target.dataset.filePath;
            const fileName = e.target.dataset.fileName;

            currentAction = 'deleteOrphaned';
            currentData = { filePath };

            document.getElementById('delete-orphaned-filename').textContent = fileName;
            openModal('deleteOrphaned');
        }

        // Importera orphaned fil
        if (e.target.classList.contains('import-orphaned-btn')) {
            e.preventDefault();
            const filePath = e.target.dataset.filePath;
            const dbPath = e.target.dataset.dbPath;
            const fileName = e.target.dataset.fileName;

            currentAction = 'import';
            currentData = { filePath, dbPath };

            document.getElementById('import-filename').textContent = fileName;
            openModal('import');
        }

        // Byt namn på fil
        if (e.target.classList.contains('rename-file-btn')) {
            e.preventDefault();
            const fileId = e.target.dataset.fileId;
            const fileName = e.target.dataset.fileName;

            currentAction = 'rename';
            currentData = { fileId };

            // Sätt nuvarande filnamn i input-fältet
            const input = document.getElementById('rename-input');
            input.value = fileName;

            openModal('rename');

            // Fokusera och markera texten i input-fältet
            setTimeout(() => {
                input.focus();
                input.select();
            }, 100);
        }
    });

    // Modal knappar - Event delegation
    document.body.addEventListener('click', function(e) {
        const target = e.target;

        // Stäng modal med overlay click
        if (target.classList.contains('modal-overlay')) {
            closeAllModals();
            return;
        }

        // Stäng modal (avbryt eller X)
        if (target.dataset.action === 'cancel') {
            e.preventDefault();
            closeAllModals();
            return;
        }

        // Bekräfta action
        if (target.dataset.action === 'confirm') {
            e.preventDefault();

            switch(currentAction) {
                case 'delete':
                    document.getElementById('delete-file-id').value = currentData.fileId;
                    document.getElementById('delete-form').submit();
                    break;

                case 'deleteOrphaned':
                    document.getElementById('delete-orphaned-path').value = currentData.filePath;
                    document.getElementById('delete-orphaned-form').submit();
                    break;

                case 'import':
                    document.getElementById('import-orphaned-path').value = currentData.filePath;
                    document.getElementById('import-orphaned-db-path').value = currentData.dbPath;
                    document.getElementById('import-orphaned-form').submit();
                    break;

                case 'rename':
                    const newName = document.getElementById('rename-input').value.trim();
                    if (newName) {
                        document.getElementById('rename-file-id').value = currentData.fileId;
                        document.getElementById('rename-new-name').value = newName;
                        document.getElementById('rename-form').submit();
                    }
                    break;
            }

            closeAllModals();
        }
    });

    // Stäng modal med ESC, spara med Enter i rename-input
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }

        // Enter i rename-input sparar
        if (e.key === 'Enter' && e.target.id === 'rename-input') {
            e.preventDefault();
            const newName = document.getElementById('rename-input').value.trim();
            if (newName && currentAction === 'rename') {
                document.getElementById('rename-file-id').value = currentData.fileId;
                document.getElementById('rename-new-name').value = newName;
                document.getElementById('rename-form').submit();
                closeAllModals();
            }
        }
    });
});
