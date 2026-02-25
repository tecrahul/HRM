import React, { useMemo } from 'react';
import { PermissionGuard } from './PermissionGuard';
import { StatusBadge } from './StatusBadge';

function SkeletonRows({ cols }) {
    return (
        <>
            {Array.from({ length: 6 }).map((_, rowIndex) => (
                <tr key={`attendance-skeleton-${rowIndex}`} className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
                    {Array.from({ length: cols }).map((__, colIndex) => (
                        <td key={`attendance-skeleton-cell-${rowIndex}-${colIndex}`} className="px-2 py-3">
                            <div className="h-4 rounded bg-slate-200 dark:bg-slate-700 animate-pulse w-full max-w-[140px]" />
                        </td>
                    ))}
                </tr>
            ))}
        </>
    );
}

export function AttendanceTable({
    records,
    meta,
    loading,
    submitting,
    capabilities,
    onEdit,
    onDelete,
    onApprove,
    onReject,
    onRequestCorrection,
    onPageChange,
}) {
    const columns = useMemo(() => {
        let count = 8;

        if (capabilities?.showBranchColumn) {
            count += 1;
        }

        if (capabilities?.showDepartmentColumn) {
            count += 1;
        }

        return count + 1;
    }, [capabilities?.showBranchColumn, capabilities?.showDepartmentColumn]);

    const visiblePages = useMemo(() => {
        const current = meta?.currentPage || 1;
        const last = meta?.lastPage || 1;
        const pages = [];

        for (let page = Math.max(1, current - 2); page <= Math.min(last, current + 2); page += 1) {
            pages.push(page);
        }

        return pages;
    }, [meta?.currentPage, meta?.lastPage]);

    return (
        <article className="hrm-modern-surface rounded-2xl p-4">
            <div className="flex items-center justify-between gap-2 flex-wrap">
                <div>
                    <h3 className="text-lg font-extrabold">Attendance Directory</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Review attendance entries, approvals, and correction workflow.
                    </p>
                </div>
                {loading ? (
                    <span className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Refreshing...</span>
                ) : null}
            </div>

            <div className="mt-4 overflow-x-auto">
                <table className="w-full min-w-[1160px] text-sm">
                    <thead>
                        <tr className="border-b text-left" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                            <th className="px-2 py-2.5 font-semibold">Employee</th>
                            <PermissionGuard allowed={capabilities?.showBranchColumn}>
                                <th className="px-2 py-2.5 font-semibold">Branch</th>
                            </PermissionGuard>
                            <PermissionGuard allowed={capabilities?.showDepartmentColumn}>
                                <th className="px-2 py-2.5 font-semibold">Department</th>
                            </PermissionGuard>
                            <th className="px-2 py-2.5 font-semibold">Date</th>
                            <th className="px-2 py-2.5 font-semibold">Status</th>
                            <th className="px-2 py-2.5 font-semibold">Check-in</th>
                            <th className="px-2 py-2.5 font-semibold">Check-out</th>
                            <th className="px-2 py-2.5 font-semibold">Total Hours</th>
                            <th className="px-2 py-2.5 font-semibold">Approval</th>
                            <th className="px-2 py-2.5 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? <SkeletonRows cols={columns} /> : null}

                        {!loading && records.length === 0 ? (
                            <tr>
                                <td colSpan={columns} className="px-2 py-8 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                    No attendance records found for selected filters.
                                </td>
                            </tr>
                        ) : null}

                        {!loading && records.map((record) => (
                            <tr
                                key={record.id}
                                className={`border-b border-l-4 ${record.leftBorderClass || 'border-slate-300'}`}
                                style={{ borderBottomColor: 'var(--hr-line)' }}
                            >
                                <td className="px-2 py-3">
                                    <p className="font-semibold">{record.employee?.name}</p>
                                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{record.employee?.email}</p>
                                </td>
                                <PermissionGuard allowed={capabilities?.showBranchColumn}>
                                    <td className="px-2 py-3">{record.employee?.branch || 'N/A'}</td>
                                </PermissionGuard>
                                <PermissionGuard allowed={capabilities?.showDepartmentColumn}>
                                    <td className="px-2 py-3">{record.employee?.department || 'N/A'}</td>
                                </PermissionGuard>
                                <td className="px-2 py-3">{record.attendanceDateLabel}</td>
                                <td className="px-2 py-3">
                                    <StatusBadge type="attendance" value={record.status} label={record.statusLabel} />
                                </td>
                                <td className="px-2 py-3">{record.checkIn}</td>
                                <td className="px-2 py-3">{record.checkOut}</td>
                                <td className="px-2 py-3">{record.totalHours}</td>
                                <td className="px-2 py-3">
                                    <StatusBadge type="approval" value={record.approvalStatus} label={record.approvalStatusLabel} />
                                    {record.isPendingCorrection && record.correctionReason ? (
                                        <p className="text-[11px] mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                                            Correction: {record.correctionReason}
                                        </p>
                                    ) : null}
                                </td>
                                <td className="px-2 py-3">
                                    <div className="flex items-center justify-end gap-2 flex-wrap">
                                        {record.canApprove ? (
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white"
                                                style={{ background: '#15803d' }}
                                                onClick={() => onApprove(record)}
                                                disabled={submitting}
                                            >
                                                <svg className="h-3.5 w-3.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                    <path d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                Approve
                                            </button>
                                        ) : null}
                                        {record.canReject ? (
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white"
                                                style={{ background: '#b45309' }}
                                                onClick={() => onReject(record)}
                                                disabled={submitting}
                                            >
                                                <svg className="h-3.5 w-3.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                    <path d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Reject
                                            </button>
                                        ) : null}
                                        {record.canEdit ? (
                                            <button
                                                type="button"
                                                className="rounded-lg border px-2.5 py-1.5 text-xs font-semibold"
                                                style={{ borderColor: 'var(--hr-line)' }}
                                                onClick={() => onEdit(record)}
                                                disabled={submitting}
                                            >
                                                <svg className="h-3.5 w-3.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" />
                                                </svg>
                                                Edit
                                            </button>
                                        ) : null}
                                        {record.canRequestCorrection ? (
                                            <button
                                                type="button"
                                                className="rounded-lg border px-2.5 py-1.5 text-xs font-semibold border-amber-400/50 text-amber-700 dark:border-amber-300/45 dark:text-amber-200"
                                                onClick={() => onRequestCorrection(record)}
                                                disabled={submitting}
                                            >
                                                Request Correction
                                            </button>
                                        ) : null}
                                        {record.canDelete ? (
                                            <button
                                                type="button"
                                                className="rounded-lg border px-2.5 py-1.5 text-xs font-semibold border-red-500/45 text-red-600 dark:border-red-400/50 dark:text-red-300"
                                                onClick={() => onDelete(record)}
                                                disabled={submitting}
                                            >
                                                <svg className="h-3.5 w-3.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                    <path d="M6 7h12" />
                                                    <path d="M9 7V5h6v2" />
                                                    <path d="M8 7v12h8V7" />
                                                    <path d="M10 11v6" />
                                                    <path d="M14 11v6" />
                                                </svg>
                                                Delete
                                            </button>
                                        ) : null}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-4 flex items-center justify-between gap-3 flex-wrap">
                <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                    Showing {meta?.from || 0} to {meta?.to || 0} of {meta?.total || 0}
                </p>
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        className="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                        style={{ borderColor: 'var(--hr-line)' }}
                        onClick={() => onPageChange((meta?.currentPage || 1) - 1)}
                        disabled={loading || (meta?.currentPage || 1) <= 1}
                    >
                        Previous
                    </button>
                    {visiblePages.map((page) => (
                        <button
                            key={page}
                            type="button"
                            className="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                            style={{
                                borderColor: page === meta?.currentPage ? 'var(--hr-accent-border)' : 'var(--hr-line)',
                                background: page === meta?.currentPage ? 'var(--hr-accent-soft)' : 'transparent',
                            }}
                            onClick={() => onPageChange(page)}
                            disabled={loading}
                        >
                            {page}
                        </button>
                    ))}
                    <button
                        type="button"
                        className="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                        style={{ borderColor: 'var(--hr-line)' }}
                        onClick={() => onPageChange((meta?.currentPage || 1) + 1)}
                        disabled={loading || (meta?.currentPage || 1) >= (meta?.lastPage || 1)}
                    >
                        Next
                    </button>
                </div>
            </div>
        </article>
    );
}
