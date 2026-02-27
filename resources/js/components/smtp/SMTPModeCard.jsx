import React from 'react';

export function SMTPModeCard({
    useSystem,
    onToggle,
    busy = false,
    renderToggle = true,
}) {
    const activeLabel = useSystem ? 'System (.env)' : 'Custom SMTP Profile';
    const toggleId = 'smtp-mode-toggle';

    return (
        <section className="hrm-modern-surface rounded-2xl p-6 flex flex-col gap-4">
            <div className="flex items-start gap-4">
                <div className="min-w-0">
                    <h3 className="text-base md:text-lg font-extrabold">Configuration Mode</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Choose whether to use system environment settings (.env) or a custom SMTP profile.
                    </p>
                </div>
                {renderToggle ? (
                    <div className="ml-auto flex items-center gap-4">
                        <label htmlFor={toggleId} className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                            Use System Environment Settings (.env)
                        </label>
                        <button
                            id={toggleId}
                            type="button"
                            role="switch"
                            aria-checked={useSystem ? 'true' : 'false'}
                            aria-label="Toggle system environment mode"
                            className="relative inline-flex h-6 w-11 items-center rounded-full border"
                            style={{ borderColor: 'var(--hr-line)', background: useSystem ? '#22c55e' : 'var(--hr-surface-strong)' }}
                            onClick={() => { if (!busy) onToggle(!useSystem); }}
                            disabled={busy}
                        >
                            <span
                                className="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                style={{ transform: useSystem ? 'translateX(20px)' : 'translateX(2px)' }}
                            />
                        </button>
                    </div>
                ) : null}
            </div>

            <div className="flex items-center gap-2 text-xs flex-wrap" style={{ color: 'var(--hr-text-muted)' }}>
                <span className="inline-flex items-center gap-2">
                    <span className="inline-block h-2 w-2 rounded-full" style={{ background: 'currentColor' }} />
                    <span>Active Source: {activeLabel}</span>
                </span>
                <span
                    className="inline-flex items-center gap-1 rounded-full px-2 py-0.5"
                    style={{ background: 'var(--hr-surface-strong)', border: '1px solid var(--hr-line)' }}
                    title="System Mode reads from server .env; Custom Mode uses database-stored configuration."
                >
                    <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                    <span className="whitespace-normal">System Mode = .env, Custom Mode = database</span>
                </span>
            </div>
        </section>
    );
}
