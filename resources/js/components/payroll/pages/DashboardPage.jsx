import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip,
    ResponsiveContainer, PieChart, Pie, Cell,
} from 'recharts';
import { payrollApi } from '../api';
import { InfoCard, SectionHeader, StatusBadge, formatCount, formatDateTime, formatMoney, useDebouncedValue } from '../shared/ui';
import { QuickInfoGrid } from '../../common/QuickInfoGrid';

const PERIOD_OPTIONS = [
    { value: 'current_month',  label: 'Current Month' },
    { value: 'last_3_months',  label: 'Last 3 Months' },
    { value: 'last_6_months',  label: 'Last 6 Months' },
    { value: 'last_12_months', label: 'Last 12 Months' },
    { value: 'financial_year', label: 'Financial Year' },
];

const compactMoney = (value) => {
    const n = Number(value ?? 0);
    if (n >= 1e7) return `${(n / 1e7).toFixed(1)}Cr`;
    if (n >= 1e5) return `${(n / 1e5).toFixed(1)}L`;
    if (n >= 1e3) return `${Math.round(n / 1e3)}K`;
    return String(Math.round(n));
};

const toFilters = (filters) => ({
    branch: filters.branch || '',
    department: filters.department || '',
    employee_id: filters.employeeId || '',
    payroll_month: filters.payrollMonth || '',
});

function BarTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;
    return (
        <div
            className="rounded-xl border px-4 py-3 text-sm shadow-xl"
            style={{ background: 'var(--hr-surface)', borderColor: 'var(--hr-line)', minWidth: 180 }}
        >
            <p className="mb-2 font-extrabold">{label}</p>
            <p style={{ color: 'var(--hr-text-main)' }}>
                Net Payroll: <span className="font-bold">{formatMoney(payload[0]?.value ?? 0)}</span>
            </p>
            <p style={{ color: 'var(--hr-text-muted)' }}>
                Employees: {formatCount(payload[0]?.payload?.employeeCount ?? 0)}
            </p>
        </div>
    );
}

function DistributionLegend({ data }) {
    const total = useMemo(() => data.reduce((sum, d) => sum + (d.value || 0), 0), [data]);
    return (
        <div className="mt-4 space-y-2.5">
            {data.map((entry) => {
                const pct = total > 0 ? ((entry.value / total) * 100).toFixed(1) : '0.0';
                return (
                    <div key={entry.label} className="flex items-center justify-between gap-3 text-sm">
                        <div className="flex items-center gap-2 min-w-0">
                            <span className="h-2.5 w-2.5 flex-none rounded-full" style={{ background: entry.color }} />
                            <span className="truncate font-semibold" style={{ color: 'var(--hr-text-main)' }}>{entry.label}</span>
                        </div>
                        <div className="flex items-center gap-2 flex-none text-right">
                            <span className="font-bold" style={{ color: 'var(--hr-text-main)' }}>{formatMoney(entry.value)}</span>
                            <span className="text-xs font-semibold rounded-full px-1.5 py-0.5" style={{ background: 'var(--hr-surface-strong)', color: 'var(--hr-text-muted)' }}>
                                {pct}%
                            </span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function PieTooltip({ active, payload, distribution }) {
    if (!active || !payload?.length) return null;
    const entry = payload[0];
    const total = distribution.reduce((sum, d) => sum + (d.value || 0), 0);
    const pct = total > 0 ? ((entry.value / total) * 100).toFixed(1) : '0.0';
    return (
        <div
            className="rounded-xl border px-4 py-3 text-sm shadow-xl"
            style={{ background: 'var(--hr-surface)', borderColor: 'var(--hr-line)', minWidth: 180 }}
        >
            <p className="mb-1.5 font-extrabold">{entry.name}</p>
            <p style={{ color: 'var(--hr-text-main)' }}>Amount: <span className="font-bold">{formatMoney(entry.value)}</span></p>
            <p style={{ color: 'var(--hr-text-muted)' }}>{pct}% of total</p>
        </div>
    );
}

function ChartCard({ title, children, actions = null, loading = false }) {
    return (
        <div
            className="rounded-2xl border p-5"
            style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}
        >
            <div className="flex items-center justify-between gap-3 mb-5">
                <h4 className="text-sm font-extrabold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-main)' }}>
                    {title}
                </h4>
                {actions}
            </div>
            {loading ? (
                <div className="flex h-52 items-center justify-center">
                    <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Loading chart data…</p>
                </div>
            ) : children}
        </div>
    );
}

export function DashboardPage({ urls, routes, filters }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [summary, setSummary] = useState(null);
    const [statusCard, setStatusCard] = useState(null);
    const [alerts, setAlerts] = useState([]);
    const [activity, setActivity] = useState([]);

    // Analytics state
    const [trendPeriod, setTrendPeriod] = useState('last_6_months');
    const [trend, setTrend] = useState([]);
    const [distribution, setDistribution] = useState([]);
    const [trendLoading, setTrendLoading] = useState(true);
    const [distributionLoading, setDistributionLoading] = useState(true);

    const query = useMemo(() => toFilters(filters), [filters]);
    const debouncedQuery = useDebouncedValue(query, 300);

    // Summary, alerts, activity
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
                if (!mounted) return;
                setSummary(summaryData?.summary ?? null);
                setStatusCard(summaryData?.currentMonthStatus ?? null);
                setAlerts(Array.isArray(alertsData?.alerts) ? alertsData.alerts : []);
                setActivity(Array.isArray(activityData?.activity) ? activityData.activity : []);
            })
            .catch(() => {
                if (!mounted) return;
                setSummary(null);
                setStatusCard(null);
                setAlerts([]);
                setActivity([]);
                setError('Unable to load payroll dashboard data.');
            })
            .finally(() => {
                if (mounted) setLoading(false);
            });

        return () => { mounted = false; };
    }, [debouncedQuery, urls.dashboardActivity, urls.dashboardAlerts, urls.dashboardSummary]);

    // Trend chart — refetches when period changes
    useEffect(() => {
        if (!urls.dashboardTrend) return;

        let mounted = true;
        setTrendLoading(true);

        payrollApi.getDashboardTrend(urls.dashboardTrend, { period: trendPeriod })
            .then((data) => {
                if (!mounted) return;
                setTrend(Array.isArray(data?.trend) ? data.trend : []);
            })
            .catch(() => { if (mounted) setTrend([]); })
            .finally(() => { if (mounted) setTrendLoading(false); });

        return () => { mounted = false; };
    }, [trendPeriod, urls.dashboardTrend]);

    // Distribution chart — fetches once on mount
    useEffect(() => {
        if (!urls.dashboardDistribution) return;

        let mounted = true;
        setDistributionLoading(true);

        payrollApi.getDashboardDistribution(urls.dashboardDistribution, {})
            .then((data) => {
                if (!mounted) return;
                setDistribution(Array.isArray(data?.distribution) ? data.distribution : []);
            })
            .catch(() => { if (mounted) setDistribution([]); })
            .finally(() => { if (mounted) setDistributionLoading(false); });

        return () => { mounted = false; };
    }, [urls.dashboardDistribution]);

    const renderPieTooltip = useCallback((props) => (
        <PieTooltip {...props} distribution={distribution} />
    ), [distribution]);

    const hasDistributionData = distribution.some(d => d.value > 0);

    const alertByKey = (key) => alerts.find((a) => a.key === key) ?? null;
    const toneStyle = (tone) => ({
        borderColor: tone === 'danger' ? 'rgb(239 68 68 / 0.35)' : tone === 'warning' ? 'rgb(245 158 11 / 0.35)' : 'rgb(59 130 246 / 0.35)',
        background: 'var(--hr-surface-strong)',
    });

    return (
        <div className="space-y-5">
            {/* KPI Summary */}
            <section className="ui-section">
                <SectionHeader
                    title="Payroll Summary"
                    subtitle="Enterprise payroll KPIs for the selected filters."
                />

                {error ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}

                <QuickInfoGrid className="mt-4">
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
                        comparisonValue={summary?.pendingApprovalsChange}
                        comparisonType={Number(summary?.pendingApprovalsChange ?? 0) >= 0 ? 'increase' : 'decrease'}
                        showChart={Array.isArray(summary?.pendingApprovalsTrend)}
                        chartData={summary?.pendingApprovalsTrend ?? []}
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
                </QuickInfoGrid>

                {/* Payroll Errors + Missing Bank Details — clickable alert cards */}
                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                    {[alertByKey('payroll_errors'), alertByKey('missing_bank_details')].map((alert) => {
                        if (!alert) return null;
                        return (
                            <button
                                key={alert.key}
                                type="button"
                                onClick={() => { if (alert.target) window.location.assign(alert.target); }}
                                className="rounded-2xl border p-4 text-left transition-all duration-150 hover:shadow-md"
                                style={toneStyle(alert.tone)}
                            >
                                <p className="text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>{alert.label}</p>
                                <p className="mt-2 text-2xl font-extrabold">{loading ? '...' : formatCount(alert.count ?? 0)}</p>
                                <p className="mt-1 text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Click to view details</p>
                            </button>
                        );
                    })}
                </div>
            </section>

            {/* Payroll Analytics */}
            <section className="ui-section">
                <SectionHeader
                    title="Payroll Analytics"
                    subtitle="Monthly trend and payroll component breakdown."
                />

                <div className="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    {/* Left — Monthly Payroll Trend */}
                    <ChartCard
                        title="Monthly Payroll Trend"
                        loading={trendLoading}
                        actions={
                            <select
                                className="rounded-lg border px-2.5 py-1.5 text-xs font-semibold"
                                style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)', color: 'var(--hr-text-main)' }}
                                value={trendPeriod}
                                onChange={(e) => setTrendPeriod(e.target.value)}
                            >
                                {PERIOD_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        }
                    >
                        {trend.length === 0 ? (
                            <div className="flex h-52 items-center justify-center">
                                <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                                    No payroll data for the selected period.
                                </p>
                            </div>
                        ) : (
                            <ResponsiveContainer width="100%" height={220}>
                                <BarChart data={trend} margin={{ top: 4, right: 4, left: 4, bottom: 4 }} barCategoryGap="30%">
                                    <defs>
                                        <linearGradient id="trendBar" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#2563eb" stopOpacity={0.92} />
                                            <stop offset="100%" stopColor="#10b981" stopOpacity={0.75} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="var(--hr-line)" vertical={false} />
                                    <XAxis
                                        dataKey="monthLabel"
                                        tick={{ fontSize: 11, fill: 'var(--hr-text-muted)', fontWeight: 600 }}
                                        axisLine={false}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        tickFormatter={compactMoney}
                                        tick={{ fontSize: 11, fill: 'var(--hr-text-muted)', fontWeight: 600 }}
                                        axisLine={false}
                                        tickLine={false}
                                        width={52}
                                    />
                                    <Tooltip content={<BarTooltip />} cursor={{ fill: 'var(--hr-line)', opacity: 0.5, radius: 6 }} />
                                    <Bar dataKey="totalPayroll" fill="url(#trendBar)" radius={[6, 6, 0, 0]} maxBarSize={56} />
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </ChartCard>

                    {/* Right — Payroll Distribution */}
                    <ChartCard
                        title="Payroll Distribution"
                        loading={distributionLoading}
                    >
                        {!hasDistributionData ? (
                            <div className="flex h-52 items-center justify-center">
                                <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                                    No component data available yet.
                                </p>
                            </div>
                        ) : (
                            <>
                                <ResponsiveContainer width="100%" height={200}>
                                    <PieChart>
                                        <Pie
                                            data={distribution}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={62}
                                            outerRadius={92}
                                            paddingAngle={3}
                                            dataKey="value"
                                            nameKey="label"
                                            animationBegin={0}
                                            animationDuration={600}
                                        >
                                            {distribution.map((entry) => (
                                                <Cell key={entry.label} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip content={renderPieTooltip} />
                                    </PieChart>
                                </ResponsiveContainer>
                                <DistributionLegend data={distribution} />
                            </>
                        )}
                    </ChartCard>
                </div>
            </section>

            {/* Current Month Status */}
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

            {/* Recent Activity */}
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
