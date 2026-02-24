import React, { useEffect, useMemo, useRef, useState } from 'react';

export function PunchPanel({
    open,
    punch = {},
    submitting = false,
    successMessage = '',
    errorMessage = '',
    reason = '',
    onReasonChange,
    onSubmit,
    onClose,
}) {
    const panelRef = useRef(null);
    const [visible, setVisible] = useState(false);
    const [now, setNow] = useState(() => new Date());

    const action = useMemo(() => {
        if (!punch?.canPunchSelf) return null;
        if (punch?.nextAction === 'check_in') return 'in';
        if (punch?.nextAction === 'check_out') return 'out';
        return null;
    }, [punch]);

    useEffect(() => {
        if (open) {
            setVisible(true);
            document.body.classList.add('app-react-modal-open');
        } else {
            setVisible(false);
            document.body.classList.remove('app-react-modal-open');
        }
    }, [open]);

    // Live time update (gentle, 1s)
    useEffect(() => {
        if (!open) return undefined;
        const timer = window.setInterval(() => setNow(new Date()), 1000);
        return () => window.clearInterval(timer);
    }, [open]);

    useEffect(() => {
        if (!open) return undefined;
        const onKeyDown = (event) => {
            if (event.key === 'Escape' && !submitting) {
                onClose();
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [onClose, open, submitting]);

    if (!open) {
        return null;
    }

    const dateStr = now.toLocaleDateString(undefined, {
        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
    });
    const timeStr = now.toLocaleTimeString(undefined, {
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });

    const alreadyCompleted = Boolean(punch?.canPunchSelf) && punch?.nextAction === 'none';
    const buttonLabel = action === 'in' ? 'Punch In' : action === 'out' ? 'Punch Out' : 'Attendance Completed';

    return (
        <div
            className="fixed inset-0 z-[2300] bg-slate-950/30 flex items-end sm:items-center justify-center p-3"
            onClick={(event) => {
                if (event.target === event.currentTarget && !submitting) {
                    onClose();
                }
            }}
        >
            <article
                ref={panelRef}
                className={`w-full sm:max-w-md rounded-2xl border shadow-2xl transform transition-all duration-200 ease-out ${visible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'}`}
                style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}
            >
                <div className="flex items-center justify-between gap-3 px-4 pt-4">
                    <h3 className="text-base font-extrabold">Mark Attendance</h3>
                    <button
                        type="button"
                        aria-label="Close"
                        className="rounded-lg p-2 text-sm"
                        style={{ color: 'var(--hr-text-muted)' }}
                        onClick={onClose}
                        disabled={submitting}
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                    </button>
                </div>

                <div className="px-4 pb-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.08em] mt-2" style={{ color: 'var(--hr-text-muted)' }}>Current Date</p>
                    <div className="mt-1 w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent" style={{ borderColor: 'var(--hr-line)' }}>{dateStr}</div>

                    <p className="text-xs font-semibold uppercase tracking-[0.08em] mt-3" style={{ color: 'var(--hr-text-muted)' }}>Current Time</p>
                    <div className="mt-1 w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent" style={{ borderColor: 'var(--hr-line)' }}>{timeStr}</div>

                    <p className="text-xs font-semibold uppercase tracking-[0.08em] mt-3" style={{ color: 'var(--hr-text-muted)' }}>Reason (optional)</p>
                    <textarea
                        rows={3}
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent resize-y mt-1"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={reason}
                        onChange={(e) => onReasonChange?.(e.target.value)}
                        placeholder="Add a note for context (optional)"
                        disabled={submitting || alreadyCompleted}
                    />

                    {successMessage ? (
                        <div className="mt-3 rounded-xl border px-3 py-2 text-sm" style={{ borderColor: 'rgb(16 185 129 / 0.4)', color: '#064e3b', background: 'rgb(16 185 129 / 0.12)' }}>
                            {successMessage}
                        </div>
                    ) : null}
                    {errorMessage ? (
                        <div className="mt-3 rounded-xl border px-3 py-2 text-sm" style={{ borderColor: 'rgb(239 68 68 / 0.4)', color: '#7f1d1d', background: 'rgb(239 68 68 / 0.12)' }}>
                            {errorMessage}
                        </div>
                    ) : null}

                    {alreadyCompleted ? (
                        <p className="mt-3 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                            Attendance completed for today.
                        </p>
                    ) : null}

                    <div className="mt-4 flex items-center justify-end">
                        <button
                            type="button"
                            className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                            style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                            onClick={() => onSubmit?.(action, reason)}
                            disabled={submitting || alreadyCompleted || !action}
                        >
                            {submitting ? (
                                <span className="inline-flex items-center gap-2">
                                    <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 3v3M12 18v3M4.22 4.22l2.12 2.12M15.66 15.66l2.12 2.12M3 12h3M18 12h3M4.22 19.78l2.12-2.12M15.66 8.34l2.12-2.12"/></svg>
                                    Processing...
                                </span>
                            ) : (
                                buttonLabel
                            )}
                        </button>
                    </div>
                </div>
            </article>
        </div>
    );
}
