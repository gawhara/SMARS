import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (sidebar && toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Collapsible sidebar submenus.
    document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.closest('[data-nav-group]');
            if (!group) {
                return;
            }
            const isOpen = group.classList.toggle('open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });
});
