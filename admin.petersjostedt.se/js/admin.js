/**
 * Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Language switcher
    const langSwitcher = document.querySelector('.lang-switcher select');
    if (langSwitcher) {
        langSwitcher.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('set_lang', this.value);
            window.location.href = url.toString();
        });
    }

    // Confirmation dialogs for delete forms
    const deleteForms = document.querySelectorAll('form[data-confirm]');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const message = form.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Log entry click handlers - använder Modal-systemet
    const truncatedElements = document.querySelectorAll('.truncate[data-time]');
    truncatedElements.forEach(function(element) {
        element.addEventListener('click', function() {
            const time = this.getAttribute('data-time');
            const user = this.getAttribute('data-user');
            const event = this.getAttribute('data-event');
            const ip = this.getAttribute('data-ip');
            const logClass = this.getAttribute('data-class');

            // Bestäm modaltyp baserat på loggklass
            let modalType = 'info';
            let title = 'Logghändelse';

            if (logClass === 'log-failed') {
                modalType = 'error';
                title = 'Misslyckad inloggning';
            } else if (logClass === 'log-login') {
                modalType = 'success';
                title = 'Inloggning';
            } else if (logClass === 'log-security') {
                modalType = 'warning';
                title = 'Säkerhetsvarning';
            }

            // Bygg HTML-innehåll för modalen
            const content = `
                <div style="margin-bottom: 1rem;">
                    <strong>Tid:</strong><br>
                    <span style="color: #aaa;">${time}</span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Användare:</strong><br>
                    <span style="color: #aaa;">${user}</span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Händelse:</strong><br>
                    <span style="color: #aaa; word-wrap: break-word; white-space: pre-wrap;">${event}</span>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>IP-adress:</strong><br>
                    <span style="color: #aaa;">${ip}</span>
                </div>
            `;

            // Visa modal med befintligt Modal-system
            Modal[modalType](title, content, { html: true });
        });
    });
});
