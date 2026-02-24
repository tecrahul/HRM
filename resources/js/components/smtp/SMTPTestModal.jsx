import React, { useEffect, useState } from 'react';
import { AppModalPortal } from '../shared/AppModalPortal';

export function SMTPTestModal({ open, onClose, onSend, busy = false, error = '', success = '' }) {
    const [email, setEmail] = useState('');

    useEffect(() => {
        if (!open) {
            setEmail('');
            return;
        }
    }, [open]);

    useEffect(() => {
        if (open && success) {
            const t = window.setTimeout(() => {
                onClose?.();
            }, 900);
            return () => window.clearTimeout(t);
        }
    }, [open, success, onClose]);

    return (
        <AppModalPortal open={open} onBackdropClick={busy ? null : onClose}>
            <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                <h3 className="text-lg font-extrabold">Send Test Email</h3>
                <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                    Verify SMTP by sending a test message.
                </p>
                <div className="mt-4">
                    <label htmlFor="smtp-test-email" className="text-[11px] font-semibold uppercase tracking-[0.12em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Recipient Email
                    </label>
                    <input
                        id="smtp-test-email"
                        type="email"
                        className="mt-1 w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                        style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder="admin@example.com"
                        disabled={busy}
                    />
                    {error ? <p className="mt-1 text-xs text-red-500">{error}</p> : null}
                    {success ? <p className="mt-1 text-xs text-green-600">{success}</p> : null}
                </div>
                <div className="mt-5 flex items-center justify-end gap-3">
                    <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold" onClick={onClose} disabled={busy} style={{ border: '1px solid var(--hr-line)' }}>
                        Close
                    </button>
                    <button
                        type="button"
                        className="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                        style={{ background: '#2563eb' }}
                        onClick={() => onSend?.(email)}
                        disabled={busy}
                    >
                        {busy ? 'Sending…' : 'Send Test'}
                    </button>
                </div>
            </div>
        </AppModalPortal>
    );
}

