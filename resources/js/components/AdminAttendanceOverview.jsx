import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { fetchAdminAttendanceOverview } from '../services/adminAttendanceApi';

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

function PieChart({ entries }) {
    const radius = 54;
    const strokeWidth = 20;
    const circumference = 2 * Math.PI * radius;
    const total = entries.reduce((sum, item) => sum + item.value, 0);

    let progressOffset = 0;

    return (
        <div className="flex items-center gap-5">
            <svg viewBox="0 0 140 140" className="h-44 w-44 shrink-0" role="img" aria-label="Attendance distribution chart">
                <circle
                    cx="70"
                    cy="70"
                    r={radius}
                    fill="none"
                    stroke="rgb(148 163 184 / 0.24)"
                    strokeWidth={strokeWidth}
                />
                {entries.map((entry) => {
                    const ratio = total > 0 ? entry.value / total : 0;
                    const arcLength = ratio * circumference;
                    const dashArray = `${arcLength} ${circumference - arcLength}`;
                    const dashOffset = -progressOffset;

                    progressOffset += arcLength;

                    return (
                        <circle
                            key={entry.key}
                            cx="70"
                            cy="70"
                            r={radius}
                            fill="none"
                            stroke={entry.color}
                            strokeWidth={strokeWidth}
                            strokeDasharray={dashArray}
                            strokeDashoffset={dashOffset}
                            strokeLinecap="butt"
                            transform="rotate(-90 70 70)"
                        />
                    );
                })}
                <text x="70" y="66" textAnchor="middle" className="fill-current text-[9px] font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                    Total
                </text>
                <text x="70" y="86" textAnchor="middle" className="fill-current text-lg font-extrabold" style={{ color: 'var(--hr-text-main)' }}>
                    {toCount(total)}
                </text>
            </svg>

            <div className="space-y-2 text-xs">
                {entries.map((entry) => (
                    <div key={entry.key} className="flex items-center justify-between gap-3 min-w-[170px]">
                        <span className="inline-flex items-center gap-2">
                            <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: entry.color }} />
                            <span style={{ color: 'var(--hr-text-main)' }}>{entry.label}</span>
                        </span>
                        <span className="font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                            {toCount(entry.value)}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

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

function AdminAttendanceOverview({ absentUrl, endpointUrl }) {
    const [payload, setPayload] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError('');
        try {
            const abortController = new AbortController();
            const response = await fetchAdminAttendanceOverview(endpointUrl, abortController.signal);
            setPayload(response);
        } catch (_error) {
            setError('Unable to load attendance overview right now.');
        } finally {
            setLoading(false);
        }
    }, [endpointUrl]);

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
                    <h3 className="ui-section-title">Attendance Overview</h3>
                    <p className="ui-section-subtitle">Today&apos;s status mix and department-wise attendance performance.</p>
                </div>
                <div className="flex items-center gap-2">
                    <a href={absentUrl} className="ui-btn ui-btn-primary">View Absent Employees</a>
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
                            <PieChart entries={entries} />
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
    createRoot(rootElement).render(<AdminAttendanceOverview absentUrl={absentUrl} endpointUrl={endpointUrl} />);
}
