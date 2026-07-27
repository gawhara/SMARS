import './bootstrap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

document.addEventListener('DOMContentLoaded', () => {
    // Day/month/year date pickers everywhere: flatpickr shows d/m/Y while the real
    // field still submits Y-m-d, so no backend changes are needed.
    document.querySelectorAll('input[type="date"]').forEach((input) => {
        // Inside a <dialog> (top layer) the calendar must live in the dialog and
        // render statically, otherwise it appears behind the dialog backdrop.
        const dialog = input.closest('dialog');
        flatpickr(input, {
            altInput: true,
            altFormat: 'd/m/Y',
            altInputClass: 'flatpickr-alt',
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
            ...(dialog ? { appendTo: dialog, static: true } : {}),
        });
    });

    // Range-picker dialogs (e.g. print monthly report): open/close controls.
    document.querySelectorAll('[data-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.dataset.dialogOpen)?.showModal();
        });
    });
    document.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

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

    const provisionForm = document.querySelector('[data-provision-form]');
    if (provisionForm) {
        const selectAll = provisionForm.querySelector('[data-select-all]');
        const boxes = [...provisionForm.querySelectorAll('[data-employee]:not(:disabled)')];
        const actions = [...provisionForm.querySelectorAll('[data-needs-selection]')];

        const sync = () => {
            const selected = boxes.filter((box) => box.checked).length;
            actions.forEach((button) => {
                // Never re-enable a button disabled for another reason (e.g. no target device).
                if (!button.dataset.lockedOff) {
                    button.disabled = selected === 0;
                }
            });
            if (selectAll) {
                selectAll.checked = boxes.length > 0 && selected === boxes.length;
                selectAll.indeterminate = selected > 0 && selected < boxes.length;
            }
        };

        actions.forEach((button) => {
            if (button.disabled) {
                button.dataset.lockedOff = 'true';
            }
        });
        selectAll?.addEventListener('change', () => {
            boxes.forEach((box) => { box.checked = selectAll.checked; });
            sync();
        });
        boxes.forEach((box) => box.addEventListener('change', sync));
        sync();
    }

    const reconciliationForm = document.querySelector('[data-reconciliation-form]');
    if (reconciliationForm) {
        const selectAll = reconciliationForm.querySelector('[data-select-all]');
        const rowSelectors = [...reconciliationForm.querySelectorAll('[data-row-select]:not(:disabled)')];
        const count = reconciliationForm.querySelector('[data-selected-count]');
        const actions = reconciliationForm.querySelectorAll('[data-bulk-action]');

        const syncSelection = () => {
            const selected = rowSelectors.filter((checkbox) => checkbox.checked).length;
            count.textContent = String(selected);
            actions.forEach((button) => { button.disabled = selected === 0; });
            if (selectAll) {
                selectAll.checked = rowSelectors.length > 0 && selected === rowSelectors.length;
                selectAll.indeterminate = selected > 0 && selected < rowSelectors.length;
            }
        };

        selectAll?.addEventListener('change', () => {
            rowSelectors.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
            syncSelection();
        });
        rowSelectors.forEach((checkbox) => checkbox.addEventListener('change', syncSelection));
        syncSelection();
    }
});
