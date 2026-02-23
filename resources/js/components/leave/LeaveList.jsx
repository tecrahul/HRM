import React, { useEffect, useMemo, useState } from 'react';
import { LeaveStatusBadge } from './LeaveStatusBadge';

function SkeletonRows() {
    return (
        <>
            {Array.from({ length: 6 }).map((_, index) => (
                <tr key={`skeleton-${index}`} className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
                    {Array.from({ length: 6 }).map((__, cellIndex) => (
                        <td key={`skeleton-cell-${index}-${cellIndex}`} className="py-3 px-2">
                            <div className="h-4 rounded bg-slate-200 dark:bg-slate-700 animate-pulse w-full max-w-[160px]" />
                        </td>
                    ))}
                </tr>
            ))}
        </>
    );
}

export function LeaveList({
    leaves,
    meta,
    loading,
    submitting,
    canReview,
    onApprove,
    onRejectRequest,
    onDeleteRequest,
    onEditRequest,
    onPageChange,
    onBulkApprove,
}) {
    const [selectedIds, setSelectedIds] = useState([]);

    useEffect(() => {
        setSelectedIds([]);
    }, [leaves]);

    const pendingSelectableIds = useMemo(
        () => leaves.filter((leave) => leave.canReview).map((leave) => leave.id),
        [leaves]
    );

    const allSelected = pendingSelectableIds.length > 0 && pendingSelectableIds.every((id) => selectedIds.includes(id));

    const toggleSelectAll = () => {
        if (allSelected) {
            setSelectedIds([]);
            return;
        }
        setSelectedIds([...pendingSelectableIds]);
    };

    const toggleSelectOne = (leaveId) => {
        setSelectedIds((prev) => (
            prev.includes(leaveId)
                ? prev.filter((id) => id !== leaveId)
                : [...prev, leaveId]
        ));
    };

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
        <article className="hrm-modern-surface rounded-2xl p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 className="text-lg font-extrabold">Leave Directory</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Track requests, review pending leaves, and manage leave lifecycle.
                    </p>
                </div>
                {canReview ? (
                    <button
                        type="button"
                        className="rounded-xl px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        style={{ background: '#15803d' }}
                        onClick={() => onBulkApprove(selectedIds)}
                        disabled={submitting || selectedIds.length === 0}
                    >
                        {submitting ? 'Processing...' : `Approve Selected (${selectedIds.length})`}
                    </button>
                ) : null}
            </div>

            <div className="mt-4 overflow-x-auto">
                <table className="w-full min-w-[1080px] text-sm">
                    <thead>
                        <tr className="border-b text-left" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                            {canReview ? (
                                <th className="py-2.5 px-2 font-semibold">
                                    <input
                                        type="checkbox"
                                        checked={allSelected}
                                        onChange={toggleSelectAll}
                                        disabled={pendingSelectableIds.length === 0 || submitting}
                                    />
                                </th>
                            ) : null}
                            <th className="py-2.5 px-2 font-semibold">Employee</th>
                            <th className="py-2.5 px-2 font-semibold">Leave Type</th>
                            <th className="py-2.5 px-2 font-semibold">Date Range</th>
                            <th className="py-2.5 px-2 font-semibold">Days</th>
                            <th className="py-2.5 px-2 font-semibold">Status</th>
                            <th className="py-2.5 px-2 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? <SkeletonRows /> : null}

                        {!loading && leaves.length === 0 ? (
                            <tr>
                                <td colSpan={canReview ? 7 : 6} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                    No leave requests found for selected filters.
                                </td>
                            </tr>
                        ) : null}

                        {!loading && leaves.map((leave) => (
                            <tr key={leave.id} className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
                                {canReview ? (
                                    <td className="py-3 px-2">
                                        <input
                                            type="checkbox"
                                            checked={selectedIds.includes(leave.id)}
                                            onChange={() => toggleSelectOne(leave.id)}
                                            disabled={!leave.canReview || submitting}
                                        />
                                    </td>
                                ) : null}
                                <td className="py-3 px-2">
                                    <p className="font-semibold">{leave.employee?.name}</p>
                                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                                        {leave.employee?.department} • {leave.employee?.branch}
                                    </p>
                                </td>
                                <td className="py-3 px-2">
                                    <p className="font-semibold">{leave.leaveTypeLabel}</p>
                                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                                        {leave.dayTypeLabel}{leave.halfDaySessionLabel ? ` - ${leave.halfDaySessionLabel}` : ''}
                                    </p>
                                </td>
                                <td className="py-3 px-2">{leave.dateRangeLabel}</td>
                                <td className="py-3 px-2">{Number(leave.totalDays || 0).toFixed(1)}</td>
                                <td className="py-3 px-2">
                                    <LeaveStatusBadge status={leave.status} label={leave.statusLabel} />
                                </td>
                                <td className="py-3 px-2">
                                    <div className="flex items-center justify-end gap-2 flex-wrap">
                                        {leave.canEdit ? (
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border"
                                                style={{ borderColor: 'var(--hr-line)' }}
                                                onClick={() => onEditRequest(leave)}
                                                disabled={submitting}
                                            >
                                                Edit
                                            </button>
                                        ) : null}
                                        {leave.canReview ? (
                                            <>
                                                <button
                                                    type="button"
                                                    className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white"
                                                    style={{ background: '#15803d' }}
                                                    onClick={() => onApprove(leave)}
                                                    disabled={submitting}
                                                >
                                                    Approve
                                                </button>
                                                <button
                                                    type="button"
                                                    className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white"
                                                    style={{ background: '#b45309' }}
                                                    onClick={() => onRejectRequest(leave)}
                                                    disabled={submitting}
                                                >
                                                    Reject
                                                </button>
                                            </>
                                        ) : null}
                                        {leave.canDelete ? (
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600"
                                                style={{ borderColor: 'rgb(239 68 68 / 0.4)' }}
                                                onClick={() => onDeleteRequest(leave)}
                                                disabled={submitting}
                                            >
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

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                    Page {meta?.currentPage || 1} of {meta?.lastPage || 1}
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
