import React from 'react';
import { AppModalPortal } from '../shared/AppModalPortal';
import { PermissionGuard } from '../shared/PermissionGuard';

export function HolidayDetailModal({
    open,
    holiday,
    canEdit = false,
    canDelete = false,
    onEdit,
    onDelete,
    onClose,
}) {
    if (!open || !holiday) {
        return null;
    }

    return (
        <AppModalPortal open={open} onBackdropClick={onClose}>
            <div className="fixed inset-0 z-[2200] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center px-4">
                <div className="w-full max-w-lg rounded-2xl border shadow-xl p-5 bg-[var(--hr-surface)]" style={{ borderColor: 'var(--hr-line)' }}>
                    <div className="flex items-start justify-between gap-3">
                        <h4 className="text-base font-extrabold">{holiday.name}</h4>
                        <button
                            type="button"
                            className="rounded-xl border px-3 py-1 text-xs font-semibold"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={onClose}
                        >
                            Close
                        </button>
                    </div>

                    <div className="mt-3 space-y-2 text-sm">
                        <p><span className="font-semibold">Date:</span> {holiday.dateLabel}</p>
                        <p><span className="font-semibold">Type:</span> {holiday.holidayTypeLabel}</p>
                        <p><span className="font-semibold">Branch:</span> {holiday.branchName || 'All Branches'}</p>
                        <p><span className="font-semibold">Status:</span> {holiday.statusLabel}</p>
                        <p><span className="font-semibold">Created:</span> {holiday.createdDateLabel || 'N/A'}</p>
                        <div>
                            <p className="font-semibold">Description</p>
                            <p className="mt-1" style={{ color: 'var(--hr-text-muted)' }}>{holiday.description || 'N/A'}</p>
                        </div>
                    </div>

                    <div className="mt-4 flex items-center justify-end gap-2">
                        <PermissionGuard allowed={canEdit}>
                            <button
                                type="button"
                                className="rounded-xl border px-3 py-2 text-sm font-semibold"
                                style={{ borderColor: 'var(--hr-line)' }}
                                onClick={() => onEdit?.(holiday)}
                            >
                                Edit
                            </button>
                        </PermissionGuard>
                        <PermissionGuard allowed={canDelete}>
                            <button
                                type="button"
                                className="rounded-xl px-3 py-2 text-sm font-semibold text-white"
                                style={{ background: 'linear-gradient(120deg, #dc2626, #ef4444)' }}
                                onClick={() => onDelete?.(holiday)}
                            >
                                Delete
                            </button>
                        </PermissionGuard>
                    </div>
                </div>
            </div>
        </AppModalPortal>
    );
}

