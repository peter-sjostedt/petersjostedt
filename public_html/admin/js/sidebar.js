/**
 * Sidebar menu groups
 */
(function() {
    'use strict';

    // Menu group toggle
    const menuGroups = document.querySelectorAll('.menu-group-header');

    menuGroups.forEach(function(header, index) {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            const group = this.parentElement;
            group.classList.toggle('collapsed');

            // Save state to localStorage using index
            const isCollapsed = group.classList.contains('collapsed');
            localStorage.setItem('menu-group-' + index, isCollapsed ? 'collapsed' : 'expanded');
        });
    });

    // Restore saved states
    document.querySelectorAll('.menu-group').forEach(function(group, index) {
        const savedState = localStorage.getItem('menu-group-' + index);
        if (savedState === 'collapsed') {
            group.classList.add('collapsed');
        }
    });
})();
