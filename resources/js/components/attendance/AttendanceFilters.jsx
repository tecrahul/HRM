import React, { useEffect, useMemo, useState } from 'react';
import { GlobalDateRangePicker } from '../common/GlobalDateRangePicker';

const TODAY = new Date().toISOString().slice(0, 10);
const MONTH_START = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);

const DEFAULT_FILTERS = {
    status: '',
    approval_status: '',
    attendance_date: TODAY,
    use_date_range: '1',
    date_from: MONTH_START,
    date_to: TODAY,
    range_mode: 'absolute',
    range_preset: '',
    department: '',
    branch: '',
    employee_id: '',
};

const inputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';
const iconInputClassName = 'w-full rounded-xl border py-2.5 pl-9 pr-9 text-sm bg-transparent';

function SearchIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
        </svg>
    );
}

function FilterIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M3 5h18" />
            <path d="M6 12h12" />
            <path d="M10 19h4" />
        </svg>
    );
}

function XIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M6 18L18 6M6 6l12 12" />
        </svg>
    );
}

function DownloadIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M12 3v12" />
            <path d="M12 15l4-4" />
            <path d="M12 15l-4-4" />
            <path d="M4 21h16" />
        </svg>
    );
}

function LockClosedIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M7 10V8a5 5 0 0110 0v2" />
            <rect x="5" y="10" width="14" height="10" rx="2" />
        </svg>
    );
}

function LockOpenIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M8 10V8a5 5 0 119 0" />
            <rect x="5" y="10" width="14" height="10" rx="2" />
        </svg>
    );
}

function FilterField({ id, label, children, className = '' }) {
    return (
        <div className={className}>
            <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }} htmlFor={id}>
                {label}
            </label>
            {children}
        </div>
    );
}

export function AttendanceFilters({
    filters,
    options,
    permissions,
    capabilities,
    loading,
    submitting,
    selectedMonthLocked,
    onFilterChange,
    onApply,
    onClear,
    onLockMonth,
    onUnlockMonth,
    onSearchEmployees,
}) {
    const access = permissions ?? capabilities ?? {};
    const canViewAll = Boolean(access?.canViewAll);
    const canViewDepartment = Boolean(access?.canViewDepartment);
    const canBranchFilter = canViewAll;
    const canDepartmentFilter = canViewAll || canViewDepartment;
    const canEmployeeFilter = canViewAll || canViewDepartment;
    const canExport = Boolean(access?.canExport);
    const canLockMonth = Boolean(access?.canLockMonth);
    const canUnlockMonth = Boolean(access?.canUnlockMonth);

    const [localFilters, setLocalFilters] = useState({
        ...DEFAULT_FILTERS,
        ...filters,
    });
    const [employeeKeyword, setEmployeeKeyword] = useState('');
    const [employeeOptions, setEmployeeOptions] = useState([]);
    const [employeeLoading, setEmployeeLoading] = useState(false);

    const employeeDisabled = canEmployeeFilter && localFilters.department === '';

    useEffect(() => {
        setLocalFilters({
            ...DEFAULT_FILTERS,
            ...filters,
        });

        if (!filters.employee_id) {
            setEmployeeKeyword('');
        }
    }, [filters]);

    useEffect(() => {
        if (!canEmployeeFilter || employeeDisabled) {
            setEmployeeOptions([]);
            return undefined;
        }

        const query = employeeKeyword.trim();
        if (query.length < 2) {
            setEmployeeOptions([]);
            return undefined;
        }

        let cancelled = false;
        const timer = window.setTimeout(async () => {
            setEmployeeLoading(true);
            try {
                const result = await onSearchEmployees(query, {
                    department: localFilters.department,
                    branch: localFilters.branch,
                });
                if (!cancelled) {
                    setEmployeeOptions(Array.isArray(result) ? result : []);
                }
            } catch (_error) {
                if (!cancelled) {
                    setEmployeeOptions([]);
                }
            } finally {
                if (!cancelled) {
                    setEmployeeLoading(false);
                }
            }
        }, 250);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [
        canEmployeeFilter,
        employeeDisabled,
        employeeKeyword,
        localFilters.branch,
        localFilters.department,
        onSearchEmployees,
    ]);

    const selectedMonth = useMemo(() => {
        const sourceDate = localFilters.date_from || localFilters.date_to || TODAY;
        return sourceDate.slice(0, 7);
    }, [localFilters.date_from, localFilters.date_to]);

    const syncFilters = (nextFilters) => {
        setLocalFilters(nextFilters);
        if (typeof onFilterChange === 'function') {
            onFilterChange(nextFilters);
        }
    };

    const updateField = (field, value) => {
        setLocalFilters((prev) => {
            const next = {
                ...prev,
                [field]: value,
            };

            if (field === 'branch') {
                next.department = '';
                next.employee_id = '';
                setEmployeeKeyword('');
                setEmployeeOptions([]);
            }

            if (field === 'department') {
                next.employee_id = '';
                setEmployeeKeyword('');
                setEmployeeOptions([]);
            }

            if (typeof onFilterChange === 'function') {
                onFilterChange(next);
            }

            return next;
        });
    };

    const normalizedFilters = useMemo(() => {
        const next = { ...localFilters };
        const seed = next.attendance_date || TODAY;
        const from = next.date_from || seed;
        const to = next.date_to || from;
        next.use_date_range = '1';
        next.date_from = from;
        next.date_to = to >= from ? to : from;
        next.attendance_date = next.date_from || seed;
        next.range_mode = 'absolute';
        next.range_preset = '';

        if (!canBranchFilter) {
            next.branch = '';
        }

        if (!canDepartmentFilter) {
            next.department = '';
        }

        if (!canEmployeeFilter || next.department === '') {
            next.employee_id = '';
        }

        return next;
    }, [canBranchFilter, canDepartmentFilter, canEmployeeFilter, localFilters]);

    const applyFilters = (event) => {
        event.preventDefault();
        syncFilters(normalizedFilters);
        onApply(normalizedFilters);
    };

    const clearFilters = () => {
        setEmployeeKeyword('');
        setEmployeeOptions([]);
        syncFilters({ ...DEFAULT_FILTERS });
        onClear();
    };

    const orgFieldCount = [canBranchFilter, canDepartmentFilter, canEmployeeFilter].filter(Boolean).length;
    const orgSpan = orgFieldCount === 1 ? 'xl:col-span-12' : orgFieldCount === 2 ? 'xl:col-span-6' : 'xl:col-span-4';

    return (
        <article className="hrm-modern-surface rounded-2xl p-4 shadow-sm">
            <form className="space-y-4" onSubmit={applyFilters}>
                <div>
                    <div>
                        <h3 className="text-sm font-extrabold uppercase tracking-[0.08em]">Advance Search</h3>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-3">
                    <div className="xl:col-span-8">
                        <GlobalDateRangePicker
                            idPrefix="attendance_filter"
                            value={localFilters}
                            defaultMode="absolute"
                            defaultFrom={MONTH_START}
                            defaultTo={TODAY}
                            showModeToggle={false}
                            forceMode="absolute"
                            toBeforeFromStrategy="clamp_to_from"
                            disabled={loading || submitting}
                            onChange={(rangeValues) => {
                                setLocalFilters((prev) => {
                                    const next = {
                                        ...prev,
                                        ...rangeValues,
                                    };

                                    if (typeof onFilterChange === 'function') {
                                        onFilterChange(next);
                                    }

                                    return next;
                                });
                            }}
                        />
                    </div>

                    <FilterField id="attendance_filter_status" label="Status" className="xl:col-span-2">
                        <select
                            id="attendance_filter_status"
                            className={inputClassName}
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={localFilters.status || ''}
                            onChange={(event) => updateField('status', event.target.value)}
                            disabled={loading || submitting}
                        >
                            <option value="">All Status</option>
                            {(options?.statuses ?? []).map((statusOption) => (
                                <option key={statusOption.value} value={statusOption.value}>{statusOption.label}</option>
                            ))}
                        </select>
                    </FilterField>

                    <FilterField id="attendance_filter_approval_status" label="Approval Status" className="xl:col-span-2">
                        <select
                            id="attendance_filter_approval_status"
                            className={inputClassName}
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={localFilters.approval_status || ''}
                            onChange={(event) => updateField('approval_status', event.target.value)}
                            disabled={loading || submitting}
                        >
                            <option value="">All Approval</option>
                            {(options?.approvalStatuses ?? []).map((statusOption) => (
                                <option key={statusOption.value} value={statusOption.value}>{statusOption.label}</option>
                            ))}
                        </select>
                    </FilterField>
                </div>

                {orgFieldCount > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-3">
                        {canBranchFilter ? (
                            <FilterField id="attendance_filter_branch" label="Branch" className={orgSpan}>
                                <select
                                    id="attendance_filter_branch"
                                    className={inputClassName}
                                    style={{ borderColor: 'var(--hr-line)' }}
                                    value={localFilters.branch || ''}
                                    onChange={(event) => updateField('branch', event.target.value)}
                                    disabled={loading || submitting}
                                >
                                    <option value="">All Branches</option>
                                    {(options?.branches ?? []).map((branch) => (
                                        <option key={branch} value={branch}>{branch}</option>
                                    ))}
                                </select>
                            </FilterField>
                        ) : null}

                        {canDepartmentFilter ? (
                            <FilterField id="attendance_filter_department" label="Department" className={orgSpan}>
                                <select
                                    id="attendance_filter_department"
                                    className={inputClassName}
                                    style={{ borderColor: 'var(--hr-line)' }}
                                    value={localFilters.department || ''}
                                    onChange={(event) => updateField('department', event.target.value)}
                                    disabled={loading || submitting}
                                >
                                    <option value="">All Departments</option>
                                    {(options?.departments ?? []).map((department) => (
                                        <option key={department} value={department}>{department}</option>
                                    ))}
                                </select>
                            </FilterField>
                        ) : null}

                        {canEmployeeFilter ? (
                            <FilterField id="attendance_filter_employee" label="Employee" className={orgSpan}>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2" style={{ color: 'var(--hr-text-muted)' }}>
                                        <SearchIcon />
                                    </span>
                                    <input
                                        id="attendance_filter_employee"
                                        type="text"
                                        className={iconInputClassName}
                                        style={{ borderColor: 'var(--hr-line)' }}
                                        placeholder={employeeDisabled ? 'Select department first' : 'Search employee (min 2 chars)'}
                                        value={employeeKeyword}
                                        onChange={(event) => {
                                            setEmployeeKeyword(event.target.value);
                                            updateField('employee_id', '');
                                        }}
                                        disabled={loading || submitting || employeeDisabled}
                                        autoComplete="off"
                                    />
                                    {employeeKeyword !== '' ? (
                                        <button
                                            type="button"
                                            className="absolute right-2 top-1/2 -translate-y-1/2 h-6 w-6 rounded-full text-xs"
                                            style={{ color: 'var(--hr-text-muted)' }}
                                            onClick={() => {
                                                setEmployeeKeyword('');
                                                setEmployeeOptions([]);
                                                updateField('employee_id', '');
                                            }}
                                            aria-label="Clear employee"
                                        >
                                            ×
                                        </button>
                                    ) : null}
                                </div>

                                {employeeDisabled ? (
                                    <p className="mt-1 text-[11px]" style={{ color: 'var(--hr-text-muted)' }}>
                                        Department is required before employee search.
                                    </p>
                                ) : null}
                                {employeeLoading ? (
                                    <p className="mt-1 text-[11px]" style={{ color: 'var(--hr-text-muted)' }}>
                                        Searching employees...
                                    </p>
                                ) : null}

                                {employeeOptions.length > 0 && localFilters.employee_id === '' ? (
                                    <div className="mt-1 max-h-48 overflow-auto rounded-xl border p-1" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                                        {employeeOptions.map((employee) => (
                                            <button
                                                key={employee.id}
                                                type="button"
                                                className="w-full rounded-lg px-2 py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-700"
                                                onClick={() => {
                                                    updateField('employee_id', String(employee.id));
                                                    setEmployeeKeyword(employee.name);
                                                    setEmployeeOptions([]);
                                                }}
                                            >
                                                <p className="text-sm font-semibold">{employee.name}</p>
                                                <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{employee.email}</p>
                                            </button>
                                        ))}
                                    </div>
                                ) : null}
                            </FilterField>
                        ) : null}
                    </div>
                ) : null}

                <div className="flex flex-wrap items-center justify-between gap-2 border-t pt-3" style={{ borderColor: 'var(--hr-line)' }}>
                    <div className="flex items-center gap-2">
                        <button
                            type="submit"
                            className="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                            style={{ background: '#0f766e' }}
                            disabled={loading || submitting}
                        >
                            <FilterIcon />
                            {loading ? 'Loading...' : 'Apply Filters'}
                        </button>
                        <button
                            type="button"
                            className="rounded-xl border px-4 py-2 text-sm font-semibold"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={clearFilters}
                            disabled={loading || submitting}
                        >
                            <XIcon />
                            Clear
                        </button>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {canExport ? (
                            <a
                                href="#"
                                className="rounded-xl border px-3 py-2 text-xs font-semibold"
                                style={{ borderColor: 'var(--hr-line)' }}
                                onClick={(event) => {
                                    event.preventDefault();
                                    onApply(normalizedFilters, true);
                                }}
                            >
                                <DownloadIcon />
                                Export
                            </a>
                        ) : null}

                        {canLockMonth ? (
                            !selectedMonthLocked ? (
                                <button
                                    type="button"
                                    className="rounded-xl px-3 py-2 text-xs font-semibold text-white"
                                    style={{ background: '#b45309' }}
                                    onClick={() => onLockMonth(selectedMonth)}
                                    disabled={loading || submitting}
                                >
                                    <LockClosedIcon />
                                    Lock Month
                                </button>
                            ) : (
                                <span className="rounded-full bg-slate-300/60 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-500/65 dark:text-white">
                                    Month Locked
                                </span>
                            )
                        ) : null}

                        {canUnlockMonth && selectedMonthLocked ? (
                            <button
                                type="button"
                                className="rounded-xl px-3 py-2 text-xs font-semibold text-white"
                                style={{ background: '#1d4ed8' }}
                                onClick={() => onUnlockMonth(selectedMonth)}
                                disabled={loading || submitting}
                            >
                                <LockOpenIcon />
                                Unlock Month
                            </button>
                        ) : null}
                    </div>
                </div>
            </form>
        </article>
    );
}
