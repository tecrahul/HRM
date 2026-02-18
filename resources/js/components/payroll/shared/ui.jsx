import React, { useEffect, useMemo, useState } from 'react';
import { EmployeeAutocomplete } from '../../EmployeeAutocomplete';
import { payrollApi } from '../api';
import { AppModalPortal } from '../../shared/AppModalPortal';

export const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export const countFormatter = new Intl.NumberFormat();

export const formatMoney = (value) => moneyFormatter.format(Number(value ?? 0));
export const formatCount = (value) => countFormatter.format(Number(value ?? 0));

export const formatDateTime = (value) => {
    if (!value) {
        return 'N/A';
    }

    try {
        return new Date(value).toLocaleString();
    } catch (_error) {
        return 'N/A';
    }
};

export const useDebouncedValue = (value, delay = 300) => {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setDebounced(value);
        }, delay);

        return () => window.clearTimeout(timer);
    }, [value, delay]);

    return debounced;
};

export function SectionHeader({ title, subtitle, actions = null }) {
    return (
        <div className="ui-section-head">
            <div>
                <h3 className="ui-section-title">{title}</h3>
                {subtitle ? <p className="ui-section-subtitle">{subtitle}</p> : null}
            </div>
            {actions}
        </div>
    );
}

export function InfoCard({ label, value, meta, tone = 'default', icon = 'status' }) {
    const toneStyles = {
        default: { borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' },
        success: { borderColor: 'rgb(34 197 94 / 0.32)', background: 'rgb(34 197 94 / 0.08)' },
        warning: { borderColor: 'rgb(245 158 11 / 0.32)', background: 'rgb(245 158 11 / 0.09)' },
        danger: { borderColor: 'rgb(239 68 68 / 0.32)', background: 'rgb(239 68 68 / 0.08)' },
        info: { borderColor: 'rgb(59 130 246 / 0.32)', background: 'rgb(59 130 246 / 0.08)' },
    };

    const style = toneStyles[tone] ?? toneStyles.default;

    const iconMap = {
        users: (
            <path d="M17 21v-2.2a3.8 3.8 0 0 0-3-3.7M7 21v-2.2a3.8 3.8 0 0 1 3-3.7M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M20 8v6M23 11h-6" />
        ),
        warning: (
            <>
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
                <path d="m10.3 3.9-8 14a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0z" />
            </>
        ),
        status: (
            <>
                <path d="M5 12h4" />
                <path d="M15 12h4" />
                <path d="M12 5v4" />
                <path d="M12 15v4" />
                <circle cx="12" cy="12" r="2.5" />
            </>
        ),
        clock: (
            <>
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7v5l3 2" />
            </>
        ),
        money: (
            <>
                <rect x="2" y="5" width="20" height="14" rx="2" />
                <path d="M2 10h20" />
                <path d="M12 14h.01" />
            </>
        ),
        calendar: (
            <>
                <path d="M8 2v3M16 2v3" />
                <rect x="3" y="5" width="18" height="16" rx="2" />
                <path d="M3 10h18" />
            </>
        ),
        chart: (
            <>
                <path d="M3 3v18h18" />
                <path d="M7 14l4-4 3 3 5-6" />
            </>
        ),
        bank: (
            <>
                <path d="M3 10h18" />
                <path d="M5 10v8M9 10v8M15 10v8M19 10v8" />
                <path d="M12 3 3 7h18l-9-4z" />
                <path d="M3 18h18" />
            </>
        ),
        shield: (
            <>
                <path d="M12 3 4 6v6c0 5.2 3.2 8 8 9 4.8-1 8-3.8 8-9V6l-8-3z" />
                <path d="m9 12 2 2 4-4" />
            </>
        ),
    };

    const toneIconColors = {
        default: { background: 'rgb(100 116 139 / 0.14)', color: '#475569' },
        success: { background: 'rgb(34 197 94 / 0.14)', color: '#15803d' },
        warning: { background: 'rgb(245 158 11 / 0.16)', color: '#b45309' },
        danger: { background: 'rgb(239 68 68 / 0.16)', color: '#b91c1c' },
        info: { background: 'rgb(59 130 246 / 0.16)', color: '#1d4ed8' },
    };
    const iconStyle = toneIconColors[tone] ?? toneIconColors.default;
    const resolvedIcon = iconMap[icon] ?? iconMap.status;

    return (
        <article className="rounded-2xl border p-4" style={style}>
            <div className="flex items-start justify-between gap-2">
                <p className="text-[11px] font-bold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>{label}</p>
                <span
                    className="inline-flex h-8 w-8 items-center justify-center rounded-lg"
                    style={iconStyle}
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        {resolvedIcon}
                    </svg>
                </span>
            </div>
            <p className="mt-2 text-2xl font-extrabold" style={{ color: 'var(--hr-text-main)' }}>{value}</p>
            {meta ? <p className="mt-1 text-xs" style={{ color: 'var(--hr-text-muted)' }}>{meta}</p> : null}
        </article>
    );
}

export function StatusBadge({ status }) {
    const value = String(status ?? '').toLowerCase();
    const map = {
        generated: 'ui-status-slate',
        draft: 'ui-status-slate',
        approved: 'ui-status-amber',
        processed: 'ui-status-amber',
        paid: 'ui-status-green',
        failed: 'ui-status-red',
    };

    const cssClass = map[value] ?? 'ui-status-slate';
    const label = value.replace('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) || 'Unknown';

    return <span className={`ui-status-chip ${cssClass}`}>{label}</span>;
}

export function HorizontalStepper({
    steps,
    activeStep,
    completedStep = 0,
    maxAccessibleStep = 1,
    onStepChange,
}) {
    return (
        <nav aria-label="Payroll processing steps">
            <ol role="list" className="mt-4 flex flex-col gap-3 md:flex-row md:gap-0">
                {steps.map((step, index) => {
                    const isActive = step.id === activeStep;
                    const isCompleted = completedStep >= step.id && !isActive;
                    const isPending = !isActive && !isCompleted;
                    const isFutureLocked = step.id > maxAccessibleStep;
                    const connectorComplete = completedStep >= step.id;
                    const stateLabel = isCompleted ? 'Completed' : isActive ? 'In Progress' : 'Pending';

                    return (
                        <li key={`stepper-${step.id}`} className="relative min-w-0 pb-3 md:flex-1 md:pb-0">
                            {index < steps.length - 1 ? (
                                <span
                                    aria-hidden="true"
                                    className="absolute left-4 top-9 z-0 h-[calc(100%-1rem)] w-px md:left-[calc(50%+1.25rem)] md:top-4 md:h-px md:w-[calc(100%-2.5rem)]"
                                    style={{ background: connectorComplete ? '#16a34a' : 'var(--hr-line)' }}
                                />
                            ) : null}

                            <button
                                type="button"
                                className="relative z-[1] flex w-full items-start gap-3 text-left md:flex-col md:items-center md:text-center"
                                onClick={() => {
                                    if (isFutureLocked) {
                                        return;
                                    }
                                    onStepChange(step.id);
                                }}
                                disabled={isFutureLocked}
                                aria-current={isActive ? 'step' : undefined}
                                aria-disabled={isFutureLocked ? 'true' : 'false'}
                                style={{ opacity: isFutureLocked ? 0.65 : 1, cursor: isFutureLocked ? 'not-allowed' : 'pointer' }}
                            >
                                <span
                                    className="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full border text-xs font-extrabold transition-colors"
                                    style={{
                                        borderColor: isCompleted ? '#16a34a' : isActive ? 'var(--hr-accent-border)' : 'var(--hr-line)',
                                        background: isCompleted ? '#16a34a' : isActive ? 'var(--hr-accent-soft)' : 'var(--hr-surface-strong)',
                                        color: isCompleted ? '#fff' : isActive ? 'var(--hr-text-main)' : 'var(--hr-text-muted)',
                                    }}
                                >
                                    {isCompleted ? (
                                        <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                            <path d="m5 12 4 4 10-10" />
                                        </svg>
                                    ) : (
                                        step.id
                                    )}
                                </span>

                                <span>
                                    <span
                                        className="text-sm"
                                        style={{
                                            fontWeight: isActive ? 800 : 700,
                                            color: isActive ? 'var(--hr-text-main)' : 'var(--hr-text-muted)',
                                        }}
                                    >
                                        {step.title}
                                    </span>
                                    <span
                                        className="block text-xs font-semibold uppercase tracking-[0.08em]"
                                        style={{ color: isCompleted ? '#15803d' : isActive ? 'var(--hr-accent)' : 'var(--hr-text-muted)' }}
                                    >
                                        {stateLabel}
                                    </span>
                                    {isPending ? (
                                        <span className="sr-only">Pending step</span>
                                    ) : null}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}

export function GlobalFilterBar({
    urls,
    filters,
    onChange,
    disabled = false,
    showEmployee = true,
    employee = null,
    onClear,
    employeePlaceholder = 'Search employee by name or email...',
}) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [branches, setBranches] = useState([]);
    const [departments, setDepartments] = useState([]);

    const branchId = String(filters.branchId || '');
    const departmentId = String(filters.departmentId || '');

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        setError('');

        payrollApi.getBranches(urls.branches)
            .then((data) => {
                if (!mounted) {
                    return;
                }

                setBranches(Array.isArray(data?.branches) ? data.branches : []);
            })
            .catch(() => {
                if (!mounted) {
                    return;
                }

                setBranches([]);
                setError('Unable to load branches.');
            })
            .finally(() => {
                if (mounted) {
                    setLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [urls.branches]);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        setError('');

        payrollApi.getDepartments(urls.departments, {
            branch_id: branchId || undefined,
        })
            .then((data) => {
                if (!mounted) {
                    return;
                }

                const nextDepartments = Array.isArray(data?.departments) ? data.departments : [];
                setDepartments(nextDepartments);

                if (departmentId && !nextDepartments.some((item) => String(item.id) === departmentId)) {
                    onChange({ departmentId: '', employeeId: '', employee: null });
                }
            })
            .catch(() => {
                if (!mounted) {
                    return;
                }

                setDepartments([]);
                setError('Unable to load departments.');
            })
            .finally(() => {
                if (mounted) {
                    setLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [urls.departments, branchId, departmentId, onChange]);

    const employeeSearchParams = useMemo(() => ({
        branch_id: branchId || undefined,
        department_id: departmentId || undefined,
    }), [branchId, departmentId]);

    return (
        <section className="ui-section">
            <SectionHeader
                title="Global Filters"
                subtitle="Filter payroll data by branch, department, and employee."
                actions={
                    <button
                        type="button"
                        className="ui-btn ui-btn-ghost"
                        onClick={onClear}
                        disabled={disabled}
                    >
                        Clear Filters
                    </button>
                }
            />

            {error ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}

            <div className="mt-4 grid gap-3 md:grid-cols-4">
                <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                    Branch
                    <select
                        className="ui-select mt-1"
                        value={branchId}
                        onChange={(event) => onChange({
                            branchId: event.target.value,
                            departmentId: '',
                            employeeId: '',
                            employee: null,
                        })}
                        disabled={disabled || loading}
                    >
                        <option value="">All Branches</option>
                        {branches.map((branch) => (
                            <option key={`branch-${branch.id}`} value={branch.id}>{branch.name}</option>
                        ))}
                    </select>
                </label>

                <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                    Department
                    <select
                        className="ui-select mt-1"
                        value={departmentId}
                        onChange={(event) => onChange({
                            departmentId: event.target.value,
                            employeeId: '',
                            employee: null,
                        })}
                        disabled={disabled || loading}
                    >
                        <option value="">All Departments</option>
                        {departments.map((department) => (
                            <option key={`department-${department.id}`} value={department.id}>{department.name}</option>
                        ))}
                    </select>
                </label>

                {showEmployee ? (
                    <label className="text-xs font-semibold uppercase tracking-[0.08em] md:col-span-2" style={{ color: 'var(--hr-text-muted)' }}>
                        Employee
                        <div className="mt-1">
                            <EmployeeAutocomplete
                                apiUrl={urls.employeeSearch}
                                placeholder={employeePlaceholder}
                                selectedEmployee={employee}
                                onSelect={(selectedEmployee) => {
                                    onChange({
                                        employeeId: selectedEmployee ? String(selectedEmployee.id) : '',
                                        employee: selectedEmployee,
                                    });
                                }}
                                disabled={disabled}
                                searchParams={employeeSearchParams}
                                allowClear
                            />
                        </div>
                    </label>
                ) : null}
            </div>
        </section>
    );
}

export function TableEmptyState({ loading, error, colSpan, emptyMessage = 'No records found.' }) {
    if (loading) {
        return (
            <tr>
                <td colSpan={colSpan} className="ui-empty">Loading...</td>
            </tr>
        );
    }

    if (error) {
        return (
            <tr>
                <td colSpan={colSpan} className="ui-empty text-red-600">{error}</td>
            </tr>
        );
    }

    return (
        <tr>
            <td colSpan={colSpan} className="ui-empty">{emptyMessage}</td>
        </tr>
    );
}

export function ConfirmModal({
    open,
    title,
    body,
    confirmLabel,
    onCancel,
    onConfirm,
    busy = false,
    tone = 'default',
}) {
    const toneStyles = {
        default: 'var(--hr-accent-soft)',
        warning: 'rgb(245 158 11 / 0.12)',
        danger: 'rgb(239 68 68 / 0.12)',
    };

    return (
        <AppModalPortal open={open} onBackdropClick={busy ? null : onCancel}>
            <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                <h4 className="text-lg font-extrabold">{title}</h4>
                <p className="mt-2 rounded-xl p-3 text-sm" style={{ background: toneStyles[tone] ?? toneStyles.default, color: 'var(--hr-text-main)' }}>
                    {body}
                </p>
                <div className="mt-4 flex items-center justify-end gap-2">
                    <button type="button" className="ui-btn ui-btn-ghost" onClick={onCancel} disabled={busy}>Cancel</button>
                    <button type="button" className="ui-btn ui-btn-primary" onClick={onConfirm} disabled={busy}>
                        {busy ? 'Please wait...' : confirmLabel}
                    </button>
                </div>
            </div>
        </AppModalPortal>
    );
}
