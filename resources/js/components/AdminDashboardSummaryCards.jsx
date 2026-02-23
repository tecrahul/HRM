import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
    buildDashboardSummaryQuery,
    fetchAdminDashboardSummary,
} from '../services/adminDashboardApi';
import { QuickInfoCard } from './common/QuickInfoCard';
import { QuickInfoGrid } from './common/QuickInfoGrid';

const numberFormatter = new Intl.NumberFormat();

const toCount = (value) => numberFormatter.format(Number(value ?? 0));
const toPercent = (value) => `${Number(value ?? 0).toFixed(1)}%`;

function DashboardIcon({ icon }) {
    const iconMap = {
        users: (
            <path d="M17 21v-2.2a3.8 3.8 0 0 0-3-3.7M7 21v-2.2a3.8 3.8 0 0 1 3-3.7M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M20 8v6M23 11h-6" />
        ),
        attendance: (
            <>
                <circle cx="12" cy="12" r="9" />
                <path d="m8.5 12 2.3 2.3 4.7-4.7" />
            </>
        ),
        leave: (
            <>
                <path d="M8 2v3M16 2v3" />
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <path d="M3 10h18" />
                <path d="m9.5 14 1.8 1.8 3.2-3.2" />
            </>
        ),
        approvals: (
            <>
                <path d="M12 3 4 7v5c0 5.2 3.2 8 8 9 4.8-1 8-3.8 8-9V7l-8-4z" />
                <path d="M12 9v5" />
                <path d="M12 17h.01" />
            </>
        ),
        payroll: (
            <>
                <rect x="2" y="5" width="20" height="14" rx="2" />
                <path d="M2 10h20" />
                <path d="M12 14h.01" />
            </>
        ),
        joiners: (
            <>
                <circle cx="9" cy="8" r="3" />
                <path d="M3 21a6 6 0 0 1 12 0" />
                <path d="M19 8v6M22 11h-6" />
            </>
        ),
        exits: (
            <>
                <circle cx="9" cy="8" r="3" />
                <path d="M3 21a6 6 0 0 1 12 0" />
                <path d="m16 11 5 5-5 5" />
                <path d="M21 16h-7" />
            </>
        ),
    };

    return (
        <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {iconMap[icon] ?? iconMap.users}
        </svg>
    );
}

function SummarySkeleton() {
    return (
        <QuickInfoGrid>
            {Array.from({ length: 7 }).map((_, index) => (
                <div
                    key={index}
                    className="rounded-2xl border p-4 h-full animate-pulse"
                    style={{ backgroundColor: 'var(--hr-surface-strong)', borderColor: 'var(--hr-line)' }}
                >
                    <div className="h-3 w-32 rounded bg-slate-300/40" />
                    <div className="mt-3 h-8 w-24 rounded bg-slate-300/40" />
                    <div className="mt-3 h-3 w-40 rounded bg-slate-300/30" />
                </div>
            ))}
        </QuickInfoGrid>
    );
}

function AdminDashboardSummaryCards({ endpointUrl, initialBranchId = '', initialDepartmentId = '' }) {
    const [summary, setSummary] = useState(null);
    const [generatedAt, setGeneratedAt] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const loadSummary = useCallback(async () => {
        setLoading(true);
        setError('');

        const abortController = new AbortController();
        try {
            const payload = await fetchAdminDashboardSummary(
                endpointUrl,
                buildDashboardSummaryQuery({
                    branchId: initialBranchId,
                    departmentId: initialDepartmentId,
                }),
                abortController.signal,
            );
            setSummary(payload?.summary ?? null);
            setGeneratedAt(typeof payload?.generatedAt === 'string' ? payload.generatedAt : '');
        } catch (requestError) {
            if (requestError?.name !== 'CanceledError') {
                setError('Unable to load dashboard summary. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    }, [endpointUrl, initialBranchId, initialDepartmentId]);

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
                secondaryInfo: 'Employees with active status',
                color: 'success',
                icon: 'users',
            },
            {
                key: 'present-today',
                title: 'Present Today',
                value: toCount(summary.presentToday?.count),
                secondaryInfo: `${toPercent(summary.presentToday?.percentage)} of active employees`,
                color: 'primary',
                icon: 'attendance',
            },
            {
                key: 'currently-on-leave',
                title: 'Employees On Leave',
                value: toCount(summary.employeesOnLeave),
                secondaryInfo: 'Approved leave overlapping today',
                color: 'warning',
                icon: 'leave',
            },
            {
                key: 'pending-approvals',
                title: 'Pending Approvals',
                value: toCount(summary.pendingApprovals?.total),
                secondaryInfo: `Leave ${toCount(summary.pendingApprovals?.leave)} + Other ${toCount(summary.pendingApprovals?.other)}`,
                color: 'warning',
                icon: 'approvals',
            },
            {
                key: 'payroll-status',
                title: 'Payroll Status',
                value: `${toCount(summary.payrollStatus?.completed)} / ${toCount(summary.payrollStatus?.pending)}`,
                secondaryInfo: `${summary.payrollStatus?.state ?? 'Pending'} (Completed / Pending)`,
                color: 'neutral',
                icon: 'payroll',
            },
            {
                key: 'new-joiners',
                title: 'New Joiners This Month',
                value: toCount(summary.newJoinersThisMonth),
                secondaryInfo: 'Based on joined-on date',
                color: 'success',
                icon: 'joiners',
            },
            {
                key: 'exits',
                title: 'Exits This Month',
                value: toCount(summary.exitsThisMonth),
                secondaryInfo: 'Inactive or suspended updates',
                color: 'error',
                icon: 'exits',
            },
        ];
    }, [summary]);

    return (
        <section>
            <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 className="text-lg font-extrabold" style={{ color: 'var(--hr-text-main)' }}>Summary</h3>
                    <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                        Snapshot of workforce, attendance, leave, approvals, and payroll.
                    </p>
                </div>
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

            {error ? (
                <p className="mb-3 text-xs font-semibold text-red-600">{error}</p>
            ) : null}

            {loading && !summary ? (
                <SummarySkeleton />
            ) : (
                <QuickInfoGrid>
                    {cards.map((card) => (
                        <QuickInfoCard
                            key={card.key}
                            title={card.title}
                            value={card.value}
                            secondaryInfo={card.secondaryInfo}
                            color={card.color}
                            icon={<DashboardIcon icon={card.icon} />}
                        />
                    ))}
                </QuickInfoGrid>
            )}
        </section>
    );
}

export function mountAdminDashboardSummaryCards() {
    const rootElement = document.getElementById('admin-dashboard-summary-cards-root');
    if (!rootElement) {
        return;
    }

    const endpointUrl = rootElement.dataset.summaryEndpoint ?? '';
    const initialBranchId = rootElement.dataset.branchId ?? '';
    const initialDepartmentId = rootElement.dataset.departmentId ?? '';

    createRoot(rootElement).render(
        <AdminDashboardSummaryCards
            endpointUrl={endpointUrl}
            initialBranchId={initialBranchId}
            initialDepartmentId={initialDepartmentId}
        />,
    );
}
