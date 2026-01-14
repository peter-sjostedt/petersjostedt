/**
 * Partner Portal - Modal-hantering
 * Kopierat från Hospitex
 */

// Öppna modal
async function openModal(url, params = {}) {
    const overlay = document.getElementById('modal-overlay');
    const container = overlay.querySelector('.modal-container');
    const content = document.getElementById('modal-content');

    // Återställ storlek
    container.className = 'modal-container';

    // Bygg URL med parametrar (relativa sökvägar fungerar direkt med fetch)
    let fetchUrl = url;
    if (Object.keys(params).length > 0) {
        const separator = url.includes('?') ? '&' : '?';
        const queryString = Object.keys(params).map(key => `${key}=${encodeURIComponent(params[key])}`).join('&');
        fetchUrl = url + separator + queryString;
    }

    try {
        const response = await fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Kunde inte ladda modal');
        }

        const html = await response.text();
        content.innerHTML = html;

        // Kolla om modalen ska vara stor
        if (content.querySelector('[data-modal-size="large"]')) {
            container.classList.add('modal-large');
        }

        overlay.classList.remove('hidden');

        // Kör scripts i modal-innehållet
        executeModalScripts(content);

        // Fokusera första input
        const firstInput = content.querySelector('input:not([type="hidden"]), select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }

        // Initiera stäng-knappar
        initModalCloseButtons();

    } catch (error) {
        console.error('Modal error:', error);
        if (typeof Modal !== 'undefined') {
            Modal.info('Fel', 'Kunde inte öppna formulär');
        }
    }
}

// Exekvera scripts i dynamiskt laddat innehåll
function executeModalScripts(container) {
    const scripts = container.querySelectorAll('script');
    let externalScripts = [];
    let inlineScripts = [];

    scripts.forEach(oldScript => {
        if (oldScript.src) {
            externalScripts.push(oldScript);
        } else {
            inlineScripts.push(oldScript);
        }
    });

    // Ladda externa scripts först, sedan kör inline
    let loaded = 0;

    if (externalScripts.length === 0) {
        // Inga externa, kör inline direkt
        inlineScripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    } else {
        // Ladda externa först
        externalScripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            newScript.src = oldScript.src;
            newScript.onload = function() {
                loaded++;
                if (loaded === externalScripts.length) {
                    // Alla externa laddade, kör inline
                    inlineScripts.forEach(inlineScript => {
                        const newInline = document.createElement('script');
                        newInline.textContent = inlineScript.textContent;
                        inlineScript.parentNode.replaceChild(newInline, inlineScript);
                    });
                }
            };
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }
}

// Stäng modal
function closeModal(shouldReload = false) {
    const overlay = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');

    overlay.classList.add('hidden');
    content.innerHTML = '';

    if (shouldReload) {
        window.location.reload();
    }
}

// Initiera stäng-knappar i modal
function initModalCloseButtons() {
    const content = document.getElementById('modal-content');
    const closeButtons = content.querySelectorAll('[data-modal-close]');

    closeButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const shouldReload = this.dataset.reload === 'true';
            closeModal(shouldReload);
        });
    });
}

// Stäng modal vid klick på overlay
document.addEventListener('click', function(e) {
    if (e.target.id === 'modal-overlay') {
        closeModal();
    }

    // Data-modal knappar
    if (e.target.matches('[data-modal]') || e.target.closest('[data-modal]')) {
        e.preventDefault();
        const btn = e.target.closest('[data-modal]') || e.target;
        const modalUrl = btn.dataset.modal;
        openModal(modalUrl);
        return;
    }

    // Data-download-qr knappar
    if (e.target.matches('[data-download-qr]') || e.target.closest('[data-download-qr]')) {
        e.preventDefault();
        if (typeof downloadQR === 'function') {
            downloadQR();
        }
        return;
    }
});

// Stäng modal med Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const overlay = document.getElementById('modal-overlay');
        if (overlay && !overlay.classList.contains('hidden')) {
            closeModal();
        }
    }
});
