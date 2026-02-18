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

export function InfoCard({ label, value, meta, tone = 'default' }) {
    const toneStyles = {
        default: { borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' },
        success: { borderColor: 'rgb(34 197 94 / 0.32)', background: 'rgb(34 197 94 / 0.08)' },
        warning: { borderColor: 'rgb(245 158 11 / 0.32)', background: 'rgb(245 158 11 / 0.09)' },
        danger: { borderColor: 'rgb(239 68 68 / 0.32)', background: 'rgb(239 68 68 / 0.08)' },
        info: { borderColor: 'rgb(59 130 246 / 0.32)', background: 'rgb(59 130 246 / 0.08)' },
    };

    const style = toneStyles[tone] ?? toneStyles.default;

    return (
        <article className="rounded-2xl border p-4" style={style}>
            <p className="text-[11px] font-bold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>{label}</p>
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
