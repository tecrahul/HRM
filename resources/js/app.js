import './bootstrap';
import { mountAdminDashboardSummaryCards } from './components/AdminDashboardSummaryCards';
import { mountAdminAttendanceOverview } from './components/AdminAttendanceOverview';
import { mountAdminLeaveOverview } from './components/AdminLeaveOverview';
import { mountAdminPayrollManagement } from './components/AdminPayrollManagement';
import { mountPayrollWorkspaceApp } from './components/payroll/PayrollWorkspaceApp';
import { mountEmployeeAutocompletes } from './components/EmployeeAutocomplete';
import { mountEmployeeOnboardingOverview } from './components/employees/EmployeeOnboardingOverview';
import { mountDashboardGreetings } from './components/dashboard/GreetingHeader';
import { mountSmtpSettingsPage } from './components/settings/SmtpSettingsApp';
import { mountBranchesPageApp } from './components/branches/BranchesPageApp';
import { mountDepartmentsPageApp } from './components/departments/DepartmentsPageApp';
import { mountLeaveManagementPage } from './pages/LeaveManagement/LeaveManagementPage';
import { mountAttendancePage } from './pages/Attendance/AttendancePage';
import { mountHolidaysPage } from './pages/Holidays/HolidaysPage';

const syncLegacyModalScrollLock = () => {
    const hasOpenLegacyModal = Boolean(document.querySelector('.modal-backdrop.is-open'));
    document.body.classList.toggle('app-legacy-modal-open', hasOpenLegacyModal);
};

const toggleModal = (modalName, open) => {
    const modal = document.querySelector(`[data-modal="${modalName}"]`);

    if (!modal) {
        return;
    }

    modal.classList.toggle('is-open', open);
    syncLegacyModalScrollLock();
};

const toggleUserMenu = (open) => {
    const menu = document.querySelector('[data-user-menu]');
    const trigger = document.querySelector('[data-user-menu-toggle]');

    if (!menu || !trigger) {
        return;
    }

    menu.classList.toggle('is-open', open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
};

document.addEventListener('click', (event) => {
    const userMenuToggle = event.target.closest('[data-user-menu-toggle]');
    const userMenuBody = event.target.closest('[data-user-menu]');

    if (userMenuToggle) {
        const isOpen = userMenuToggle.getAttribute('aria-expanded') === 'true';
        toggleUserMenu(!isOpen);
        return;
    }

    if (!userMenuBody) {
        toggleUserMenu(false);
    }

    const openTrigger = event.target.closest('[data-modal-open]');
    if (openTrigger) {
        toggleModal(openTrigger.dataset.modalOpen, true);
        return;
    }

    const closeTrigger = event.target.closest('[data-modal-close]');
    if (closeTrigger) {
        toggleModal(closeTrigger.dataset.modalClose, false);
        return;
    }

    const backdrop = event.target.closest('.modal-backdrop');
    if (backdrop && event.target === backdrop) {
        backdrop.classList.remove('is-open');
        syncLegacyModalScrollLock();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    toggleUserMenu(false);

    document.querySelectorAll('.modal-backdrop.is-open').forEach((modal) => {
        modal.classList.remove('is-open');
    });
    syncLegacyModalScrollLock();
});

mountAdminDashboardSummaryCards();
mountAdminAttendanceOverview();
mountAdminLeaveOverview();
mountAdminPayrollManagement();
mountPayrollWorkspaceApp();
mountEmployeeAutocompletes();
mountEmployeeOnboardingOverview();
mountDashboardGreetings();
mountSmtpSettingsPage();
mountBranchesPageApp();
mountDepartmentsPageApp();
mountLeaveManagementPage();
mountAttendancePage();
mountHolidaysPage();
