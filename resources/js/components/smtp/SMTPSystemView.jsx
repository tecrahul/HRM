import React from 'react';

export function SMTPSystemView({
    config = {},
    onTestClick,
    testing = false,
    // New props for toggle placement in header
    useSystem = true,
    onToggle,
    toggleBusy = false,
}) {
    const Field = ({ label, value }) => (
        <div className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
            <p className="text-[11px] font-semibold uppercase tracking-[0.12em]" style={{ color: 'var(--hr-text-muted)' }}>{label}</p>
            <div className="mt-1 flex items-center gap-2">
                <svg className="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="10" rx="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                <p className="text-sm font-semibold break-words">{value || 'Not set'}</p>
            </div>
        </div>
    );

    return (
        <section className="hrm-modern-surface rounded-2xl p-6">
            <div className="flex items-start gap-4">
                <div>
                    <h3 className="text-base md:text-lg font-extrabold">System SMTP Configuration</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Values are loaded from your server environment file.
                    </p>
                </div>
                <div className="ml-auto flex items-center gap-3">
                    <label htmlFor="smtp-mode-toggle-header" className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                        Use System Environment Settings (.env)
                    </label>
                    <button
                        id="smtp-mode-toggle-header"
                        type="button"
                        role="switch"
                        aria-checked={useSystem ? 'true' : 'false'}
                        aria-label="Toggle system environment mode"
                        className="relative inline-flex h-6 w-11 items-center rounded-full border"
                        style={{ borderColor: 'var(--hr-line)', background: useSystem ? '#22c55e' : 'var(--hr-surface-strong)' }}
                        onClick={() => { if (!toggleBusy && typeof onToggle === 'function') onToggle(!useSystem); }}
                        disabled={toggleBusy}
                    >
                        <span
                            className="inline-block h-4 w-4 transform rounded-full bg-white transition"
                            style={{ transform: useSystem ? 'translateX(20px)' : 'translateX(2px)' }}
                        />
                    </button>
                </div>
            </div>
            <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <Field label="Mail Driver" value={config.mail_driver} />
                <Field label="Mail Host" value={config.mail_host} />
                <Field label="Mail Port" value={String(config.mail_port || '')} />
                <Field label="Mail Username" value={config.mail_username} />
                <Field label="Encryption" value={config.mail_encryption} />
                <Field label="From Address" value={config.from_address} />
                <Field label="From Name" value={config.from_name} />
                <Field label="Mail Password" value={config.has_password ? '••••••••' : 'Not set'} />
            </div>
            <div className="mt-6 flex items-center justify-end">
                <button
                    type="button"
                    className="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                    style={{ background: '#2563eb' }}
                    onClick={onTestClick}
                    disabled={testing}
                >
                    {testing ? 'Sending…' : 'Send Test Email'}
                </button>
            </div>
        </section>
    );
}
