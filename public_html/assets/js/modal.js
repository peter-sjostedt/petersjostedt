/**
 * Modal System - JavaScript
 * Version: 1.0.0
 * 
 * Användning:
 *   Modal.info('Titel', 'Meddelande');
 *   Modal.success('Titel', 'Meddelande');
 *   Modal.warning('Titel', 'Meddelande');
 *   Modal.error('Titel', 'Meddelande');
 *   const result = await Modal.confirm('Titel', 'Meddelande', options);
 *   const value = await Modal.prompt('Titel', 'Meddelande', 'placeholder');
 *   Modal.loading('Titel', 'Meddelande');
 *   Modal.close();
 */

const Modal = (function() {
    'use strict';

    // DOM Elements (skapas vid init)
    let overlay, container, headerEl, iconEl, titleEl, bodyEl, footerEl, closeBtn;
    let isInitialized = false;
    let currentResolve = null;

    // Ikoner för olika modaltyper
    const icons = {
        info: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        success: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        warning: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        error: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        confirm: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        prompt: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        loading: '<div class="modal-spinner"></div>'
    };

    /**
     * Initierar modal-systemet (skapar DOM-element)
     */
    function init() {
        if (isInitialized) return;

        // Skapa overlay
        overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'modalOverlay';

        // Skapa modal HTML
        overlay.innerHTML = `
            <div class="modal" id="modalContainer">
                <button class="modal-close" id="modalClose" type="button" aria-label="Stäng">&times;</button>
                <div class="modal-header">
                    <div class="modal-icon" id="modalIcon"></div>
                    <h2 class="modal-title" id="modalTitle"></h2>
                </div>
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-footer" id="modalFooter"></div>
            </div>
        `;

        document.body.appendChild(overlay);

        // Hämta element-referenser
        container = document.getElementById('modalContainer');
        headerEl = document.querySelector('.modal-header');
        iconEl = document.getElementById('modalIcon');
        titleEl = document.getElementById('modalTitle');
        bodyEl = document.getElementById('modalBody');
        footerEl = document.getElementById('modalFooter');
        closeBtn = document.getElementById('modalClose');

        // Event listeners
        closeBtn.addEventListener('click', () => close(false));

        document.addEventListener('keydown', handleKeydown);

        isInitialized = true;
    }

    /**
     * Hanterar tangentbordsinput
     */
    function handleKeydown(e) {
        if (!overlay.classList.contains('active')) return;

        if (e.key === 'Escape') {
            close(false);
        } else if (e.key === 'Enter') {
            const primaryBtn = footerEl.querySelector('.modal-btn.primary, .modal-btn.success, .modal-btn.danger');
            if (primaryBtn) {
                primaryBtn.click();
            }
        }
    }

    /**
     * Visar modal
     */
    function show(type, title, message, options = {}) {
        init();

        return new Promise((resolve) => {
            currentResolve = resolve;

            // Sätt ikon och typ
            headerEl.className = 'modal-header ' + type;
            iconEl.className = 'modal-icon ' + type;
            iconEl.innerHTML = icons[type] || icons.info;

            // Sätt titel
            titleEl.textContent = title;

            // Sätt body-innehåll
            if (options.html) {
                bodyEl.innerHTML = message;
            } else if (options.input) {
                const escapedMessage = escapeHtml(message);
                const escapedPlaceholder = escapeHtml(options.placeholder || '');
                bodyEl.innerHTML = `
                    <p>${escapedMessage}</p>
                    <input type="${options.inputType || 'text'}" 
                           class="modal-input" 
                           id="modalInput" 
                           placeholder="${escapedPlaceholder}"
                           ${options.inputValue ? `value="${escapeHtml(options.inputValue)}"` : ''}>
                `;
            } else {
                bodyEl.textContent = message;
            }

            // Rensa och bygg footer
            footerEl.innerHTML = '';
            closeBtn.style.display = options.hideClose ? 'none' : 'flex';

            if (options.buttons && options.buttons.length > 0) {
                options.buttons.forEach(btn => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'modal-btn ' + (btn.class || 'primary');
                    button.textContent = btn.text;
                    button.addEventListener('click', () => {
                        if (options.input && btn.value === true) {
                            const input = document.getElementById('modalInput');
                            close(input ? input.value : '');
                        } else {
                            close(btn.value);
                        }
                    });
                    footerEl.appendChild(button);
                });
            }

            // Visa modal
            overlay.classList.add('active');

            // Fokusera input eller primär knapp
            setTimeout(() => {
                const input = document.getElementById('modalInput');
                if (input) {
                    input.focus();
                    input.select();
                } else {
                    const primaryBtn = footerEl.querySelector('.modal-btn.primary, .modal-btn.success');
                    if (primaryBtn) primaryBtn.focus();
                }
            }, 100);

            // Auto-stäng om specificerat
            if (options.autoClose && typeof options.autoClose === 'number') {
                setTimeout(() => close(true), options.autoClose);
            }
        });
    }

    /**
     * Stänger modal
     */
    function close(result = null) {
        if (!overlay) return;
        
        overlay.classList.remove('active');
        
        if (currentResolve) {
            currentResolve(result);
            currentResolve = null;
        }
    }

    /**
     * Escape HTML för säkerhet
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Publikt API
    return {
        /**
         * Visar informationsmodal
         */
        info: (title, message, options = {}) => show('info', title, message, {
            ...options,
            buttons: [{ text: options.buttonText || 'OK', class: 'primary', value: true }]
        }),

        /**
         * Visar success-modal
         */
        success: (title, message, options = {}) => show('success', title, message, {
            ...options,
            buttons: [{ text: options.buttonText || 'OK', class: 'success', value: true }]
        }),

        /**
         * Visar varningsmodal
         */
        warning: (title, message, options = {}) => show('warning', title, message, {
            ...options,
            buttons: [{ text: options.buttonText || 'OK', class: 'primary', value: true }]
        }),

        /**
         * Visar felmodal
         */
        error: (title, message, options = {}) => show('error', title, message, {
            ...options,
            buttons: [{ text: options.buttonText || 'Stäng', class: 'danger', value: true }]
        }),

        /**
         * Visar bekräftelsemodal
         * @returns {Promise<boolean>}
         */
        confirm: (title, message, options = {}) => show('confirm', title, message, {
            ...options,
            buttons: [
                { text: options.cancelText || 'Avbryt', class: 'cancel', value: false },
                { text: options.confirmText || 'Bekräfta', class: options.danger ? 'danger' : 'primary', value: true }
            ]
        }),

        /**
         * Visar prompt-modal för användarinput
         * @returns {Promise<string|false>}
         */
        prompt: (title, message, options = {}) => {
            // Hantera om options är en string (placeholder)
            if (typeof options === 'string') {
                options = { placeholder: options };
            }
            return show('prompt', title, message, {
                ...options,
                input: true,
                buttons: [
                    { text: options.cancelText || 'Avbryt', class: 'cancel', value: false },
                    { text: options.confirmText || 'OK', class: 'primary', value: true }
                ]
            });
        },

        /**
         * Visar laddningsmodal (stäng manuellt med Modal.close())
         */
        loading: (title, message = 'Vänligen vänta...') => show('loading', title, message, {
            hideClose: true,
            buttons: []
        }),

        /**
         * Stänger aktiv modal
         */
        close: close,

        /**
         * Anpassad modal med full kontroll
         */
        custom: show
    };
})();

// Exportera för ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Modal;
}