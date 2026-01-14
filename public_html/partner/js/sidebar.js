/**
 * Partner Portal JavaScript
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

    // Menu group toggle
    const menuGroups = document.querySelectorAll('.menu-group-header');

    menuGroups.forEach(function(header, index) {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            const group = this.parentElement;
            group.classList.toggle('collapsed');

            // Save state to localStorage using index
            const isCollapsed = group.classList.contains('collapsed');
            localStorage.setItem('partner-menu-group-' + index, isCollapsed ? 'collapsed' : 'expanded');
        });
    });

    // Restore saved states
    document.querySelectorAll('.menu-group').forEach(function(group, index) {
        const savedState = localStorage.getItem('partner-menu-group-' + index);
        if (savedState === 'collapsed') {
            group.classList.add('collapsed');
        }
    });
});
