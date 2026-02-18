import React, { useCallback, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { DashboardPage } from './pages/DashboardPage';
import { GlobalFilterBar } from './shared/ui';
import { SalaryStructuresPage } from './pages/SalaryStructuresPage';
import { ProcessingPage } from './pages/ProcessingPage';
import { HistoryPage } from './pages/HistoryPage';
import { UtilityPage } from './pages/UtilityPage';

const parsePayload = (rootElement) => {
    try {
        const raw = rootElement.dataset.payload;
        return raw ? JSON.parse(raw) : null;
    } catch (_error) {
        return null;
    }
};

function PayrollWorkspaceApp({ payload }) {
    const page = String(payload?.page || 'dashboard');
    const urls = payload?.urls ?? {};
    const routes = payload?.routes ?? {};
    const csrfToken = String(payload?.csrfToken || '');
    const permissions = payload?.permissions ?? {};
    const initialStatus = String(payload?.filters?.status || '');
    const initialAlert = String(payload?.filters?.alert || '');

    const [filters, setFilters] = useState({
        branchId: String(payload?.filters?.branch_id || ''),
        departmentId: String(payload?.filters?.department_id || ''),
        employeeId: String(payload?.filters?.employee_id || ''),
        employee: null,
        payrollMonth: String(payload?.filters?.payroll_month || ''),
    });

    const onFilterChange = useCallback((next) => {
        setFilters((prev) => ({ ...prev, ...next }));
    }, []);

    const onClearFilters = useCallback(() => {
        setFilters((prev) => ({
            ...prev,
            branchId: '',
            departmentId: '',
            employeeId: '',
            employee: null,
        }));
    }, []);

    return (
        <div className="space-y-5">
            {page !== 'processing' ? (
                <GlobalFilterBar
                    urls={urls}
                    filters={filters}
                    employee={filters.employee}
                    onChange={onFilterChange}
                    onClear={onClearFilters}
                />
            ) : null}

            {page === 'dashboard' ? (
                <DashboardPage urls={urls} routes={routes} filters={filters} />
            ) : null}

            {page === 'salary_structures' ? (
                <SalaryStructuresPage
                    urls={urls}
                    csrfToken={csrfToken}
                    filters={filters}
                    initialStatus={initialStatus}
                />
            ) : null}

            {page === 'processing' ? (
                <ProcessingPage
                    urls={urls}
                    csrfToken={csrfToken}
                    filters={filters}
                    onFilterChange={onFilterChange}
                    onClearFilters={onClearFilters}
                    permissions={permissions}
                    initialAlert={initialAlert}
                />
            ) : null}

            {page === 'history' ? (
                <HistoryPage
                    urls={urls}
                    routes={routes}
                    filters={filters}
                    initialStatus={initialStatus}
                />
            ) : null}

            {page === 'payslips' ? (
                <UtilityPage page="payslips" urls={urls} routes={routes} filters={filters} />
            ) : null}

            {page === 'reports' ? (
                <UtilityPage page="reports" urls={urls} routes={routes} filters={filters} />
            ) : null}

            {page === 'settings' ? (
                <UtilityPage page="settings" urls={urls} routes={routes} filters={filters} />
            ) : null}
        </div>
    );
}

export function mountPayrollWorkspaceApp() {
    const rootElement = document.getElementById('payroll-workspace-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        return;
    }

    createRoot(rootElement).render(<PayrollWorkspaceApp payload={payload} />);
}
