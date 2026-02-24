import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { SMTPApi } from '../../services/SMTPApi';
import { SMTPModeCard } from '../../components/smtp/SMTPModeCard';
import { SMTPSystemView } from '../../components/smtp/SMTPSystemView';
import { SMTPCustomForm } from '../../components/smtp/SMTPCustomForm';
import { SMTPTestModal } from '../../components/smtp/SMTPTestModal';
import { AppModalPortal } from '../../components/shared/AppModalPortal';

const parsePayload = (node) => {
    if (!node) return null;
    const raw = node.dataset.payload;
    if (!raw) return null;
    try { return JSON.parse(raw); } catch { return null; }
};

export function SMTPSettingsPage({ payload }) {
    const permissions = payload?.permissions ?? {};
    const routes = payload?.routes ?? {};
    const api = useMemo(() => new SMTPApi({ routes, csrfToken: payload?.csrfToken || '' }), [routes, payload?.csrfToken]);

    const [mode, setMode] = useState(payload?.mode === 'custom' ? 'custom' : 'system');
    const [useSystemMode, setUseSystemMode] = useState(mode !== 'custom');
    const [systemConfig, setSystemConfig] = useState(payload?.systemConfig ?? {});
    const [customConfig, setCustomConfig] = useState(() => ({
        mail_driver: payload?.customConfig?.mail_driver || 'smtp',
        mail_encryption: payload?.customConfig?.mail_encryption || '',
        mail_host: payload?.customConfig?.mail_host || '',
        mail_port: String(payload?.customConfig?.mail_port || 587),
        mail_username: payload?.customConfig?.mail_username || '',
        mail_password: '',
        from_address: payload?.customConfig?.from_address || '',
        from_name: payload?.customConfig?.from_name || '',
        hasPassword: Boolean(payload?.customConfig?.has_password),
    }));
    const [baselineCustom, setBaselineCustom] = useState(() => ({
        mail_driver: payload?.customConfig?.mail_driver || 'smtp',
        mail_encryption: payload?.customConfig?.mail_encryption || '',
        mail_host: payload?.customConfig?.mail_host || '',
        mail_port: String(payload?.customConfig?.mail_port || 587),
        mail_username: payload?.customConfig?.mail_username || '',
        from_address: payload?.customConfig?.from_address || '',
        from_name: payload?.customConfig?.from_name || '',
    }));
    const [saving, setSaving] = useState(false);
    const [testing, setTesting] = useState(false);
    const [toggleBusy, setToggleBusy] = useState(false);
    const [testModalOpen, setTestModalOpen] = useState(false);
    const [testError, setTestError] = useState('');
    const [testSuccess, setTestSuccess] = useState('');
    const [errors, setErrors] = useState({});
    const [confirmToggle, setConfirmToggle] = useState(null); // 'toSystem' | 'toCustom' | null

    const lastUpdatedAt = payload?.customConfig?.updated_at || null;
    const configuredBy = payload?.customConfig?.configured_by || null;

    const handleToggle = (nextUseSystem) => {
        if (!permissions.canSwitchMode || toggleBusy) return;
        if (nextUseSystem === useSystemMode) return;
        setConfirmToggle(nextUseSystem ? 'toSystem' : 'toCustom');
    };

    const applyToggle = async () => {
        if (confirmToggle === 'toSystem') {
            try {
                setToggleBusy(true);
                const data = await api.activateSystem();
                setUseSystemMode(true);
                setMode('system');
                if (data?.config) setSystemConfig(data.config);
            } catch {
                // leave as is; optional toast could be added
            } finally {
                setToggleBusy(false);
                setConfirmToggle(null);
            }
            return;
        }

        // Switching to custom just flips client-side; save activates
        setUseSystemMode(false);
        setMode('custom');
        setConfirmToggle(null);
    };

    const handleField = (field, value) => {
        setCustomConfig((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => {
            if (!prev[field]) return prev;
            const next = { ...prev }; delete next[field]; return next;
        });
    };

    const handleSave = async () => {
        setSaving(true);
        setErrors({});
        try {
            const payloadBody = {
                mail_driver: customConfig.mail_driver,
                mail_host: customConfig.mail_host,
                mail_port: customConfig.mail_port,
                mail_username: customConfig.mail_username || null,
                mail_password: customConfig.mail_password || undefined,
                mail_encryption: customConfig.mail_encryption || null,
                from_address: customConfig.from_address,
                from_name: customConfig.from_name || null,
            };
            const data = await api.saveCustom(payloadBody);
            const cfg = data?.config ?? {};
            setMode('custom');
            setUseSystemMode(false);
            setCustomConfig((prev) => ({
                ...prev,
                mail_driver: cfg.mail_driver || prev.mail_driver,
                mail_encryption: cfg.mail_encryption ?? prev.mail_encryption,
                mail_host: cfg.mail_host || prev.mail_host,
                mail_port: String(cfg.mail_port ?? prev.mail_port),
                mail_username: cfg.mail_username ?? prev.mail_username,
                from_address: cfg.from_address ?? prev.from_address,
                from_name: cfg.from_name ?? prev.from_name,
                mail_password: '',
                hasPassword: Boolean(cfg.has_password),
            }));
            setBaselineCustom({
                mail_driver: cfg.mail_driver || 'smtp',
                mail_encryption: cfg.mail_encryption || '',
                mail_host: cfg.mail_host || '',
                mail_port: String(cfg.mail_port || 587),
                mail_username: cfg.mail_username || '',
                from_address: cfg.from_address || '',
                from_name: cfg.from_name || '',
            });
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                const serverErrors = Object.entries(error.response.data.errors).reduce((acc, [field, messages]) => ({
                    ...acc,
                    [field]: Array.isArray(messages) ? messages[0] : messages,
                }), {});
                setErrors(serverErrors);
            }
        } finally {
            setSaving(false);
        }
    };

    const openTest = () => {
        setTestError('');
        setTestSuccess('');
        setTestModalOpen(true);
    };

    const sendTest = async (email) => {
        setTesting(true);
        setTestError('');
        setTestSuccess('');
        try {
            const data = await api.sendTestEmail(email);
            setTestSuccess(data?.message || 'Test email sent.');
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setTestError(error.response.data.errors.recipient?.[0] || 'Invalid recipient email.');
            } else {
                setTestError('Unable to send test email.');
            }
        } finally {
            setTesting(false);
        }
    };

    return (
        <div className="flex flex-col gap-4">
            <SMTPModeCard useSystem={useSystemMode} onToggle={handleToggle} busy={toggleBusy} renderToggle={false} />

            {useSystemMode ? (
                <SMTPSystemView
                    config={systemConfig}
                    onTestClick={openTest}
                    testing={testing}
                    useSystem={useSystemMode}
                    onToggle={handleToggle}
                    toggleBusy={toggleBusy}
                />
            ) : (
                <SMTPCustomForm
                    values={customConfig}
                    errors={errors}
                    onChange={handleField}
                    onSave={handleSave}
                    onTest={openTest}
                    saving={saving}
                    testing={testing}
                    baseline={baselineCustom}
                    lastUpdatedAt={lastUpdatedAt}
                    configuredBy={configuredBy}
                />
            )}

            <SMTPTestModal
                open={testModalOpen}
                onClose={() => setTestModalOpen(false)}
                onSend={sendTest}
                busy={testing}
                error={testError}
                success={testSuccess}
            />

            <AppModalPortal open={Boolean(confirmToggle)} onBackdropClick={() => setConfirmToggle(null)}>
                <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                    <h3 className="text-lg font-extrabold">Confirm Mode Switch</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        {confirmToggle === 'toSystem'
                            ? 'Switch to System mode and use .env configuration?'
                            : 'Switch to Custom mode to configure SMTP in the app?'}
                    </p>
                    <div className="mt-5 flex items-center justify-end gap-3">
                        <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold" onClick={() => setConfirmToggle(null)} style={{ border: '1px solid var(--hr-line)' }}>
                            Cancel
                        </button>
                        <button
                            type="button"
                            className="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                            style={{ background: '#0f766e' }}
                            onClick={applyToggle}
                            disabled={toggleBusy}
                        >
                            Confirm
                        </button>
                    </div>
                </div>
            </AppModalPortal>
        </div>
    );
}

export function mountSMTPSettings() {
    const rootElement = document.getElementById('smtp-settings-root');
    if (!rootElement) return;
    const payload = parsePayload(rootElement);
    if (!payload) return;
    createRoot(rootElement).render(<SMTPSettingsPage payload={payload} />);
}
