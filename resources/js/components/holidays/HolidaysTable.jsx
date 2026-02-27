import React from 'react';
import { PermissionGuard } from '../shared/PermissionGuard';
import { StatusBadge } from '../shared/StatusBadge';

function Pagination({ meta, loading, onPageChange }) {
    const current = meta?.currentPage || 1;
    const last = meta?.lastPage || 1;

    if (last <= 1) {
        return null;
    }

    const visiblePages = [];
    for (let page = Math.max(1, current - 2); page <= Math.min(last, current + 2); page += 1) {
        visiblePages.push(page);
    }

    return (
        <div className="mt-6 flex items-center justify-between gap-4 flex-wrap">
            <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                {meta.total > 0 ? `Showing ${meta.from}-${meta.to} of ${meta.total}` : 'No records'}
            </p>
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    className="rounded-lg border px-2.5 py-1 text-xs font-semibold disabled:opacity-55 disabled:cursor-not-allowed"
                    style={{ borderColor: 'var(--hr-line)' }}
                    disabled={loading || current <= 1}
                    onClick={() => onPageChange(current - 1)}
                >
                    Prev
                </button>
                {visiblePages.map((page) => (
                    <button
                        key={page}
                        type="button"
                        className="rounded-lg border px-2.5 py-1 text-xs font-semibold"
                        style={{
                            borderColor: page === current ? '#0ea5a4' : 'var(--hr-line)',
                            background: page === current ? 'rgb(15 118 110 / 0.14)' : 'transparent',
                        }}
                        disabled={loading}
                        onClick={() => onPageChange(page)}
                    >
                        {page}
                    </button>
                ))}
                <button
                    type="button"
                    className="rounded-lg border px-2.5 py-1 text-xs font-semibold disabled:opacity-55 disabled:cursor-not-allowed"
                    style={{ borderColor: 'var(--hr-line)' }}
                    disabled={loading || current >= last}
                    onClick={() => onPageChange(current + 1)}
                >
                    Next
                </button>
            </div>
        </div>
    );
}

function DateHeader({ sort, onToggleSort }) {
    return (
        <button
            type="button"
            className="inline-flex items-center gap-1"
            onClick={onToggleSort}
            title="Sort by date"
        >
            Date / Date Range
            <span>{sort === 'date_desc' ? '▼' : '▲'}</span>
        </button>
    );
}

export function HolidaysTable({
    holidays,
    meta,
    loading,
    listError,
    sort,
    isDarkMode,
    canEdit,
    canDelete,
    onToggleSort,
    onEdit,
    onDelete,
    onRetry,
    onPageChange,
}) {
    const canManageRows = canEdit || canDelete;

    return (
        <section className="hrm-modern-surface rounded-2xl p-6">
            {listError ? (
                <div className="rounded-xl border px-3 py-2 text-sm text-red-600" style={{ borderColor: 'rgb(248 113 113 / 0.4)', background: 'rgb(254 242 242 / 0.72)' }}>
                    <div className="flex items-center justify-between gap-2">
                        <span>{listError}</span>
                        <button
                            type="button"
                            className="rounded-lg px-2.5 py-1 text-xs font-semibold border"
                            style={{ borderColor: 'rgb(248 113 113 / 0.42)' }}
                            onClick={onRetry}
                        >
                            Retry
                        </button>
                    </div>
                </div>
            ) : null}

            <div className="mt-6 overflow-x-auto">
                <table className="w-full min-w-[980px] text-sm">
                    <thead>
                        <tr className="border-b text-left" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                            <th className="py-4 px-6 font-semibold">Holiday Name</th>
                            <th className="py-4 px-6 font-semibold">
                                <DateHeader sort={sort} onToggleSort={onToggleSort} />
                            </th>
                            <th className="py-4 px-6 font-semibold">Type</th>
                            <th className="py-4 px-6 font-semibold">Branch</th>
                            <th className="py-4 px-6 font-semibold">Status</th>
                            <th className="py-4 px-6 font-semibold">Created Date</th>
                            {canManageRows ? (
                                <th className="py-4 px-6 font-semibold text-right">Actions</th>
                            ) : null}
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={canManageRows ? 7 : 6} className="py-8 px-6 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                    Loading holidays...
                                </td>
                            </tr>
                        ) : null}

                        {!loading && holidays.length === 0 ? (
                            <tr>
                                <td colSpan={canManageRows ? 7 : 6} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                    No holiday records found.
                                </td>
                            </tr>
                        ) : null}

                        {!loading && holidays.map((holiday) => (
                            <tr key={holiday.id} className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
                                <td className="py-4 px-6">
                                    <p className="font-semibold">{holiday.name}</p>
                                    <p className="text-xs mt-2" style={{ color: 'var(--hr-text-muted)' }} title={holiday.description || 'N/A'}>
                                        {holiday.descriptionShort || 'N/A'}
                                    </p>
                                </td>
                                <td className="py-4 px-6 font-semibold">{holiday.dateLabel}</td>
                                <td className="py-4 px-6">
                                    <StatusBadge
                                        value={holiday.holiday_type}
                                        label={holiday.holidayTypeLabel}
                                        isDark={isDarkMode}
                                    />
                                </td>
                                <td className="py-4 px-6">{holiday.branchName || 'All Branches'}</td>
                                <td className="py-4 px-6">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <StatusBadge
                                            value={holiday.is_active ? 'active' : 'inactive'}
                                            label={holiday.statusLabel}
                                            isDark={isDarkMode}
                                        />
                                        <StatusBadge
                                            value={holiday.temporalStatus}
                                            label={holiday.temporalStatusLabel}
                                            isDark={isDarkMode}
                                        />
                                    </div>
                                </td>
                                <td className="py-4 px-6">{holiday.createdDateLabel || 'N/A'}</td>
                                {canManageRows ? (
                                    <td className="py-4 px-6">
                                        <div className="flex items-center justify-end gap-2">
                                            <PermissionGuard allowed={canEdit}>
                                                <button
                                                    type="button"
                                                    className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border"
                                                    style={{ borderColor: 'var(--hr-line)' }}
                                                    onClick={() => onEdit(holiday)}
                                                >
                                                    Edit
                                                </button>
                                            </PermissionGuard>
                                            <PermissionGuard allowed={canDelete}>
                                                <button
                                                    type="button"
                                                    className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600"
                                                    style={{ borderColor: 'rgb(239 68 68 / 0.32)' }}
                                                    onClick={() => onDelete(holiday)}
                                                >
                                                    Delete
                                                </button>
                                            </PermissionGuard>
                                        </div>
                                    </td>
                                ) : null}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination meta={meta} loading={loading} onPageChange={onPageChange} />
        </section>
    );
}
