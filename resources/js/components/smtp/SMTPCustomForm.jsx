import React, { useMemo, useState } from 'react';

export function SMTPCustomForm({
    values,
    errors = {},
    onChange,
    onSave,
    onTest,
    saving = false,
    testing = false,
    baseline = {},
    lastUpdatedAt,
    configuredBy,
}) {
    const [showPassword, setShowPassword] = useState(false);

    const isDirty = useMemo(() => {
        const keys = ['mail_driver', 'mail_encryption', 'mail_host', 'mail_port', 'mail_username', 'from_address', 'from_name'];
        for (const key of keys) {
            if (String(values[key] || '') !== String(baseline[key] || '')) {
                return true;
            }
        }
        if (values.mail_password) {
            return true;
        }
        return false;
    }, [baseline, values]);

    const Field = ({ id, label, type = 'text', value, onValue, placeholder, error, rightSlot, disabled = false }) => (
        <div className="flex flex-col gap-2">
            <label htmlFor={id} className="text-[11px] font-semibold uppercase tracking-[0.12em]" style={{ color: 'var(--hr-text-muted)' }}>
                {label}
            </label>
            <div className="relative">
                <input
                    id={id}
                    type={type}
                    className={`w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent ${error ? 'border-red-400 focus:border-red-500' : ''}`}
                    style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
                    value={value}
                    onChange={(e) => onValue(e.target.value)}
                    placeholder={placeholder}
                    autoComplete={id}
                    disabled={disabled}
                />
                {rightSlot ? (
                    <div className="absolute right-2 top-1/2 -translate-y-1/2">
                        {rightSlot}
                    </div>
                ) : null}
            </div>
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );

    const SelectField = ({ id, label, value, onValue, options = [], placeholder, error, disabled = false }) => (
        <div className="flex flex-col gap-2">
            <label htmlFor={id} className="text-[11px] font-semibold uppercase tracking-[0.12em]" style={{ color: 'var(--hr-text-muted)' }}>
                {label}
            </label>
            <select
                id={id}
                className={`w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent ${error ? 'border-red-400 focus:border-red-500' : ''}`}
                style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
                value={value}
                onChange={(e) => onValue(e.target.value)}
                disabled={disabled}
            >
                {placeholder ? <option value="">{placeholder}</option> : null}
                {options.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                        {opt.label}
                    </option>
                ))}
            </select>
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );

    return (
        <section className="hrm-modern-surface rounded-2xl p-6">
            <div className="flex items-start gap-4">
                <div className="min-w-0">
                    <h3 className="text-base md:text-lg font-extrabold">Custom SMTP Configuration</h3>
                    <p className="text-sm mt-2" style={{ color: 'var(--hr-text-muted)' }}>
                        Switching to custom mode overrides .env configuration.
                    </p>
                    <div className="mt-2 text-xs flex gap-4" style={{ color: 'var(--hr-text-muted)' }}>
                        {lastUpdatedAt ? <span>Last Updated: {new Date(lastUpdatedAt).toLocaleString()}</span> : null}
                        {configuredBy ? <span>Configured By: {configuredBy}</span> : null}
                    </div>
                </div>
                <div className="ml-auto flex items-center gap-3">
                    <button
                        type="button"
                        className="rounded-xl px-4 py-2 text-sm font-semibold"
                        style={{ border: '1px solid var(--hr-line)' }}
                        onClick={onTest}
                        disabled={testing}
                    >
                        {testing ? 'Sending…' : 'Send Test Email'}
                    </button>
                </div>
            </div>

            {/**
             * Determine if Localhost (sendmail) is selected.
             * When using sendmail, SMTP-specific fields should be disabled.
             */}
            <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Row 1 */}
                <SelectField
                    id="mail_driver"
                    label="Mail Driver"
                    value={values.mail_driver}
                    onValue={(v) => {
                        // If switching to Localhost (sendmail), set sensible defaults and disable SMTP fields
                        if (v === 'sendmail') {
                            onChange('mail_driver', v);
                            onChange('mail_host', values.mail_host || '127.0.0.1');
                            onChange('mail_port', values.mail_port || '25');
                            onChange('mail_username', '');
                            onChange('mail_password', '');
                            onChange('mail_encryption', '');
                        } else {
                            onChange('mail_driver', v);
                        }
                    }}
                    options={[
                        { value: 'smtp', label: 'SMTP' },
                        { value: 'sendmail', label: 'Localhost (local server)' },
                    ]}
                    error={errors.mail_driver}
                />
                <SelectField
                    id="mail_encryption"
                    label="Encryption"
                    value={values.mail_encryption || ''}
                    onValue={(v) => onChange('mail_encryption', v)}
                    options={[
                        { value: '', label: 'None' },
                        { value: 'tls', label: 'TLS' },
                        { value: 'ssl', label: 'SSL' },
                    ]}
                    error={errors.mail_encryption}
                />
                {/* Row 2 */}
                <Field id="mail_host" label="Mail Host" value={values.mail_host} onValue={(v) => onChange('mail_host', v)} error={errors.mail_host} />
                <Field id="mail_port" label="Mail Port" type="number" value={values.mail_port} onValue={(v) => onChange('mail_port', v)} error={errors.mail_port} />
                {/* Row 3 */}
                <Field id="mail_username" label="Mail Username" value={values.mail_username} onValue={(v) => onChange('mail_username', v)} error={errors.mail_username} />
                <Field
                    id="mail_password"
                    label="Mail Password"
                    type={showPassword ? 'text' : 'password'}
                    value={values.mail_password}
                    onValue={(v) => onChange('mail_password', v)}
                    error={errors.mail_password}
                    rightSlot={(
                        <button type="button" className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }} onClick={() => setShowPassword((s) => !s)}>
                            {showPassword ? 'Hide' : 'Show'}
                        </button>
                    )}
                />
                {/* Row 4 */}
                <Field id="from_address" label="From Address" type="email" value={values.from_address} onValue={(v) => onChange('from_address', v)} error={errors.from_address} />
                <Field id="from_name" label="From Name" value={values.from_name} onValue={(v) => onChange('from_name', v)} error={errors.from_name} />
            </div>

            <div className="mt-6 flex items-center gap-4">
                <button
                    type="button"
                    className="rounded-xl px-5 py-2.5 text-sm font-semibold text-white"
                    style={{ background: isDirty ? '#0f766e' : 'rgba(15,118,110,0.4)' }}
                    onClick={onSave}
                    disabled={saving || !isDirty}
                >
                    {saving ? 'Saving…' : 'Save Configuration'}
                </button>
                <button
                    type="button"
                    className="rounded-xl px-4 py-2 text-sm font-semibold"
                    style={{ border: '1px solid var(--hr-line)' }}
                    onClick={onTest}
                    disabled={testing}
                >
                    {testing ? 'Sending…' : 'Send Test Email'}
                </button>
            </div>
        </section>
    );
}
