import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { fetchAdminLeaveOverview } from '../services/adminLeaveOverviewApi';
import { buildDashboardSummaryQuery } from '../services/adminDashboardApi';

const numberFormatter = new Intl.NumberFormat();

const toCount = (value) => numberFormatter.format(Number(value ?? 0));

function MetricCard({ title, value, subtitle, href, highlighted = false }) {
    const cardClass = highlighted
        ? 'rounded-xl border p-4 transition hover:-translate-y-0.5'
        : 'rounded-xl border p-4';

    const cardStyle = highlighted
        ? {
            borderColor: 'rgb(245 158 11 / 0.45)',
            background: 'linear-gradient(120deg, rgb(245 158 11 / 0.14), rgb(245 158 11 / 0.04))',
        }
        : { borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' };

    return (
        <a href={href || '#'} className={cardClass} style={cardStyle}>
            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                {title}
            </p>
            <p className="mt-2 text-3xl font-extrabold" style={{ color: 'var(--hr-text-main)' }}>
                {toCount(value)}
            </p>
            <p className="mt-2 text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                {subtitle}
            </p>
            <p className="mt-3 text-xs font-semibold" style={{ color: 'var(--hr-accent)' }}>
                View detailed list
            </p>
        </a>
    );
}

function LeaveTypeBreakdown({ breakdown, actions }) {
    const rows = [
        { key: 'sick', label: 'Sick', color: '#ef4444', value: Number(breakdown?.sick ?? 0), href: actions?.sickLeavesUrl || '#' },
        { key: 'casual', label: 'Casual', color: '#f59e0b', value: Number(breakdown?.casual ?? 0), href: actions?.casualLeavesUrl || '#' },
        { key: 'paid', label: 'Paid', color: '#22c55e', value: Number(breakdown?.paid ?? 0), href: actions?.paidLeavesUrl || '#' },
    ];

    const total = rows.reduce((sum, row) => sum + row.value, 0);

    return (
        <article className="rounded-xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
            <div className="flex items-center justify-between gap-2">
                <h4 className="text-sm font-extrabold">Leave Type Breakdown</h4>
                <span className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Sick / Casual / Paid</span>
            </div>

            <div className="mt-4 space-y-3">
                {rows.map((row) => {
                    const ratio = total > 0 ? (row.value / total) * 100 : 0;

                    return (
                        <a key={row.key} href={row.href} className="block rounded-lg border p-3 transition hover:-translate-y-0.5" style={{ borderColor: 'var(--hr-line)' }}>
                            <div className="mb-1 flex items-center justify-between gap-2">
                                <span className="inline-flex items-center gap-2 text-sm font-semibold" style={{ color: 'var(--hr-text-main)' }}>
                                    <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: row.color }} />
                                    {row.label}
                                </span>
                                <span className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                                    {toCount(row.value)}
                                </span>
                            </div>
                            <div className="h-2 rounded-full overflow-hidden" style={{ background: 'rgb(148 163 184 / 0.2)' }}>
                                <div
                                    className="h-full rounded-full"
                                    style={{ width: `${Math.max(0, Math.min(100, ratio))}%`, backgroundColor: row.color }}
                                />
                            </div>
                        </a>
                    );
                })}
            </div>
        </article>
    );
}

function LeaveOverviewSkeleton() {
    return (
        <div className="animate-pulse space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {Array.from({ length: 3 }).map((_, index) => (
                    <div key={index} className="rounded-xl border p-4" style={{ borderColor: 'var(--hr-line)' }}>
                        <div className="h-3 w-24 rounded bg-slate-300/35" />
                        <div className="mt-3 h-9 w-16 rounded bg-slate-300/35" />
                    </div>
                ))}
            </div>
            <div className="h-44 rounded-xl border bg-slate-300/20" style={{ borderColor: 'var(--hr-line)' }} />
        </div>
    );
}

function AdminLeaveOverview({ endpointUrl, initialBranchId = '', initialDepartmentId = '' }) {
    const [payload, setPayload] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const queryParams = useMemo(
        () => buildDashboardSummaryQuery({
            branchId: initialBranchId,
            departmentId: initialDepartmentId,
        }),
        [initialBranchId, initialDepartmentId],
    );

    const load = useCallback(async () => {
        setLoading(true);
        setError('');

        try {
            const abortController = new AbortController();
            const response = await fetchAdminLeaveOverview(endpointUrl, queryParams, abortController.signal);
            setPayload(response);
        } catch (_error) {
            setError('Unable to load leave overview right now.');
        } finally {
            setLoading(false);
        }
    }, [endpointUrl, queryParams]);

    useEffect(() => {
        load();
    }, [load]);

    const monthLabel = useMemo(() => {
        const start = payload?.period?.monthStart;
        const end = payload?.period?.monthEnd;
        if (!start || !end) {
            return '';
        }

        return `${new Date(start).toLocaleDateString()} - ${new Date(end).toLocaleDateString()}`;
    }, [payload]);

    return (
        <section className="relative rounded-xl border p-4 pt-12" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
            <button
                type="button"
                onClick={load}
                className="ui-btn ui-btn-ghost absolute right-4 top-4"
                disabled={loading}
            >
                {loading ? 'Refreshing...' : 'Refresh'}
            </button>

            <div className="ui-section-head">
                <div>
                    <h3 className="ui-section-title">Leave Overview</h3>
                    <p className="ui-section-subtitle">Pending approvals, approved leaves, leave mix, and who is on leave today.</p>
                </div>
            </div>

            {error && (
                <div className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">
                    {error}
                </div>
            )}

            {loading && !payload ? (
                <div className="mt-4">
                    <LeaveOverviewSkeleton />
                </div>
            ) : (
                <div className="mt-4 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <MetricCard
                            title="Pending Leave Approvals"
                            value={payload?.metrics?.pendingApprovals ?? 0}
                            subtitle="Needs immediate attention"
                            href={payload?.actions?.pendingApprovalsUrl}
                            highlighted
                        />
                        <MetricCard
                            title="Approved Leaves"
                            value={payload?.metrics?.approvedLeaves ?? 0}
                            subtitle={monthLabel ? `This month (${monthLabel})` : 'This month'}
                            href={payload?.actions?.approvedLeavesUrl}
                        />
                        <MetricCard
                            title="Employees On Leave Today"
                            value={payload?.metrics?.employeesOnLeaveToday ?? 0}
                            subtitle="Approved leave active for today"
                            href={payload?.actions?.employeesOnLeaveTodayUrl}
                        />
                    </div>

                    <LeaveTypeBreakdown
                        breakdown={payload?.leaveTypeBreakdown}
                        actions={payload?.actions}
                    />
                </div>
            )}
        </section>
    );
}

export function mountAdminLeaveOverview() {
    const rootElement = document.getElementById('admin-dashboard-leave-overview-root');
    if (!rootElement) {
        return;
    }

    const endpointUrl = rootElement.dataset.endpoint ?? '';
    const initialBranchId = rootElement.dataset.branchId ?? '';
    const initialDepartmentId = rootElement.dataset.departmentId ?? '';

    createRoot(rootElement).render(
        <AdminLeaveOverview
            endpointUrl={endpointUrl}
            initialBranchId={initialBranchId}
            initialDepartmentId={initialDepartmentId}
        />,
    );
}
