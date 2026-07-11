import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-close]');

    if (sidebar && toggle) {
        const setSidebarOpen = (open) => {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('sidebar-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', () => setSidebarOpen(!sidebar.classList.contains('open')));
        backdrop?.addEventListener('click', () => setSidebarOpen(false));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setSidebarOpen(false);
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 860) {
                setSidebarOpen(false);
            }
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

    const scheduleModes = document.querySelectorAll('input[name="schedule_mode"]');
    const secondShift = document.querySelector('[data-second-shift]');

    if (scheduleModes.length && secondShift) {
        const syncScheduleMode = () => {
            const isDouble = document.querySelector('input[name="schedule_mode"]:checked')?.value === 'double';
            secondShift.hidden = !isDouble;
            secondShift.querySelectorAll('[data-second-required], [data-time-value]').forEach((field) => {
                field.required = isDouble;
            });
        };

        scheduleModes.forEach((input) => input.addEventListener('change', syncScheduleMode));
        syncScheduleMode();
    }

    document.querySelectorAll('[data-time-12]').forEach((control) => {
        const hour = control.querySelector('[data-time-hour]');
        const minute = control.querySelector('[data-time-minute]');
        const period = control.querySelector('[data-time-period]');
        const value = control.querySelector('[data-time-value]');

        const syncTime = () => {
            const hour12 = Number(hour.value);
            const minuteValue = Math.min(59, Math.max(0, Number(minute.value) || 0));
            const hour24 = period.value === 'PM' ? (hour12 % 12) + 12 : hour12 % 12;
            minute.value = String(minuteValue).padStart(2, '0');
            value.value = `${String(hour24).padStart(2, '0')}:${String(minuteValue).padStart(2, '0')}`;
        };

        [hour, minute, period].forEach((input) => input.addEventListener('change', syncTime));
        syncTime();
    });
});
