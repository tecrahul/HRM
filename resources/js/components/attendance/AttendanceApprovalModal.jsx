import React, { useEffect } from 'react';

export function AttendanceApprovalModal({
    open,
    title,
    description,
    busy,
    confirmLabel = 'Confirm',
    confirmTone = 'primary',
    onConfirm,
    onCancel,
    children,
}) {
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onKeyDown = (event) => {
            if (event.key === 'Escape' && !busy) {
                onCancel();
            }
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [busy, onCancel, open]);

    if (!open) {
        return null;
    }

    const buttonStyle = confirmTone === 'danger'
        ? { background: '#b91c1c' }
        : confirmTone === 'warning'
            ? { background: '#b45309' }
            : { background: '#0f766e' };

    return (
        <div className="fixed inset-0 z-[2300] bg-slate-950/45 backdrop-blur-[1.2px] flex items-center justify-center p-4" onClick={(event) => {
            if (event.target === event.currentTarget && !busy) {
                onCancel();
            }
        }}>
            <article className="w-full max-w-lg rounded-2xl p-5 border shadow-2xl" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                <h3 className="text-lg font-extrabold">{title}</h3>
                {description ? (
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>{description}</p>
                ) : null}

                {children ? <div className="mt-4">{children}</div> : null}

                <div className="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        className="rounded-xl border px-3 py-2 text-sm font-semibold"
                        style={{ borderColor: 'var(--hr-line)' }}
                        onClick={onCancel}
                        disabled={busy}
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        className="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                        style={buttonStyle}
                        onClick={onConfirm}
                        disabled={busy}
                    >
                        {busy ? 'Processing...' : confirmLabel}
                    </button>
                </div>
            </article>
        </div>
    );
}
