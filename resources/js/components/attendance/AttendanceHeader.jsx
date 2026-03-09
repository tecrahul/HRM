import React from 'react';
import { PermissionGuard } from './PermissionGuard';

export function AttendanceHeader({
    canCreate,
    canApprove,
    pendingApprovals = 0,
    punch = {},
    punchInUrl = '',
    punchOutUrl = '',
    onOpenForm,
    submitting = false,
}) {
    const showPunchIn = Boolean(punch?.canPunchSelf) && punch?.nextAction === 'check_in';
    const showPunchOut = Boolean(punch?.canPunchSelf) && punch?.nextAction === 'check_out';
    const showPunchDone = Boolean(punch?.canPunchSelf) && punch?.nextAction === 'none';

    return (
        <section className="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h2 className="text-xl font-extrabold">Attendance</h2>
                <p className="text-sm mt-2" style={{ color: 'var(--hr-text-muted)' }}>
                    Role-aware attendance operations with approval and lock controls.
                </p>
            </div>

            <div className="flex items-center gap-4">
                <PermissionGuard allowed={canApprove}>
                    <span className="rounded-full px-3 py-1 text-xs font-semibold border" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                        Pending Approvals: {pendingApprovals}
                    </span>
                </PermissionGuard>

                {/* Admin/HR management action: open full create form */}
                <PermissionGuard allowed={canCreate}>
                    <button
                        type="button"
                        className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                        onClick={onOpenForm}
                        disabled={submitting}
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                        + Mark Attendance
                    </button>
                </PermissionGuard>

                {/* Punch In/Out links — redirect to dedicated punch pages */}
                {showPunchIn && punchInUrl ? (
                    <a
                        href={punchInUrl}
                        className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        style={{ background: 'linear-gradient(120deg, #15803d, #22c55e)' }}
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 2" />
                        </svg>
                        Punch In
                    </a>
                ) : null}

                {showPunchOut && punchOutUrl ? (
                    <a
                        href={punchOutUrl}
                        className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        style={{ background: 'linear-gradient(120deg, #b45309, #f59e0b)' }}
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 2" />
                        </svg>
                        Punch Out
                    </a>
                ) : null}

                {showPunchDone ? (
                    <span className="rounded-full border px-3 py-1 text-xs font-semibold" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                        Punch completed for today
                    </span>
                ) : null}
            </div>
        </section>
    );
}
