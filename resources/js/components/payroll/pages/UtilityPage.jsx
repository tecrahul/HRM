import React, { useEffect, useMemo, useState } from 'react';
import { payrollApi } from '../api';
import { InfoCard, SectionHeader, formatCount, formatMoney, useDebouncedValue } from '../shared/ui';

const PAGE_META = {
    payslips: {
        title: 'Payslips',
        subtitle: 'Generate and distribute payslips after payroll closure.',
    },
    reports: {
        title: 'Payroll Reports',
        subtitle: 'Download payroll summaries and compliance packs.',
    },
    settings: {
        title: 'Payroll Settings',
        subtitle: 'Manage policies, default schedules, and payout configurations.',
    },
};

export function UtilityPage({ page, urls, routes, filters }) {
    const [loading, setLoading] = useState(true);
    const [summary, setSummary] = useState(null);
    const [error, setError] = useState('');

    const query = useMemo(() => ({
        branch_id: filters.branchId || '',
        department_id: filters.departmentId || '',
        employee_id: filters.employeeId || '',
        payroll_month: filters.payrollMonth || '',
    }), [filters.branchId, filters.departmentId, filters.employeeId, filters.payrollMonth]);

    const debouncedQuery = useDebouncedValue(query, 300);

    useEffect(() => {
        setLoading(true);
        setError('');

        payrollApi.getDashboardSummary(urls.dashboardSummary, debouncedQuery)
            .then((data) => {
                setSummary(data?.summary ?? null);
            })
            .catch(() => {
                setSummary(null);
                setError('Unable to load payroll overview data.');
            })
            .finally(() => {
                setLoading(false);
            });
    }, [debouncedQuery, urls.dashboardSummary]);

    const meta = PAGE_META[page] ?? PAGE_META.settings;

    return (
        <div className="space-y-5">
            <section className="ui-section">
                <SectionHeader title={meta.title} subtitle={meta.subtitle} />
                {error ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}
                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <InfoCard label="Total Employees" value={loading ? '...' : formatCount(summary?.totalEmployees ?? 0)} />
                    <InfoCard label="Pending Approvals" value={loading ? '...' : formatCount(summary?.pendingApprovals ?? 0)} tone="warning" />
                    <InfoCard label="Missing Structure" value={loading ? '...' : formatCount(summary?.missingSalaryStructure ?? 0)} tone="warning" />
                    <InfoCard label="Net Payroll" value={loading ? '...' : formatMoney(summary?.totalNetPayroll ?? 0)} tone="success" />
                </div>
            </section>

            <section className="ui-section">
                <SectionHeader title="Quick Navigation" subtitle="Move to the relevant payroll workspace for action." />
                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <a href={routes.dashboard} className="ui-btn ui-btn-ghost">Payroll Dashboard</a>
                    <a href={routes.processing} className="ui-btn ui-btn-ghost">Payroll Processing</a>
                    <a href={routes.history} className="ui-btn ui-btn-ghost">Payroll History</a>
                    <a href={routes.salaryStructures} className="ui-btn ui-btn-ghost">Salary Structures</a>
                    <a href={routes.reports} className="ui-btn ui-btn-ghost">Reports</a>
                    <a href={routes.settings} className="ui-btn ui-btn-ghost">Settings</a>
                </div>
            </section>
        </div>
    );
}
