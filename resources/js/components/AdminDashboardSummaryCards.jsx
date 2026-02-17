import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { fetchAdminDashboardSummary } from '../services/adminDashboardApi';

const numberFormatter = new Intl.NumberFormat();

const toCount = (value) => numberFormatter.format(Number(value ?? 0));
const toPercent = (value) => `${Number(value ?? 0).toFixed(1)}%`;

const toneClass = {
    emerald: 'from-emerald-500/15 to-emerald-500/5 border-emerald-500/25',
    sky: 'from-sky-500/15 to-sky-500/5 border-sky-500/25',
    amber: 'from-amber-500/15 to-amber-500/5 border-amber-500/25',
    violet: 'from-violet-500/15 to-violet-500/5 border-violet-500/25',
    slate: 'from-slate-500/15 to-slate-500/5 border-slate-500/25',
};

function SummaryCard({ title, value, subtitle, tone = 'slate' }) {
    return (
        <article
            className={`rounded-2xl border bg-gradient-to-br p-4 ${toneClass[tone] ?? toneClass.slate}`}
            style={{ backgroundColor: 'var(--hr-surface-strong)', borderColor: 'var(--hr-line)' }}
        >
            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                {title}
            </p>
            <p className="mt-2 text-2xl font-extrabold leading-tight" style={{ color: 'var(--hr-text-main)' }}>
                {value}
            </p>
            <p className="mt-2 text-xs font-medium" style={{ color: 'var(--hr-text-muted)' }}>
                {subtitle}
            </p>
        </article>
    );
}

function SummarySkeleton() {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 animate-pulse">
            {Array.from({ length: 7 }).map((_, index) => (
                <div
                    key={index}
                    className="rounded-2xl border p-4"
                    style={{ backgroundColor: 'var(--hr-surface-strong)', borderColor: 'var(--hr-line)' }}
                >
                    <div className="h-3 w-32 rounded bg-slate-300/40" />
                    <div className="mt-3 h-8 w-24 rounded bg-slate-300/40" />
                    <div className="mt-3 h-3 w-40 rounded bg-slate-300/30" />
                </div>
            ))}
        </div>
    );
}

function AdminDashboardSummaryCards({ endpointUrl }) {
    const [summary, setSummary] = useState(null);
    const [generatedAt, setGeneratedAt] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const loadSummary = useCallback(async () => {
        setLoading(true);
        setError('');

        const abortController = new AbortController();
        try {
            const payload = await fetchAdminDashboardSummary(endpointUrl, abortController.signal);
            setSummary(payload?.summary ?? null);
            setGeneratedAt(typeof payload?.generatedAt === 'string' ? payload.generatedAt : '');
        } catch (requestError) {
            if (requestError?.name !== 'CanceledError') {
                setError('Unable to load dashboard summary. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    }, [endpointUrl]);

    useEffect(() => {
        loadSummary();
    }, [loadSummary]);

    const cards = useMemo(() => {
        if (!summary) {
            return [];
        }

        return [
            {
                key: 'active-employees',
                title: 'Total Active Employees',
                value: toCount(summary.totalActiveEmployees),
                subtitle: 'Employees with active status',
                tone: 'emerald',
            },
            {
                key: 'present-today',
                title: 'Present Today',
                value: toCount(summary.presentToday?.count),
                subtitle: `${toPercent(summary.presentToday?.percentage)} of active employees`,
                tone: 'sky',
            },
            {
                key: 'currently-on-leave',
                title: 'Employees On Leave',
                value: toCount(summary.employeesOnLeave),
                subtitle: 'Approved leave overlapping today',
                tone: 'amber',
            },
            {
                key: 'pending-approvals',
                title: 'Pending Approvals',
                value: toCount(summary.pendingApprovals?.total),
                subtitle: `Leave ${toCount(summary.pendingApprovals?.leave)} + Other ${toCount(summary.pendingApprovals?.other)}`,
                tone: 'violet',
            },
            {
                key: 'payroll-status',
                title: 'Payroll Status',
                value: `${toCount(summary.payrollStatus?.completed)} / ${toCount(summary.payrollStatus?.pending)}`,
                subtitle: `${summary.payrollStatus?.state ?? 'Pending'} (Completed / Pending)`,
                tone: 'slate',
            },
            {
                key: 'new-joiners',
                title: 'New Joiners This Month',
                value: toCount(summary.newJoinersThisMonth),
                subtitle: 'Based on joined-on date',
                tone: 'emerald',
            },
            {
                key: 'exits',
                title: 'Exits This Month',
                value: toCount(summary.exitsThisMonth),
                subtitle: 'Inactive or suspended updates',
                tone: 'amber',
            },
        ];
    }, [summary]);

    if (loading && !summary) {
        return <SummarySkeleton />;
    }

    if (error && !summary) {
        return (
            <div
                className="rounded-2xl border p-5"
                style={{ backgroundColor: 'var(--hr-surface-strong)', borderColor: 'var(--hr-line)' }}
            >
                <p className="text-sm font-semibold text-red-600">{error}</p>
                <button
                    type="button"
                    onClick={loadSummary}
                    className="mt-3 rounded-xl border px-3 py-1.5 text-sm font-semibold"
                    style={{ borderColor: 'var(--hr-line)' }}
                >
                    Retry
                </button>
            </div>
        );
    }

    return (
        <section>
            <div className="mb-3 flex items-center justify-between gap-3">
                <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                    Summary cards are loaded from API.
                </p>
                <div className="flex items-center gap-3">
                    {generatedAt !== '' && (
                        <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                            Updated {new Date(generatedAt).toLocaleString()}
                        </p>
                    )}
                    <button
                        type="button"
                        onClick={loadSummary}
                        disabled={loading}
                        className="rounded-xl border px-3 py-1.5 text-xs font-semibold disabled:opacity-60"
                        style={{ borderColor: 'var(--hr-line)' }}
                    >
                        {loading ? 'Refreshing...' : 'Refresh'}
                    </button>
                </div>
            </div>

            {error && (
                <p className="mb-3 text-xs font-semibold text-red-600">{error}</p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map((card) => (
                    <SummaryCard
                        key={card.key}
                        title={card.title}
                        value={card.value}
                        subtitle={card.subtitle}
                        tone={card.tone}
                    />
                ))}
            </div>
        </section>
    );
}

export function mountAdminDashboardSummaryCards() {
    const rootElement = document.getElementById('admin-dashboard-summary-cards-root');
    if (!rootElement) {
        return;
    }

    const endpointUrl = rootElement.dataset.summaryEndpoint ?? '';
    createRoot(rootElement).render(<AdminDashboardSummaryCards endpointUrl={endpointUrl} />);
}

