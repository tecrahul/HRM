import React, { useEffect, useMemo, useState } from 'react';
import { payrollApi } from '../api';
import { InfoCard, SectionHeader, StatusBadge, formatCount, formatDateTime, formatMoney, useDebouncedValue } from '../shared/ui';

const toFilters = (filters) => ({
    branch_id: filters.branchId || '',
    department_id: filters.departmentId || '',
    employee_id: filters.employeeId || '',
    payroll_month: filters.payrollMonth || '',
});

export function DashboardPage({ urls, routes, filters }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [summary, setSummary] = useState(null);
    const [statusCard, setStatusCard] = useState(null);
    const [alerts, setAlerts] = useState([]);
    const [activity, setActivity] = useState([]);

    const query = useMemo(() => toFilters(filters), [filters]);
    const debouncedQuery = useDebouncedValue(query, 300);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        setError('');

        Promise.all([
            payrollApi.getDashboardSummary(urls.dashboardSummary, debouncedQuery),
            payrollApi.getDashboardAlerts(urls.dashboardAlerts, debouncedQuery),
            payrollApi.getDashboardActivity(urls.dashboardActivity, debouncedQuery),
        ])
            .then(([summaryData, alertsData, activityData]) => {
                if (!mounted) {
                    return;
                }

                setSummary(summaryData?.summary ?? null);
                setStatusCard(summaryData?.currentMonthStatus ?? null);
                setAlerts(Array.isArray(alertsData?.alerts) ? alertsData.alerts : []);
                setActivity(Array.isArray(activityData?.activity) ? activityData.activity : []);
            })
            .catch(() => {
                if (!mounted) {
                    return;
                }

                setSummary(null);
                setStatusCard(null);
                setAlerts([]);
                setActivity([]);
                setError('Unable to load payroll dashboard data.');
            })
            .finally(() => {
                if (mounted) {
                    setLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [debouncedQuery, urls.dashboardActivity, urls.dashboardAlerts, urls.dashboardSummary]);

    return (
        <div className="space-y-5">
            <section className="ui-section">
                <SectionHeader
                    title="Payroll Summary"
                    subtitle="Enterprise payroll KPIs for the selected filters."
                />

                {error ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}

                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <InfoCard
                        label="Total Employees"
                        value={loading ? '...' : formatCount(summary?.totalEmployees ?? 0)}
                        icon="users"
                    />
                    <InfoCard
                        label="Missing Salary Structure"
                        value={loading ? '...' : formatCount(summary?.missingSalaryStructure ?? 0)}
                        tone="warning"
                        icon="warning"
                    />
                    <InfoCard
                        label="Current Month Status"
                        value={loading ? '...' : String(summary?.currentMonthPayrollStatus ?? 'Draft')}
                        tone="info"
                        icon="status"
                    />
                    <InfoCard
                        label="Pending Approvals"
                        value={loading ? '...' : formatCount(summary?.pendingApprovals ?? 0)}
                        tone="warning"
                        icon="clock"
                    />
                    <InfoCard
                        label="Total Net Payroll"
                        value={loading ? '...' : formatMoney(summary?.totalNetPayroll ?? 0)}
                        tone="success"
                        icon="money"
                    />
                    <InfoCard
                        label="Last Processed Month"
                        value={loading ? '...' : String(summary?.lastProcessedMonth ?? 'N/A')}
                        icon="calendar"
                    />
                </div>
            </section>

            <section className="ui-section">
                <SectionHeader
                    title="Current Month Status"
                    subtitle="Continue payroll execution from the next required stage."
                />
                <div className="mt-4 rounded-2xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Payroll Month</p>
                            <p className="mt-1 text-xl font-extrabold">{statusCard?.monthLabel ?? 'N/A'}</p>
                        </div>
                        <StatusBadge status={statusCard?.status ?? 'draft'} />
                    </div>
                    <div className="mt-4">
                        <div className="h-2.5 w-full rounded-full" style={{ background: 'rgb(148 163 184 / 0.2)' }}>
                            <span
                                className="block h-full rounded-full"
                                style={{ width: `${Number(statusCard?.progressPercent ?? 0)}%`, background: 'linear-gradient(110deg, #2563eb, #10b981)' }}
                            />
                        </div>
                        <p className="mt-2 text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                            {formatCount(statusCard?.paidCount ?? 0)} paid of {formatCount(statusCard?.generatedCount ?? 0)} generated
                        </p>
                    </div>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <a href={routes.processing} className="ui-btn ui-btn-primary">Continue Processing</a>
                        <a href={routes.history} className="ui-btn ui-btn-ghost">View History</a>
                    </div>
                </div>
            </section>

            <section className="ui-section">
                <SectionHeader title="Alerts" subtitle="Click an alert to navigate with context filters." />
                <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {alerts.length === 0 && !loading ? (
                        <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-muted)' }}>No Issues This Month</p>
                    ) : null}
                    {alerts.map((alert) => (
                        <button
                            key={alert.key}
                            type="button"
                            onClick={() => {
                                if (alert.target) {
                                    window.location.assign(alert.target);
                                }
                            }}
                            className="rounded-2xl border p-4 text-left"
                            style={{
                                borderColor: alert.tone === 'danger' ? 'rgb(239 68 68 / 0.35)' : alert.tone === 'warning' ? 'rgb(245 158 11 / 0.35)' : 'rgb(59 130 246 / 0.35)',
                                background: 'var(--hr-surface-strong)',
                            }}
                        >
                            <p className="text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>{alert.label}</p>
                            <p className="mt-2 text-2xl font-extrabold">{formatCount(alert.count ?? 0)}</p>
                        </button>
                    ))}
                </div>
            </section>

            <section className="ui-section">
                <SectionHeader title="Recent Activity" subtitle="Payroll audit trail for generate, approve, and paid actions." />
                <div className="mt-4 space-y-2">
                    {activity.length === 0 && !loading ? (
                        <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>No recent payroll activity.</p>
                    ) : null}
                    {activity.map((entry) => (
                        <article
                            key={`activity-${entry.id}`}
                            className="rounded-xl border px-3 py-2"
                            style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}
                        >
                            <p className="text-sm font-semibold">{entry.actionLabel}</p>
                            <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                                {entry.performedBy} • {formatDateTime(entry.performedAt)}
                            </p>
                        </article>
                    ))}
                </div>
            </section>
        </div>
    );
}
