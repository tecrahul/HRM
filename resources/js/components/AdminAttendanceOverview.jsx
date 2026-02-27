import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { fetchAdminAttendanceOverview } from '../services/adminAttendanceApi';
import { buildDashboardSummaryQuery } from '../services/adminDashboardApi';
import { AnalyticsDonutChart } from './common/charts/AnalyticsDonutChart';
import { ChartLegend } from './common/charts/ChartLegend';
import Icon from './shared/Icon';

const numberFormatter = new Intl.NumberFormat();

const toCount = (value) => numberFormatter.format(Number(value ?? 0));

const statusStyle = {
    present: { label: 'Present', color: '#16a34a' },
    absent: { label: 'Absent', color: '#dc2626' },
    late: { label: 'Late', color: '#f97316' },
    onLeave: { label: 'On Leave', color: '#8b5cf6' },
    workFromHome: { label: 'Work From Home', color: '#0284c7' },
    notMarked: { label: 'Not Marked', color: '#64748b' },
};

function StatusCard({ label, value, color }) {
    return (
        <article
            className="rounded-xl border p-3"
            style={{ borderColor: 'var(--hr-line)', backgroundColor: 'var(--hr-surface-strong)' }}
        >
            <div className="flex items-center justify-between gap-2">
                <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                    {label}
                </p>
                <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: color }} />
            </div>
            <p className="mt-2 text-2xl font-extrabold" style={{ color: 'var(--hr-text-main)' }}>
                {toCount(value)}
            </p>
        </article>
    );
}

// Replaced legacy SVG pie with unified Recharts donut chart

function DepartmentBars({ departments }) {
    return (
        <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                Attendance % by Department
            </p>
            {departments.map((department) => (
                <div key={department.name}>
                    <div className="mb-1 flex items-center justify-between text-xs">
                        <p className="font-semibold" style={{ color: 'var(--hr-text-main)' }}>
                            {department.name}
                        </p>
                        <p className="font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                            {department.percentage.toFixed(1)}% ({toCount(department.present)}/{toCount(department.total)})
                        </p>
                    </div>
                    <div className="h-2.5 w-full overflow-hidden rounded-full" style={{ backgroundColor: 'rgb(148 163 184 / 0.2)' }}>
                        <div
                            className="h-full rounded-full"
                            style={{
                                width: `${Math.min(100, Math.max(0, department.percentage))}%`,
                                background: 'linear-gradient(120deg, #0284c7, #14b8a6)',
                            }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}

function AttendanceSkeleton() {
    return (
        <div className="animate-pulse space-y-4">
            <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                {Array.from({ length: 6 }).map((_, index) => (
                    <div key={index} className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)' }}>
                        <div className="h-3 w-24 rounded bg-slate-300/35" />
                        <div className="mt-3 h-7 w-16 rounded bg-slate-300/35" />
                    </div>
                ))}
            </div>
            <div className="h-56 rounded-xl border bg-slate-300/20" style={{ borderColor: 'var(--hr-line)' }} />
        </div>
    );
}

function AdminAttendanceOverview({
    absentUrl,
    endpointUrl,
    initialBranchId = '',
    initialDepartmentId = '',
}) {
    const [payload, setPayload] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [dayMode, setDayMode] = useState('today'); // today | yesterday | custom
    const [selectedDate, setSelectedDate] = useState(() => {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    });

    const queryParams = useMemo(
        () => buildDashboardSummaryQuery({
            branchId: initialBranchId,
            departmentId: initialDepartmentId,
        }),
        [initialBranchId, initialDepartmentId],
    );

    const absentEmployeesUrl = useMemo(() => {
        const search = new URLSearchParams(queryParams);
        search.set('attendance_date', selectedDate);
        const base = absentUrl.split('?')[0];
        const qp = search.toString();
        return qp ? `${base}?${qp}` : base;
    }, [absentUrl, queryParams, selectedDate]);

    const load = useCallback(async () => {
        setLoading(true);
        setError('');
        try {
            const abortController = new AbortController();
            const response = await fetchAdminAttendanceOverview(
                endpointUrl,
                { ...queryParams, date: selectedDate },
                abortController.signal,
            );
            setPayload(response);
        } catch (_error) {
            setError('Unable to load attendance overview right now.');
        } finally {
            setLoading(false);
        }
    }, [endpointUrl, queryParams, selectedDate]);

    useEffect(() => {
        load();
    }, [load]);

    const entries = useMemo(() => {
        if (!payload?.totals) {
            return [];
        }

        return Object.entries(statusStyle).map(([key, config]) => ({
            key,
            label: config.label,
            value: Number(payload.totals[key] ?? 0),
            color: config.color,
        }));
    }, [payload]);

    const [hidden, setHidden] = useState(new Set());
    const legendItems = useMemo(() => {
        const sum = entries.reduce((acc, e) => acc + Number(e.value || 0), 0);
        return entries.map((e) => ({
            key: e.key,
            label: e.label,
            color: e.color,
            value: toCount(e.value),
            pct: sum > 0 ? ((Number(e.value || 0) / sum) * 100).toFixed(1) : '0.0',
            visible: !hidden.has(e.key),
        }));
    }, [entries, hidden]);
    const displayed = useMemo(() => entries.filter((e) => !hidden.has(e.key)), [entries, hidden]);

    return (
        <section className="rounded-xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
            <div className="ui-section-head">
                <div>
                    <h3 className="ui-section-title">Attendance Overview</h3>
                    <p className="ui-section-subtitle">Status mix and department-wise attendance performance.</p>
                </div>
                <div className="flex items-center justify-end gap-2">
                    <select
                        className="ui-select"
                        value={dayMode}
                        onChange={(e) => {
                            const mode = e.target.value;
                            setDayMode(mode);
                            if (mode === 'today') {
                                const d = new Date();
                                setSelectedDate(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`);
                            } else if (mode === 'yesterday') {
                                const d = new Date();
                                d.setDate(d.getDate() - 1);
                                setSelectedDate(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`);
                            }
                        }}
                        aria-label="Select day"
                    >
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="custom">Custom Date</option>
                    </select>
                    {dayMode === 'custom' && (
                        <input
                            type="date"
                            className="ui-input"
                            value={selectedDate}
                            onChange={(e) => setSelectedDate(e.target.value)}
                            aria-label="Choose date"
                        />
                    )}
                    <a href={absentEmployeesUrl} className="ui-btn ui-btn-primary">View Absent Employees</a>
                    <button
                        type="button"
                        className="ui-btn ui-btn-ghost"
                        onClick={load}
                        disabled={loading}
                        aria-label="Refresh"
                        title="Refresh"
                    >
                        <Icon name="refresh" className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>
                </div>
            </div>

            {error && (
                <div className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">
                    {error}
                </div>
            )}

            {loading && !payload ? (
                <div className="mt-4">
                    <AttendanceSkeleton />
                </div>
            ) : (
                <>
                    <div className="mt-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                        {entries.map((entry) => (
                            <StatusCard key={entry.key} label={entry.label} value={entry.value} color={entry.color} />
                        ))}
                    </div>

                    <div className="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-5">
                        <div className="rounded-xl border p-4" style={{ borderColor: 'var(--hr-line)' }}>
                            <p className="mb-3 text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Status Distribution (Pie)
                            </p>
                            <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_240px] items-start">
                                <AnalyticsDonutChart data={displayed} height={240} showCenterTotal tooltipTitle="Attendance" />
                                <ChartLegend
                                    items={legendItems}
                                    onToggle={(key) => setHidden((prev) => {
                                        const next = new Set(prev);
                                        if (next.has(key)) next.delete(key); else next.add(key);
                                        return next;
                                    })}
                                />
                            </div>
                        </div>
                        <div className="rounded-xl border p-4" style={{ borderColor: 'var(--hr-line)' }}>
                            <DepartmentBars departments={payload?.departments ?? []} />
                        </div>
                    </div>
                </>
            )}
        </section>
    );
}

export function mountAdminAttendanceOverview() {
    const rootElement = document.getElementById('admin-dashboard-attendance-overview-root');
    if (!rootElement) {
        return;
    }

    const absentUrl = rootElement.dataset.absentUrl ?? '#';
    const endpointUrl = rootElement.dataset.endpoint ?? '';
    const initialBranchId = rootElement.dataset.branchId ?? '';
    const initialDepartmentId = rootElement.dataset.departmentId ?? '';

    createRoot(rootElement).render(
        <AdminAttendanceOverview
            absentUrl={absentUrl}
            endpointUrl={endpointUrl}
            initialBranchId={initialBranchId}
            initialDepartmentId={initialDepartmentId}
        />,
    );
}
