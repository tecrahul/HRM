import React, { useEffect, useState } from 'react';

export function LeaveFilters({
    filters,
    statusOptions,
    onApply,
    onReset,
    canFilterByEmployee = false,
    onSearchEmployees = async () => [],
    loading = false,
}) {
    const [localFilters, setLocalFilters] = useState({
        q: '',
        status: 'all',
        date_from: '',
        date_to: '',
        employee_id: '',
        range_mode: 'absolute',
        range_preset: '',
    });
    const [employeeQuery, setEmployeeQuery] = useState('');
    const [employeeOptions, setEmployeeOptions] = useState([]);
    const [employeeLoading, setEmployeeLoading] = useState(false);

    useEffect(() => {
        setLocalFilters({
            q: filters?.q ?? '',
            status: filters?.status ?? 'all',
            date_from: filters?.date_from ?? '',
            date_to: filters?.date_to ?? '',
            employee_id: filters?.employee_id ?? '',
            range_mode: filters?.range_mode ?? 'absolute',
            range_preset: filters?.range_preset ?? '',
        });

        if ((filters?.employee_id ?? '') === '') {
            setEmployeeQuery('');
            setEmployeeOptions([]);
        }
    }, [
        filters?.date_from,
        filters?.date_to,
        filters?.employee_id,
        filters?.q,
        filters?.range_mode,
        filters?.range_preset,
        filters?.status,
    ]);

    useEffect(() => {
        if (!canFilterByEmployee) {
            return;
        }

        const keyword = employeeQuery.trim();
        if (keyword.length < 2 || localFilters.employee_id !== '') {
            setEmployeeOptions([]);
            setEmployeeLoading(false);
            return;
        }

        let cancelled = false;
        const timer = window.setTimeout(async () => {
            setEmployeeLoading(true);
            try {
                const result = await onSearchEmployees(keyword);
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
    }, [canFilterByEmployee, employeeQuery, localFilters.employee_id, onSearchEmployees]);

    const handleChange = (field, value) => {
        setLocalFilters((prev) => ({
            ...prev,
            [field]: value,
        }));
    };

    const submit = (event) => {
        event.preventDefault();
        onApply({
            q: localFilters.q,
            status: localFilters.status,
            date_from: localFilters.date_from,
            date_to: localFilters.date_to,
            employee_id: canFilterByEmployee ? localFilters.employee_id : '',
            range_mode: 'absolute',
            range_preset: '',
        });
    };

    return (
        <form
            onSubmit={submit}
            className={`rounded-2xl border p-4 grid grid-cols-1 md:grid-cols-2 ${canFilterByEmployee ? 'xl:grid-cols-7' : 'xl:grid-cols-6'} gap-3`}
            style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}
        >
            <div className="xl:col-span-2">
                <label htmlFor="leave_filter_q" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                    Search
                </label>
                <input
                    id="leave_filter_q"
                    type="search"
                    className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent mt-2"
                    style={{ borderColor: 'var(--hr-line)' }}
                    value={localFilters.q}
                    onChange={(event) => handleChange('q', event.target.value)}
                    placeholder="Leave type or reason"
                    disabled={loading}
                />
            </div>

            <div>
                <label htmlFor="leave_filter_status" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                    Status
                </label>
                <select
                    id="leave_filter_status"
                    className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent mt-2"
                    style={{ borderColor: 'var(--hr-line)' }}
                    value={localFilters.status}
                    onChange={(event) => handleChange('status', event.target.value)}
                    disabled={loading}
                >
                    <option value="all">All Statuses</option>
                    {statusOptions.map((statusOption) => (
                        <option key={statusOption.value} value={statusOption.value}>
                            {statusOption.label}
                        </option>
                    ))}
                </select>
            </div>

            {canFilterByEmployee ? (
                <div className="xl:col-span-2 relative">
                    <label htmlFor="leave_filter_employee" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Employee
                    </label>
                    <input
                        id="leave_filter_employee"
                        type="text"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent mt-2"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={employeeQuery}
                        placeholder="Search employee by name or email"
                        onChange={(event) => {
                            const next = event.target.value;
                            setEmployeeQuery(next);
                            if (localFilters.employee_id !== '') {
                                handleChange('employee_id', '');
                            }
                        }}
                        disabled={loading}
                        autoComplete="off"
                    />
                    {employeeLoading ? (
                        <p className="text-xs mt-1" style={{ color: 'var(--hr-text-muted)' }}>Searching employees...</p>
                    ) : null}
                    {employeeOptions.length > 0 && localFilters.employee_id === '' ? (
                        <div className="absolute z-20 mt-1 w-full rounded-xl border p-2 max-h-40 overflow-auto" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                            {employeeOptions.map((employee) => (
                                <button
                                    key={employee.id}
                                    type="button"
                                    className="w-full text-left px-2 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                                    onClick={() => {
                                        handleChange('employee_id', String(employee.id));
                                        setEmployeeQuery(employee.name);
                                        setEmployeeOptions([]);
                                    }}
                                >
                                    <p className="text-sm font-semibold">{employee.name}</p>
                                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{employee.email}</p>
                                </button>
                            ))}
                        </div>
                    ) : null}
                </div>
            ) : null}

            <div className={`${canFilterByEmployee ? 'xl:col-span-2' : 'xl:col-span-3'} grid grid-cols-1 md:grid-cols-2 gap-3`}>
                <div>
                    <label htmlFor="leave_filter_date_from" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        From
                    </label>
                    <input
                        id="leave_filter_date_from"
                        type="date"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent mt-2"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={localFilters.date_from}
                        onChange={(event) => {
                            const from = event.target.value;
                            setLocalFilters((prev) => ({
                                ...prev,
                                date_from: from,
                                date_to: prev.date_to && from && prev.date_to < from ? from : prev.date_to,
                            }));
                        }}
                        disabled={loading}
                    />
                </div>
                <div>
                    <label htmlFor="leave_filter_date_to" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        To
                    </label>
                    <input
                        id="leave_filter_date_to"
                        type="date"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent mt-2"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={localFilters.date_to}
                        min={localFilters.date_from || undefined}
                        onChange={(event) => {
                            const to = event.target.value;
                            setLocalFilters((prev) => ({
                                ...prev,
                                date_to: to && prev.date_from && to < prev.date_from ? prev.date_from : to,
                            }));
                        }}
                        disabled={loading}
                    />
                </div>
            </div>

            <div className="md:col-span-2 xl:col-span-full flex items-center gap-2">
                <button
                    type="submit"
                    className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                    style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                    disabled={loading}
                >
                    {loading ? 'Applying...' : 'Apply Filters'}
                </button>
                <button
                    type="button"
                    className="rounded-xl px-4 py-2.5 text-sm font-semibold border"
                    style={{ borderColor: 'var(--hr-line)' }}
                    onClick={() => {
                        setEmployeeQuery('');
                        setEmployeeOptions([]);
                        onReset();
                    }}
                    disabled={loading}
                >
                    Reset
                </button>
            </div>
        </form>
    );
}
