document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-clear-on-load]').forEach((input) => {
        input.value = '';
        window.setTimeout(() => {
            input.value = '';
        }, 100);
    });

    document.querySelectorAll('.confirm-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirm || 'Are you sure you want to continue?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-directorate-select]').forEach((directorateSelect) => {
        const form = directorateSelect.closest('form') || document;
        const departmentSelect = form.querySelector('[data-department-select]');
        if (!departmentSelect) {
            return;
        }

        const departmentOptions = Array.from(departmentSelect.options);
        const allowAllDepartments = directorateSelect.dataset.allowAllDepartments === 'true';

        function filterDepartments() {
            const directorateId = directorateSelect.value;

            departmentOptions.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const matches = (allowAllDepartments && directorateId === '') || option.dataset.directorateId === directorateId;
                option.hidden = !matches;
                option.disabled = !matches;
            });

            const selectedOption = departmentSelect.selectedOptions[0];
            if (selectedOption && selectedOption.value !== '' && selectedOption.disabled) {
                departmentSelect.value = '';
            }
        }

        directorateSelect.addEventListener('change', filterDepartments);
        filterDepartments();
    });

    document.querySelectorAll('[data-worker-role-select]').forEach((roleSelect) => {
        const form = roleSelect.closest('form');
        if (!form) {
            return;
        }

        const officeScopeFields = Array.from(form.querySelectorAll('[data-office-scope-field]'));
        const officeScopeNote = form.querySelector('[data-hr-office-note]');

        function toggleOfficeScope() {
            const isHrOffice = roleSelect.value === 'hr';

            if (officeScopeNote) {
                officeScopeNote.hidden = !isHrOffice;
            }

            officeScopeFields.forEach((field) => {
                field.hidden = isHrOffice;
                field.querySelectorAll('input, select, textarea').forEach((control) => {
                    if (control.dataset.wasRequired === undefined) {
                        control.dataset.wasRequired = control.required ? 'true' : 'false';
                    }

                    if (isHrOffice) {
                        control.value = '';
                        control.required = false;
                        control.disabled = true;
                        return;
                    }

                    control.disabled = false;
                    control.required = control.dataset.wasRequired === 'true';
                });
            });

            const directorateSelect = form.querySelector('[data-directorate-select]');
            if (directorateSelect && !isHrOffice) {
                directorateSelect.dispatchEvent(new Event('change'));
            }
        }

        roleSelect.addEventListener('change', toggleOfficeScope);
        toggleOfficeScope();
    });

    const leavePlanner = document.querySelector('[data-leave-planner]');
    const leavePlannerDataElement = document.getElementById('leave-planner-data');

    if (leavePlanner && leavePlannerDataElement) {
        let plannerData = { leaveTypes: [], holidays: [] };

        try {
            plannerData = JSON.parse(leavePlannerDataElement.textContent || '{}');
        } catch (error) {
            plannerData = { leaveTypes: [], holidays: [] };
        }

        const leaveTypes = new Map((plannerData.leaveTypes || []).map((type) => [String(type.id), type]));
        const holidays = new Map((plannerData.holidays || []).map((holiday) => [holiday.holiday_date, holiday.name]));
        const typeInput = leavePlanner.querySelector('[data-leave-type-select]');
        const startInput = leavePlanner.querySelector('[data-leave-start-date]');
        const daysDisplay = leavePlanner.querySelector('[data-leave-days-display]');
        const endInput = leavePlanner.querySelector('[data-leave-end-date]');
        const noBackdate = leavePlanner.dataset.noBackdate === '1';
        const typeHint = leavePlanner.querySelector('[data-leave-type-hint]');
        const daysHint = leavePlanner.querySelector('[data-leave-days-hint]');
        const attachmentHint = leavePlanner.querySelector('[data-planner-attachment]');
        const status = leavePlanner.querySelector('[data-planner-status]');
        const plannerDays = leavePlanner.querySelector('[data-planner-days]');
        const plannerEnd = leavePlanner.querySelector('[data-planner-end]');
        const plannerReturn = leavePlanner.querySelector('[data-planner-return]');
        const plannerBalance = leavePlanner.querySelector('[data-planner-balance]');
        const plannerNote = leavePlanner.querySelector('[data-planner-note]');

        const pad = (value) => String(value).padStart(2, '0');
        const formatIsoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        const formatHumanDate = (date) => date.toLocaleDateString(undefined, {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
        const formatNumber = (value) => Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todayIso = formatIsoDate(today);

        function parseIsoDate(value) {
            if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                return null;
            }

            const [year, month, day] = value.split('-').map(Number);
            return new Date(year, month - 1, day);
        }

        function isBusinessDay(date) {
            const day = date.getDay();
            return day !== 0 && day !== 6 && !holidays.has(formatIsoDate(date));
        }

        function firstBusinessDayOnOrAfter(date) {
            const cursor = new Date(date);
            let guard = 0;

            while (!isBusinessDay(cursor) && guard < 60) {
                cursor.setDate(cursor.getDate() + 1);
                guard += 1;
            }

            return cursor;
        }

        function businessDaysBetween(startDate, endDate) {
            if (!startDate || !endDate || endDate < startDate) {
                return 0;
            }

            const cursor = new Date(startDate);
            let days = 0;

            while (cursor <= endDate) {
                if (isBusinessDay(cursor)) {
                    days += 1;
                }

                cursor.setDate(cursor.getDate() + 1);
            }

            return days;
        }

        function calculateReturnDate(endDate) {
            const cursor = new Date(endDate);
            cursor.setDate(cursor.getDate() + 1);

            return firstBusinessDayOnOrAfter(cursor);
        }

        function holidayNamesBetween(startDate, endDate) {
            const cursor = new Date(startDate);
            const names = [];

            while (cursor <= endDate) {
                const isoDate = formatIsoDate(cursor);
                if (holidays.has(isoDate)) {
                    names.push(holidays.get(isoDate));
                }
                cursor.setDate(cursor.getDate() + 1);
            }

            return names;
        }

        function setStatus(text, state = '') {
            if (!status) {
                return;
            }

            status.textContent = text;
            status.className = `badge${state ? ` ${state}` : ''}`;
        }

        function setPlannerText(element, text) {
            if (element) {
                element.textContent = text;
            }
        }

        function selectedLeaveType() {
            return typeInput ? leaveTypes.get(typeInput.value) : null;
        }

        function updateDayFields(requestedDays, hasCompleteRange) {
            if (daysDisplay) {
                daysDisplay.value = requestedDays > 0
                    ? `${requestedDays} working day${requestedDays === 1 ? '' : 's'}`
                    : (hasCompleteRange ? 'No working days selected' : 'Calculated from selected dates');
            }
        }

        function updateTypeGuidance(type, requestedDays) {
            if (!type) {
                setPlannerText(typeHint, 'Select a leave type to see its rules.');
                setPlannerText(daysHint, 'Working days are calculated from the selected start and end dates.');
                setPlannerText(attachmentHint, 'PDF attachment guidance will appear after selecting a leave type.');
                return;
            }

            const entitlement = formatNumber(type.defaultEntitlement);
            const available = type.availableDays === null ? null : Number(type.availableDays);
            const paidLabel = type.isPaid ? 'Paid leave' : 'Unpaid leave';
            const autoLabel = requestedDays > 0
                ? `${requestedDays} working day${requestedDays === 1 ? '' : 's'} will be requested`
                : 'select dates to calculate working days';
            const balanceLabel = type.requiresBalance
                ? `${available === null ? entitlement : formatNumber(available)} day${available === 1 ? '' : 's'} available`
                : 'does not deduct leave balance';

            setPlannerText(typeHint, `${type.name}: ${paidLabel}, ${autoLabel}; ${balanceLabel}.`);

            if (type.requiresBalance && available !== null) {
                setPlannerText(daysHint, `Working days are counted from the selected date range. ${formatNumber(available)} working day${available === 1 ? '' : 's'} currently available.`);
            } else {
                setPlannerText(daysHint, 'Working days are counted from the selected date range. This leave type does not count against the normal leave balance.');
            }

            if (type.requiresAttachment) {
                setPlannerText(attachmentHint, 'PDF attachment is required for this leave type.');
            } else if (type.attachmentAfterDays !== null) {
                const threshold = Number(type.attachmentAfterDays);
                const requiredNow = requestedDays >= threshold;
                setPlannerText(
                    attachmentHint,
                    requiredNow
                        ? `PDF attachment is required because this request is ${requestedDays} working days or more.`
                        : `PDF attachment is required when this leave reaches ${formatNumber(threshold)} working days.`
                );
            } else {
                setPlannerText(attachmentHint, 'No attachment is required unless HR asks for PDF supporting evidence.');
            }
        }

        function balanceSummary(type, requestedDays) {
            if (!type) {
                return '-';
            }

            if (!type.requiresBalance) {
                return 'Uncounted';
            }

            if (type.availableDays === null) {
                return 'Check balance';
            }

            const available = Number(type.availableDays);
            if (!requestedDays) {
                return `${formatNumber(available)} available`;
            }

            if (available >= requestedDays) {
                return `${formatNumber(available - requestedDays)} left`;
            }

            return `${formatNumber(requestedDays - available)} short`;
        }

        function updatePlanner() {
            const type = selectedLeaveType();
            const startDate = parseIsoDate(startInput ? startInput.value : '');
            const endDate = parseIsoDate(endInput ? endInput.value : '');
            const hasCompleteRange = Boolean(startDate && endDate);
            const requestedDays = hasCompleteRange ? businessDaysBetween(startDate, endDate) : 0;
            const hasRequestedDays = requestedDays > 0;

            if (noBackdate) {
                if (startInput) {
                    startInput.min = todayIso;
                }

                if (endInput) {
                    endInput.min = startInput && startInput.value && startInput.value > todayIso
                        ? startInput.value
                        : todayIso;
                }
            } else if (endInput && startInput && startInput.value) {
                endInput.min = startInput.value;
            }

            updateDayFields(requestedDays, hasCompleteRange);
            updateTypeGuidance(type, hasRequestedDays ? requestedDays : 0);
            setPlannerText(plannerDays, hasRequestedDays ? `${requestedDays} day${requestedDays === 1 ? '' : 's'}` : '-');
            setPlannerText(plannerBalance, balanceSummary(type, hasRequestedDays ? requestedDays : 0));
            setPlannerText(plannerEnd, '-');
            setPlannerText(plannerReturn, '-');

            if (!type) {
                setStatus('Select leave type', 'warning');
                setPlannerText(plannerNote, 'Choose a leave type to see working days, balance, leave end date, and report-back date.');
                return;
            }

            if (!startDate) {
                setStatus('Waiting for date', 'warning');
                setPlannerText(plannerNote, 'Select the start date and end date to calculate working days and balance.');
                return;
            }

            if (!endDate) {
                setStatus('Waiting for end date', 'warning');
                setPlannerText(plannerNote, 'Select the end date to calculate working days, balance, and report-back date.');
                return;
            }

            if (endDate < startDate) {
                setStatus('Check dates', 'danger');
                setPlannerText(plannerNote, 'End date cannot be earlier than the start date.');
                return;
            }

            if (!hasRequestedDays) {
                setStatus('No working days', 'danger');
                setPlannerText(plannerNote, 'The selected range does not include a working day. Weekends and public holidays are skipped.');
                return;
            }

            const returnDate = calculateReturnDate(endDate);
            const firstBusinessDay = firstBusinessDayOnOrAfter(startDate);
            const skippedHolidays = holidayNamesBetween(startDate, endDate);

            if (endInput) {
                endInput.value = formatIsoDate(endDate);
            }

            setPlannerText(plannerEnd, formatHumanDate(endDate));
            setPlannerText(plannerReturn, formatHumanDate(returnDate));

            const available = type.availableDays === null ? null : Number(type.availableDays);
            if (type.requiresBalance && available !== null && available < requestedDays) {
                setStatus('Balance low', 'danger');
            } else {
                setStatus('Ready', 'success');
            }

            const notes = [];
            if (formatIsoDate(firstBusinessDay) !== formatIsoDate(startDate)) {
                notes.push(`Counting starts on ${formatHumanDate(firstBusinessDay)} because the selected start date is not a working day.`);
            }

            if (skippedHolidays.length > 0) {
                notes.push(`Public holiday skipped: ${skippedHolidays.slice(0, 2).join(', ')}${skippedHolidays.length > 2 ? '...' : ''}.`);
            }

            if (type.requiresBalance && available !== null && available < requestedDays) {
                notes.push('This request is above the available balance and may be rejected.');
            }

            if (notes.length === 0) {
                notes.push('The end date and report-back date are ready for this request.');
            }

            setPlannerText(plannerNote, notes.join(' '));
        }

        [typeInput, startInput, endInput].forEach((input) => {
            if (input) {
                input.addEventListener('input', updatePlanner);
                input.addEventListener('change', updatePlanner);
            }
        });

        updatePlanner();
    }

    const menuToggles = document.querySelectorAll('[data-menu-toggle]');
    const mobileSidebar = document.querySelector('[data-mobile-sidebar]');
    const menuOverlay = document.querySelector('[data-menu-overlay]');
    const menuClose = document.querySelector('[data-menu-close]');

    if (menuToggles.length && mobileSidebar && menuOverlay) {
        function setMobileMenu(open) {
            mobileSidebar.classList.toggle('is-open', open);
            menuOverlay.hidden = !open;
            menuToggles.forEach((t) => t.setAttribute('aria-expanded', open ? 'true' : 'false'));
            document.body.classList.toggle('menu-open', open);
        }

        menuToggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                setMobileMenu(!mobileSidebar.classList.contains('is-open'));
            });
        });

        menuOverlay.addEventListener('click', () => {
            setMobileMenu(false);
        });

        if (menuClose) {
            menuClose.addEventListener('click', () => {
                setMobileMenu(false);
            });
        }

        mobileSidebar.querySelectorAll('.nav a').forEach((link) => {
            link.addEventListener('click', () => {
                setMobileMenu(false);
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 1060) {
                setMobileMenu(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileSidebar.classList.contains('is-open')) {
                setMobileMenu(false);
            }
        });
    }

    const accountToggle = document.querySelector('[data-account-toggle]');
    const accountMenu = document.getElementById('account-profile-menu');

    if (accountToggle && accountMenu) {
        function setAccountMenu(open) {
            accountMenu.hidden = !open;
            accountToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        accountToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            setAccountMenu(accountMenu.hidden);
        });

        accountMenu.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        document.addEventListener('click', () => {
            setAccountMenu(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !accountMenu.hidden) {
                setAccountMenu(false);
                accountToggle.focus();
            }
        });
    }

    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = this.closest('.password-wrap').querySelector('input');
            var open = this.querySelector('.eye-open');
            var closed = this.querySelector('.eye-closed');
            if (input.type === 'password') {
                input.type = 'text';
                open.style.display = 'none';
                closed.style.display = 'block';
                this.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                open.style.display = 'block';
                closed.style.display = 'none';
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });
});
