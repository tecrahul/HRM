import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import { AppModalPortal } from '../shared/AppModalPortal';

const parsePayload = (rootElement) => {
    const raw = rootElement?.dataset?.payload;
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return null;
    }
};

const toLabel = (value) => String(value ?? '').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const formatDate = (value) => {
    if (!value) {
        return 'Not set';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    }).format(date);
};

const statusTone = (status, required) => {
    if (status) {
        return {
            icon: 'check',
            label: 'Completed',
            color: '#166534',
            bg: 'linear-gradient(140deg, rgb(34 197 94 / 0.18), rgb(34 197 94 / 0.05))',
            border: 'rgb(34 197 94 / 0.34)',
            iconBg: 'rgb(34 197 94 / 0.18)',
        };
    }

    if (required) {
        return {
            icon: 'x',
            label: 'Required',
            color: '#991b1b',
            bg: 'linear-gradient(140deg, rgb(239 68 68 / 0.16), rgb(239 68 68 / 0.04))',
            border: 'rgb(239 68 68 / 0.34)',
            iconBg: 'rgb(239 68 68 / 0.14)',
        };
    }

    return {
        icon: 'warning',
        label: 'Missing',
        color: '#9a3412',
        bg: 'linear-gradient(140deg, rgb(249 115 22 / 0.15), rgb(249 115 22 / 0.04))',
        border: 'rgb(249 115 22 / 0.34)',
        iconBg: 'rgb(249 115 22 / 0.14)',
    };
};

const Icon = ({ name, className = 'h-5 w-5' }) => {
    if (name === 'check') {
        return (
            <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M20 6 9 17l-5-5" />
            </svg>
        );
    }

    if (name === 'warning') {
        return (
            <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
                <path d="M10.3 3.9 1.8 18.2a2 2 0 0 0 1.7 2.9h17a2 2 0 0 0 1.7-2.9L13.7 3.9a2 2 0 0 0-3.4 0Z" />
            </svg>
        );
    }

    if (name === 'x') {
        return (
            <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <path d="m15 9-6 6" />
                <path d="m9 9 6 6" />
            </svg>
        );
    }

    if (name === 'user') {
        return (
            <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="8" r="4" />
                <path d="M5.5 21a8.5 8.5 0 0 1 13 0" />
            </svg>
        );
    }

    if (name === 'mail') {
        return (
            <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M4 5h16v14H4z" />
                <path d="m4 7 8 6 8-6" />
            </svg>
        );
    }

    if (name === 'open') {
        return (
            <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M14 4h6v6" />
                <path d="M10 14 20 4" />
                <path d="M20 14v6H4V4h6" />
            </svg>
        );
    }

    return null;
};

function EmployeeOnboardingOverview({ payload }) {
    const employee = payload?.employee ?? {};
    const urls = payload?.urls ?? {};
    const meta = payload?.meta ?? {};

    const [setupStatus, setSetupStatus] = useState(payload?.setupStatus ?? null);
    const [setupLoading, setSetupLoading] = useState(false);
    const [setupError, setSetupError] = useState('');
    const [showSuccessHeader, setShowSuccessHeader] = useState(Boolean(payload?.ui?.showSuccessHeader));
    const [actionLoading, setActionLoading] = useState('');
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [sendingLogin, setSendingLogin] = useState(false);
    const [sendMessage, setSendMessage] = useState('');
    const [sendError, setSendError] = useState('');

    const checklist = useMemo(() => {
        const status = setupStatus ?? {};

        return [
            {
                key: 'salaryConfigured',
                label: 'Salary Structure Configured',
                done: Boolean(status.salaryConfigured),
                required: true,
                actionLabel: 'Add Structure',
                href: urls.salaryStructure,
                description: 'Set earnings and deductions before processing payroll.',
            },
            {
                key: 'payrollAssigned',
                label: 'Payroll Assigned',
                done: Boolean(status.payrollAssigned),
                required: true,
                actionLabel: 'Assign Payroll',
                href: urls.payrollProcessing,
                description: 'Include the employee in the current payroll run.',
            },
            {
                key: 'leavePolicyAssigned',
                label: 'Leave Policy Assigned',
                done: Boolean(status.leavePolicyAssigned),
                required: true,
                actionLabel: 'Assign Leave Policy',
                href: urls.leavePolicy,
                description: 'Attach the correct leave rules and entitlements.',
            },
            {
                key: 'documentsUploaded',
                label: 'Documents Uploaded',
                done: Boolean(status.documentsUploaded),
                required: true,
                actionLabel: 'Upload Documents',
                href: urls.documents,
                description: 'Upload required onboarding and compliance documents.',
            },
        ];
    }, [setupStatus, urls]);

    const missingRequiredCount = checklist.filter((item) => item.required && !item.done).length;

    const loadSetupStatus = async () => {
        if (!urls.setupStatusApi) {
            return;
        }

        setSetupLoading(true);
        setSetupError('');

        try {
            const { data } = await axios.get(urls.setupStatusApi);
            setSetupStatus(data ?? {});
        } catch (_error) {
            setSetupError('Unable to load setup status. Please retry.');
        } finally {
            setSetupLoading(false);
        }
    };

    useEffect(() => {
        loadSetupStatus();
    }, [urls.setupStatusApi]);

    const openExternal = (key, href) => {
        if (!href) {
            return;
        }

        setActionLoading(key);
        window.open(href, '_blank', 'noopener,noreferrer');
        window.setTimeout(() => {
            setActionLoading((current) => (current === key ? '' : current));
        }, 600);
    };

    const sendLoginCredentials = async () => {
        if (!urls.sendLoginApi || sendingLogin) {
            return;
        }

        setSendingLogin(true);
        setSendError('');
        setSendMessage('');

        try {
            const { data } = await axios.post(
                urls.sendLoginApi,
                {},
                {
                    headers: {
                        'X-CSRF-TOKEN': payload?.csrfToken,
                    },
                },
            );

            setSendMessage(String(data?.message ?? 'Login credentials sent successfully.'));
            setConfirmOpen(false);
            await loadSetupStatus();
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to send login credentials right now.';
            setSendError(String(message));
        } finally {
            setSendingLogin(false);
        }
    };

    const statusPill = String(employee.status || '').toLowerCase() === 'active'
        ? { label: 'Active', color: '#166534', bg: 'rgb(34 197 94 / 0.16)' }
        : { label: 'Pending', color: '#9a3412', bg: 'rgb(249 115 22 / 0.16)' };

    const initials = String(employee.name || 'NA')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('') || 'NA';

    return (
        <div className="space-y-6">
            {showSuccessHeader ? (
                <section className="hrm-modern-surface rounded-2xl p-6">
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex flex-wrap items-center gap-4">
                            <span
                                className="inline-flex h-14 w-14 items-center justify-center rounded-2xl"
                                style={{ background: 'rgb(34 197 94 / 0.18)', color: '#166534' }}
                            >
                                <Icon name="check" className="h-8 w-8" />
                            </span>
                            <div>
                                <h1 className="text-2xl font-extrabold" style={{ color: 'var(--hr-text-main)' }}>
                                    {meta.successTitle || 'Employee Created Successfully'}
                                </h1>
                                <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                                    {meta.successSubtitle || 'Complete remaining setup steps below'}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-2">
                            <button
                                type="button"
                                className="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold"
                                style={{
                                    borderColor: 'rgb(34 197 94 / 0.42)',
                                    color: '#166534',
                                    background: 'rgb(220 252 231 / 0.6)',
                                }}
                                onClick={() => setConfirmOpen(true)}
                                disabled={sendingLogin}
                            >
                                {sendingLogin ? (
                                    <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-emerald-300 border-t-emerald-700" />
                                ) : (
                                    <Icon name="mail" className="h-4 w-4" />
                                )}
                                Send Login Credentials
                            </button>
                            <button
                                type="button"
                                aria-label="Close success message"
                                className="inline-flex h-8 w-8 items-center justify-center rounded-lg border"
                                style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)', background: 'var(--hr-surface-strong)' }}
                                onClick={() => setShowSuccessHeader(false)}
                            >
                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="m18 6-12 12" />
                                    <path d="m6 6 12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </section>
            ) : null}

            <section className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <article className="hrm-modern-surface rounded-2xl p-6 xl:col-span-2">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="flex items-center gap-4">
                            {employee.avatarUrl ? (
                                <img
                                    src={employee.avatarUrl}
                                    alt={employee.name || 'Employee'}
                                    className="h-16 w-16 rounded-2xl border object-cover"
                                    style={{ borderColor: 'var(--hr-line)' }}
                                />
                            ) : (
                                <span
                                    className="inline-flex h-16 w-16 items-center justify-center rounded-2xl text-lg font-extrabold"
                                    style={{ background: 'var(--hr-accent-soft)', color: 'var(--hr-accent)' }}
                                >
                                    {initials}
                                </span>
                            )}
                            <div>
                                <h2 className="text-xl font-extrabold" style={{ color: 'var(--hr-text-main)' }}>{employee.name || 'N/A'}</h2>
                                <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>{employee.email || 'N/A'}</p>
                                <p className="mt-2 text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                    {employee.employeeCode || `EMP-${employee.id || ''}`}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <span
                                className="inline-flex rounded-full px-2.5 py-1 text-xs font-bold"
                                style={{ background: statusPill.bg, color: statusPill.color }}
                            >
                                {statusPill.label}
                            </span>
                            <button
                                type="button"
                                className="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold"
                                style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-main)' }}
                                onClick={() => openExternal('edit-details', urls.editDetails)}
                                disabled={actionLoading === 'edit-details'}
                            >
                                {actionLoading === 'edit-details' ? (
                                    <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-500" />
                                ) : (
                                    <Icon name="open" className="h-4 w-4" />
                                )}
                                Edit Details
                            </button>
                        </div>
                    </div>

                    <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)' }}>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Department</p>
                            <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{employee.department || 'Not set'}</p>
                        </div>
                        <div className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)' }}>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Branch</p>
                            <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{employee.branch || 'Not set'}</p>
                        </div>
                        <div className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)' }}>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Designation</p>
                            <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{employee.designation || 'Not set'}</p>
                        </div>
                        <div className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)' }}>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Joining Date</p>
                            <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{formatDate(employee.joiningDate)}</p>
                        </div>
                        <div className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)' }}>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Employment Type</p>
                            <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{toLabel(employee.employmentType) || 'Not set'}</p>
                        </div>
                        <div className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)' }}>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Employee ID</p>
                            <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{employee.employeeCode || 'N/A'}</p>
                        </div>
                    </div>
                </article>

                <article className="hrm-modern-surface rounded-2xl p-6">
                    <h3 className="text-base font-extrabold" style={{ color: 'var(--hr-text-main)' }}>Required Next Actions</h3>
                    <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        {missingRequiredCount > 0
                            ? `${missingRequiredCount} required setup item(s) still incomplete.`
                            : 'All required setup items are complete.'}
                    </p>

                    <div className="mt-4 space-y-2">
                        {checklist.filter((item) => !item.done).slice(0, 4).map((item) => (
                            <div key={`todo-${item.key}`} className="rounded-xl border px-3 py-2" style={{ borderColor: 'var(--hr-line)' }}>
                                <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-main)' }}>{item.label}</p>
                            </div>
                        ))}
                        {checklist.filter((item) => !item.done).length === 0 ? (
                            <div className="rounded-xl border px-3 py-2" style={{ borderColor: 'rgb(34 197 94 / 0.3)', background: 'rgb(34 197 94 / 0.08)' }}>
                                <p className="text-sm font-semibold" style={{ color: '#166534' }}>No pending setup actions.</p>
                            </div>
                        ) : null}
                    </div>
                </article>
            </section>

            <section className="hrm-modern-surface rounded-2xl p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 className="text-lg font-extrabold" style={{ color: 'var(--hr-text-main)' }}>Setup Progress Status</h3>
                        <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>Track completion and open setup tasks in new pages.</p>
                    </div>
                    <button
                        type="button"
                        className="rounded-xl border px-3 py-2 text-sm font-semibold"
                        style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-main)' }}
                        onClick={loadSetupStatus}
                        disabled={setupLoading}
                    >
                        {setupLoading ? 'Refreshing...' : 'Refresh Status'}
                    </button>
                </div>

                {setupError ? (
                    <p className="mt-3 rounded-xl border px-3 py-2 text-sm font-semibold text-red-600" style={{ borderColor: 'rgb(239 68 68 / 0.32)', background: 'rgb(254 226 226 / 0.5)' }}>
                        {setupError}
                    </p>
                ) : null}

                {sendMessage ? (
                    <p className="mt-3 rounded-xl border px-3 py-2 text-sm font-semibold" style={{ borderColor: 'rgb(34 197 94 / 0.32)', color: '#166534', background: 'rgb(220 252 231 / 0.55)' }}>
                        {sendMessage}
                    </p>
                ) : null}

                {sendError ? (
                    <p className="mt-3 rounded-xl border px-3 py-2 text-sm font-semibold text-red-600" style={{ borderColor: 'rgb(239 68 68 / 0.32)', background: 'rgb(254 226 226 / 0.5)' }}>
                        {sendError}
                    </p>
                ) : null}

                <div className="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
                    {checklist.map((item) => {
                        const tone = statusTone(item.done, item.required);

                        return (
                            <article
                                key={item.key}
                                className="rounded-2xl border p-4"
                                style={{
                                    borderColor: tone.border,
                                    background: tone.bg,
                                    boxShadow: '0 14px 28px -24px rgb(15 23 42 / 0.65)',
                                }}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span
                                                className="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                                                style={{ color: tone.color, background: tone.iconBg }}
                                            >
                                                <Icon name={tone.icon} className="h-4 w-4" />
                                            </span>
                                            <p className="text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{item.label}</p>
                                        </div>
                                        <p className="mt-2 text-xs leading-5" style={{ color: 'var(--hr-text-muted)' }}>
                                            {item.description}
                                        </p>
                                    </div>

                                    <span
                                        className="inline-flex shrink-0 rounded-full px-2 py-1 text-xs font-bold"
                                        style={{ background: 'var(--hr-surface-strong)', color: tone.color }}
                                    >
                                        {tone.label}
                                    </span>
                                </div>

                                {!item.done ? (
                                    <div className="mt-3">
                                        <button
                                            type="button"
                                            className="inline-flex items-center gap-2 rounded-lg border px-2.5 py-1.5 text-xs font-semibold"
                                            style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)', color: 'var(--hr-text-main)' }}
                                            onClick={() => openExternal(`check-${item.key}`, item.href)}
                                            disabled={actionLoading === `check-${item.key}`}
                                        >
                                            {actionLoading === `check-${item.key}` ? (
                                                <span className="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-500" />
                                            ) : (
                                                <Icon name="open" className="h-3.5 w-3.5" />
                                            )}
                                            {item.actionLabel}
                                        </button>
                                    </div>
                                ) : null}
                            </article>
                        );
                    })}
                </div>
            </section>

            <section className="hrm-modern-surface rounded-2xl p-6">
                <h3 className="text-lg font-extrabold" style={{ color: 'var(--hr-text-main)' }}>Quick Actions</h3>
                <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>Open setup pages in a new tab and complete onboarding faster.</p>

                <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {[
                        { key: 'quick-salary', label: 'Add Salary Structure', href: urls.salaryStructure, icon: 'open' },
                        { key: 'quick-payroll', label: 'Add to Payroll', href: urls.payrollProcessing, icon: 'open' },
                        { key: 'quick-leave', label: 'Assign Leave Policy', href: urls.leavePolicy, icon: 'open' },
                        { key: 'quick-bank', label: 'Add Bank Details', href: urls.bankDetails, icon: 'open' },
                        { key: 'quick-docs', label: 'Upload Documents', href: urls.documents, icon: 'open' },
                        { key: 'quick-profile', label: 'View Full Employee Profile', href: urls.viewFullProfile, icon: 'user' },
                    ].map((action) => (
                        <button
                            key={action.key}
                            type="button"
                            className="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left"
                            style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}
                            onClick={() => openExternal(action.key, action.href)}
                            disabled={actionLoading === action.key}
                        >
                            <span className="text-sm font-semibold" style={{ color: 'var(--hr-text-main)' }}>{action.label}</span>
                            {actionLoading === action.key ? (
                                <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-500" />
                            ) : (
                                <span style={{ color: 'var(--hr-text-muted)' }}><Icon name={action.icon} className="h-4 w-4" /></span>
                            )}
                        </button>
                    ))}
                </div>
            </section>

            <AppModalPortal open={confirmOpen} onBackdropClick={() => setConfirmOpen(false)}>
                <div className="app-modal-panel" role="dialog" aria-modal="true" aria-label="Send Login Credentials">
                    <div className="border-b px-5 py-4" style={{ borderColor: 'var(--hr-line)' }}>
                        <h4 className="text-base font-extrabold" style={{ color: 'var(--hr-text-main)' }}>Send Login Credentials</h4>
                        <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                            This will send login details to {employee.email || 'the employee'} and rotate the temporary password.
                        </p>
                    </div>
                    <div className="px-5 py-4 text-sm" style={{ color: 'var(--hr-text-main)' }}>
                        Continue with sending credentials now?
                    </div>
                    <div className="flex justify-end gap-2 border-t px-5 py-4" style={{ borderColor: 'var(--hr-line)' }}>
                        <button
                            type="button"
                            className="rounded-xl border px-3 py-2 text-sm font-semibold"
                            style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-main)' }}
                            onClick={() => setConfirmOpen(false)}
                            disabled={sendingLogin}
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            className="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-white"
                            style={{ background: 'linear-gradient(120deg, #16a34a, #15803d)' }}
                            onClick={sendLoginCredentials}
                            disabled={sendingLogin}
                        >
                            {sendingLogin ? (
                                <span className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-white" />
                            ) : (
                                <Icon name="mail" className="h-4 w-4" />
                            )}
                            {sendingLogin ? 'Sending...' : 'Send Credentials'}
                        </button>
                    </div>
                </div>
            </AppModalPortal>
        </div>
    );
}

export function mountEmployeeOnboardingOverview() {
    const rootElement = document.getElementById('employee-onboarding-overview-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        return;
    }

    createRoot(rootElement).render(<EmployeeOnboardingOverview payload={payload} />);
}
