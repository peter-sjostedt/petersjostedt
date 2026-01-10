/**
 * Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Language switcher
    const langSwitcher = document.querySelector('.lang-switcher select');
    if (langSwitcher) {
        langSwitcher.addEventListener('change', function() {
            window.location.href = '?set_lang=' + this.value;
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
});
