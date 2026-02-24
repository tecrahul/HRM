import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import { AppModalPortal } from '../shared/AppModalPortal';

const parsePayload = (node) => {
    if (!node) {
        return null;
    }

    const raw = node.dataset.payload;
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return null;
    }
};

const maskSecret = (hasValue) => (hasValue ? '••••••••' : 'Not set');

const displayValue = (value) => (value ? value : 'Not set');

const Alert = ({ tone = 'info', children }) => {
    const styles = tone === 'warning'
        ? {
            background: 'linear-gradient(120deg, rgb(251 146 60 / 0.16), rgb(251 191 36 / 0.12))',
            color: '#9a3412',
            borderColor: 'rgb(249 115 22 / 0.4)',
        }
        : tone === 'danger'
            ? {
                background: 'linear-gradient(120deg, rgb(248 113 113 / 0.16), rgb(248 113 113 / 0.08))',
                color: '#991b1b',
                borderColor: 'rgb(248 113 113 / 0.4)',
            }
            : {
                background: 'linear-gradient(120deg, rgb(59 130 246 / 0.12), rgb(147 197 253 / 0.16))',
                color: '#1d4ed8',
                borderColor: 'rgb(147 197 253 / 0.5)',
            };

    return (
        <div className="rounded-2xl border px-4 py-3 text-sm" style={styles}>
            {children}
        </div>
    );
};

const FieldLabel = ({ label }) => (
    <p className="text-xs font-semibold uppercase tracking-[0.12em]" style={{ color: 'var(--hr-text-muted)' }}>
        {label}
    </p>
);

const ReadOnlyField = ({ label, value }) => (
    <div className="rounded-2xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
        <FieldLabel label={label} />
        <p className="mt-2 text-sm font-semibold break-words">{value}</p>
    </div>
);

const InputField = ({
    label,
    name,
    type = 'text',
    value,
    onChange,
    placeholder,
    disabled,
    error,
    helper,
}) => (
    <div className="flex flex-col gap-2">
        <label htmlFor={name} className="text-xs font-semibold uppercase tracking-[0.12em]" style={{ color: 'var(--hr-text-muted)' }}>
            {label}
        </label>
        <input
            id={name}
            name={name}
            type={type}
            className={`rounded-xl border px-3 py-2 text-sm bg-transparent ${error ? 'border-red-400 focus:border-red-500' : ''}`}
            style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            placeholder={placeholder}
            disabled={disabled}
        />
        {helper ? (
            <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{helper}</p>
        ) : null}
        {error ? (
            <p className="text-xs text-red-500">{error}</p>
        ) : null}
    </div>
);

function SmtpSettingsApp({ payload }) {
    if (!payload) {
        return <Alert tone="danger">Unable to load SMTP data.</Alert>;
    }

    const permissions = payload.permissions ?? {};
    const routes = payload.routes ?? {};
    const defaultSource = payload.customConfig ?? payload.systemConfig ?? {};
    const [activeMode, setActiveMode] = useState(payload.mode === 'custom' ? 'custom' : 'system');
    const [useSystemMode, setUseSystemMode] = useState(payload.mode !== 'custom');
    const [systemValues, setSystemValues] = useState(payload.systemConfig ?? {});
    const [formValues, setFormValues] = useState(() => ({
        mail_driver: defaultSource.mail_driver || 'smtp',
        mail_host: defaultSource.mail_host || '',
        mail_port: String(defaultSource.mail_port || 587),
        mail_username: defaultSource.mail_username || '',
        mail_encryption: defaultSource.mail_encryption || '',
        from_address: defaultSource.from_address || '',
        from_name: defaultSource.from_name || '',
        mail_password: '',
        hasPassword: Boolean(defaultSource.has_password),
    }));
    const [statusMessage, setStatusMessage] = useState('');
    const [errorMessage, setErrorMessage] = useState('');
    const [validationErrors, setValidationErrors] = useState({});
    const [saving, setSaving] = useState(false);
    const [toggleLoading, setToggleLoading] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [testModalOpen, setTestModalOpen] = useState(false);
    const [testEmail, setTestEmail] = useState('');
    const [testBusy, setTestBusy] = useState(false);
    const [testError, setTestError] = useState('');
    const [testSuccess, setTestSuccess] = useState('');

    const csrfHeader = useMemo(() => (
        payload.csrfToken
            ? { headers: { 'X-CSRF-TOKEN': payload.csrfToken } }
            : undefined
    ), [payload.csrfToken]);

    const canEdit = !useSystemMode && permissions.canManage;
    const isAdminView = permissions.canManage;

    const readonlyValues = useSystemMode ? systemValues : formValues;

    const handleToggle = async (checked) => {
        if (!permissions.canSwitchMode || toggleLoading) {
            return;
        }

        if (checked) {
            if (activeMode === 'system') {
                setUseSystemMode(true);
                setStatusMessage('System environment mode is already active.');
                setErrorMessage('');
                return;
            }

            if (!routes.useSystem) {
                return;
            }

            setToggleLoading(true);
            setStatusMessage('');
            setErrorMessage('');
            try {
                const { data } = await axios.post(routes.useSystem, {}, csrfHeader);
                setUseSystemMode(true);
                setActiveMode('system');
                if (data?.config) {
                    setSystemValues(data.config);
                }
                setStatusMessage(data?.message || 'System configuration restored.');
            } catch (_error) {
                setErrorMessage('Unable to switch back to system mode. Please try again.');
                setUseSystemMode(false);
            } finally {
                setToggleLoading(false);
            }

            return;
        }

        setUseSystemMode(false);
        setStatusMessage('Custom mode enabled. Remember to save your SMTP settings.');
        setErrorMessage('');
    };

    const handleFieldChange = (field, value) => {
        setFormValues((prev) => ({
            ...prev,
            [field]: value,
        }));
        setValidationErrors((prev) => {
            if (!prev[field]) {
                return prev;
            }

            const next = { ...prev };
            delete next[field];
            return next;
        });
    };

    const submitCustomSettings = async () => {
        if (!routes.saveCustom) {
            return;
        }

        setSaving(true);
        setStatusMessage('');
        setErrorMessage('');
        setValidationErrors({});

        const payloadBody = {
            mail_driver: formValues.mail_driver,
            mail_host: formValues.mail_host,
            mail_port: formValues.mail_port,
            mail_username: formValues.mail_username || null,
            mail_encryption: formValues.mail_encryption || null,
            from_address: formValues.from_address,
            from_name: formValues.from_name || null,
        };

        if (formValues.mail_password) {
            payloadBody.mail_password = formValues.mail_password;
        }

        try {
            const { data } = await axios.post(routes.saveCustom, payloadBody, csrfHeader);
            const config = data?.config ?? {};
            setStatusMessage(data?.message || 'Custom SMTP settings saved.');
            setActiveMode('custom');
            setUseSystemMode(false);
            setFormValues((prev) => ({
                ...prev,
                mail_driver: config.mail_driver || prev.mail_driver,
                mail_host: config.mail_host || prev.mail_host,
                mail_port: String(config.mail_port ?? prev.mail_port),
                mail_username: config.mail_username ?? prev.mail_username,
                mail_encryption: config.mail_encryption ?? prev.mail_encryption,
                from_address: config.from_address ?? prev.from_address,
                from_name: config.from_name ?? prev.from_name,
                mail_password: '',
                hasPassword: Boolean(config.has_password),
            }));
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                const serverErrors = Object.entries(error.response.data.errors).reduce((acc, [field, messages]) => ({
                    ...acc,
                    [field]: Array.isArray(messages) ? messages[0] : messages,
                }), {});
                setValidationErrors(serverErrors);
            } else {
                setErrorMessage('Failed to save SMTP settings. Please verify the inputs and try again.');
            }
        } finally {
            setSaving(false);
            setConfirmOpen(false);
        }
    };

    const openConfirmSave = () => {
        setConfirmOpen(true);
    };

    const handleTestEmail = async () => {
        if (!routes.testEmail) {
            return;
        }

        setTestBusy(true);
        setTestError('');
        setTestSuccess('');

        try {
            const { data } = await axios.post(routes.testEmail, { recipient: testEmail }, csrfHeader);
            setTestSuccess(data?.message || 'Test email sent.');
            setTestEmail('');
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setTestError(error.response.data.errors.recipient?.[0] || 'Invalid recipient email.');
            } else {
                setTestError('Unable to send test email. Please verify SMTP details.');
            }
        } finally {
            setTestBusy(false);
        }
    };

    const renderReadOnlyFields = (passwordHasValue) => (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ReadOnlyField label="Mail Driver" value={displayValue(readonlyValues.mail_driver)} />
            <ReadOnlyField label="Mail Host" value={displayValue(readonlyValues.mail_host)} />
            <ReadOnlyField label="Mail Port" value={displayValue(readonlyValues.mail_port)} />
            <ReadOnlyField label="Mail Username" value={displayValue(readonlyValues.mail_username)} />
            <ReadOnlyField label="Mail Encryption" value={displayValue(readonlyValues.mail_encryption)} />
            <ReadOnlyField label="From Address" value={displayValue(readonlyValues.from_address)} />
            <ReadOnlyField label="From Name" value={displayValue(readonlyValues.from_name)} />
            <ReadOnlyField label="Mail Password" value={maskSecret(passwordHasValue)} />
        </div>
    );

    const renderEditableFields = () => (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <InputField
                label="Mail Driver"
                name="mail_driver"
                value={formValues.mail_driver}
                onChange={(value) => handleFieldChange('mail_driver', value)}
                disabled={!canEdit}
                error={validationErrors.mail_driver}
            />
            <InputField
                label="Mail Host"
                name="mail_host"
                value={formValues.mail_host}
                onChange={(value) => handleFieldChange('mail_host', value)}
                disabled={!canEdit}
                error={validationErrors.mail_host}
            />
            <InputField
                label="Mail Port"
                name="mail_port"
                type="number"
                value={formValues.mail_port}
                onChange={(value) => handleFieldChange('mail_port', value)}
                disabled={!canEdit}
                error={validationErrors.mail_port}
            />
            <InputField
                label="Mail Username"
                name="mail_username"
                value={formValues.mail_username}
                onChange={(value) => handleFieldChange('mail_username', value)}
                disabled={!canEdit}
                error={validationErrors.mail_username}
            />
            <InputField
                label="Mail Encryption"
                name="mail_encryption"
                value={formValues.mail_encryption}
                onChange={(value) => handleFieldChange('mail_encryption', value)}
                disabled={!canEdit}
                error={validationErrors.mail_encryption}
            />
            <InputField
                label="From Address"
                name="from_address"
                type="email"
                value={formValues.from_address}
                onChange={(value) => handleFieldChange('from_address', value)}
                disabled={!canEdit}
                error={validationErrors.from_address}
            />
            <InputField
                label="From Name"
                name="from_name"
                value={formValues.from_name}
                onChange={(value) => handleFieldChange('from_name', value)}
                disabled={!canEdit}
                error={validationErrors.from_name}
            />
            <InputField
                label="Mail Password"
                name="mail_password"
                type="password"
                value={formValues.mail_password}
                onChange={(value) => handleFieldChange('mail_password', value)}
                disabled={!canEdit}
                placeholder={formValues.hasPassword ? 'Leave blank to keep existing password' : 'Enter SMTP password'}
                helper={formValues.hasPassword ? 'Password is stored securely. Leave blank to retain it.' : 'Password will be encrypted before saving.'}
                error={validationErrors.mail_password}
            />
        </div>
    );

    const showCustomWarning = !useSystemMode;
    const showInfo = useSystemMode;

    return (
        <div className="space-y-6">
            <section className="rounded-2xl border px-4 py-4 flex flex-col gap-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold">Source Configuration</p>
                        <p className="text-xs mt-1" style={{ color: 'var(--hr-text-muted)' }}>Toggle between using .env values and a custom SMTP profile stored in the database.</p>
                        <p className="text-xs mt-2 font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                            Active Source: {activeMode === 'custom' ? 'Custom (Database)' : 'System (.env)'}
                            {(!useSystemMode && activeMode === 'system') ? ' • Pending activation after save' : ''}
                        </p>
                    </div>
                    <div className="flex flex-col gap-3 items-start lg:items-end">
                        <label className="inline-flex items-center gap-2 text-sm font-semibold">
                            <span>Use System Environment Settings (.env)</span>
                            <button
                                type="button"
                                className={`h-6 w-11 rounded-full border flex ${useSystemMode ? 'bg-indigo-600 justify-end' : 'bg-gray-300 justify-start'} ${(!permissions.canSwitchMode || toggleLoading) ? 'opacity-50 cursor-not-allowed' : ''}`}
                                onClick={() => handleToggle(!useSystemMode)}
                                disabled={!permissions.canSwitchMode || toggleLoading}
                                aria-pressed={useSystemMode}
                            >
                                <span className="h-5 w-5 rounded-full bg-white shadow"></span>
                            </button>
                        </label>
                        {permissions.canSendTest ? (
                            <button
                                type="button"
                                className="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white"
                                style={{ background: '#2563eb' }}
                                onClick={() => {
                                    setTestModalOpen(true);
                                    setTestSuccess('');
                                    setTestError('');
                                }}
                            >
                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M4 4h16v16H4z" />
                                    <path d="m4 7 8 6 8-6" />
                                </svg>
                                Send Test Email
                            </button>
                        ) : null}
                    </div>
                </div>
                <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{payload.messages?.systemInfo}</p>
            </section>

            {statusMessage ? <Alert>{statusMessage}</Alert> : null}
            {errorMessage ? <Alert tone="danger">{errorMessage}</Alert> : null}
            {showInfo ? <Alert>{payload.messages?.systemInfo}</Alert> : null}
            {showCustomWarning && permissions.canManage ? (
                <Alert tone="warning">{payload.messages?.customWarning}</Alert>
            ) : null}

            {useSystemMode || !isAdminView
                ? renderReadOnlyFields(useSystemMode ? Boolean(readonlyValues.has_password) : Boolean(formValues.hasPassword))
                : renderEditableFields()}

            {!useSystemMode && permissions.canManage ? (
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        className="rounded-xl px-5 py-2.5 text-sm font-semibold text-white"
                        style={{ background: '#0f766e' }}
                        onClick={openConfirmSave}
                        disabled={saving}
                    >
                        {saving ? 'Saving…' : 'Save SMTP Settings'}
                    </button>
                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>A confirmation is required before switching to custom mode.</p>
                </div>
            ) : null}

            <AppModalPortal open={confirmOpen} onBackdropClick={() => setConfirmOpen(false)}>
                <div className="app-modal-panel w-full max-w-lg p-5" role="dialog" aria-modal="true">
                    <h3 className="text-lg font-bold">Activate Custom SMTP Settings</h3>
                    <p className="mt-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        Saving will encrypt credentials, activate database-based SMTP, and override the .env configuration. Continue?
                    </p>
                    <div className="mt-5 flex items-center justify-end gap-3">
                        <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold" onClick={() => setConfirmOpen(false)} style={{ border: '1px solid var(--hr-line)' }}>
                            Cancel
                        </button>
                        <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold text-white" style={{ background: '#0f766e' }} onClick={submitCustomSettings} disabled={saving}>
                            {saving ? 'Saving…' : 'Confirm & Save'}
                        </button>
                    </div>
                </div>
            </AppModalPortal>

            <AppModalPortal open={testModalOpen} onBackdropClick={() => setTestModalOpen(false)}>
                <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                    <h3 className="text-lg font-bold">Send Test Email</h3>
                    <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        Provide a recipient address to verify the currently active SMTP configuration.
                    </p>
                    <div className="flex flex-col gap-2 mt-4">
                        <label className="text-xs font-semibold uppercase tracking-[0.12em]" htmlFor="smtp-test-email" style={{ color: 'var(--hr-text-muted)' }}>
                            Recipient Email
                        </label>
                        <input
                            id="smtp-test-email"
                            type="email"
                            className="rounded-xl border px-3 py-2 text-sm"
                            style={{ borderColor: testError ? '#f87171' : 'var(--hr-line)' }}
                            value={testEmail}
                            onChange={(event) => setTestEmail(event.target.value)}
                            placeholder="admin@example.com"
                            disabled={testBusy}
                        />
                        {testError ? <p className="text-xs text-red-500">{testError}</p> : null}
                        {testSuccess ? <p className="text-xs text-green-600">{testSuccess}</p> : null}
                    </div>
                    <div className="mt-5 flex items-center justify-end gap-3">
                        <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold" onClick={() => setTestModalOpen(false)} style={{ border: '1px solid var(--hr-line)' }}>
                            Close
                        </button>
                        <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold text-white" style={{ background: '#2563eb' }} onClick={handleTestEmail} disabled={testBusy}>
                            {testBusy ? 'Sending…' : 'Send Email'}
                        </button>
                    </div>
                </div>
            </AppModalPortal>
        </div>
    );
}

import { mountSMTPSettings } from '../../pages/Settings/SMTPSettings';

export function mountSmtpSettingsPage() {
    // Delegate to the new structured SMTP settings page
    mountSMTPSettings();
}
