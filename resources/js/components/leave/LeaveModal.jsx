import React from 'react';
import { AppModalPortal } from '../shared/AppModalPortal';

export function LeaveModal({
    open,
    title,
    children,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    confirmTone = 'primary',
    busy = false,
    onConfirm,
    onCancel,
}) {
    const confirmStyle = confirmTone === 'danger'
        ? { background: '#dc2626' }
        : confirmTone === 'warning'
            ? { background: '#d97706' }
            : { background: '#0f766e' };

    return (
        <AppModalPortal open={open} onBackdropClick={busy ? null : onCancel}>
            <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                <h3 className="text-lg font-bold">{title}</h3>
                <div className="mt-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                    {children}
                </div>
                <div className="mt-5 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        className="rounded-xl px-4 py-2 text-sm font-semibold border"
                        style={{ borderColor: 'var(--hr-line)' }}
                        onClick={onCancel}
                        disabled={busy}
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        className="rounded-xl px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        style={confirmStyle}
                        onClick={onConfirm}
                        disabled={busy}
                    >
                        {busy ? 'Processing...' : confirmLabel}
                    </button>
                </div>
            </div>
        </AppModalPortal>
    );
}
