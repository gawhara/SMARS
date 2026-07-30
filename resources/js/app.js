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

    // Quick date-range presets: fill the from/to date inputs (and their
    // flatpickr instances) within the same form.
    const pad = (n) => String(n).padStart(2, '0');
    const fmt = (dt) => `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`;
    const rangeFor = (key) => {
        const d = new Date();
        const today = new Date();
        switch (key) {
            case 'this_month': return [fmt(new Date(d.getFullYear(), d.getMonth(), 1)), fmt(today)];
            case 'last_month': return [fmt(new Date(d.getFullYear(), d.getMonth() - 1, 1)), fmt(new Date(d.getFullYear(), d.getMonth(), 0))];
            case 'last_3_months': return [fmt(new Date(d.getFullYear(), d.getMonth() - 2, 1)), fmt(today)];
            case 'this_year': return [fmt(new Date(d.getFullYear(), 0, 1)), fmt(today)];
            default: return [fmt(today), fmt(today)];
        }
    };
    const setDateInput = (input, value) => {
        if (!input) return;
        if (input._flatpickr) {
            input._flatpickr.setDate(value, true);
        } else {
            input.value = value;
        }
    };
    document.querySelectorAll('[data-range]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('form');
            if (!form) return;
            const [from, to] = rangeFor(button.dataset.range);
            setDateInput(form.querySelector('input[name="date_from"]'), from);
            setDateInput(form.querySelector('input[name="date_to"]'), to);
            form.querySelectorAll('[data-range]').forEach((b) => b.classList.toggle('active', b === button));
        });
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
