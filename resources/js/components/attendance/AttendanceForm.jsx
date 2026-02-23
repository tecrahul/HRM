import React, { useEffect, useState } from 'react';

const inputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';

const EMPTY_VALUES = {
    user_id: '',
    attendance_date: '',
    status: 'present',
    check_in_time: '',
    check_out_time: '',
    notes: '',
};

export function AttendanceForm({
    capabilities,
    options,
    currentUser,
    editingRecord,
    submitting,
    onSubmit,
    onCancel,
    onClose,
    onSearchEmployees,
}) {
    const [values, setValues] = useState(EMPTY_VALUES);
    const [errors, setErrors] = useState({});
    const [employeeKeyword, setEmployeeKeyword] = useState('');
    const [employeeOptions, setEmployeeOptions] = useState([]);
    const [employeeLoading, setEmployeeLoading] = useState(false);

    const canPickEmployee = Boolean(capabilities?.canEdit || capabilities?.canViewAll || capabilities?.canViewDepartment);

    useEffect(() => {
        if (!editingRecord) {
            setValues({
                ...EMPTY_VALUES,
                user_id: canPickEmployee ? '' : String(currentUser?.id || ''),
                attendance_date: new Date().toISOString().slice(0, 10),
                status: 'present',
            });
            setErrors({});
            setEmployeeKeyword('');
            return;
        }

        setValues({
            user_id: String(editingRecord.employee?.id || ''),
            attendance_date: editingRecord.attendanceDate || new Date().toISOString().slice(0, 10),
            status: editingRecord.status || 'present',
            check_in_time: editingRecord.checkIn !== 'N/A' && editingRecord.checkIn ? convertTo24(editingRecord.checkIn) : '',
            check_out_time: editingRecord.checkOut !== 'N/A' && editingRecord.checkOut ? convertTo24(editingRecord.checkOut) : '',
            notes: editingRecord.notes || '',
        });
        setErrors({});
        setEmployeeKeyword(editingRecord.employee?.name || '');
    }, [canPickEmployee, currentUser?.id, editingRecord]);

    useEffect(() => {
        if (!canPickEmployee) {
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
                const result = await onSearchEmployees(query);
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
    }, [canPickEmployee, employeeKeyword, onSearchEmployees]);

    const setField = (field, value) => {
        setValues((prev) => ({
            ...prev,
            [field]: value,
        }));

        setErrors((prev) => {
            if (!prev[field]) {
                return prev;
            }

            const next = { ...prev };
            delete next[field];
            return next;
        });
    };

    const validate = () => {
        const nextErrors = {};

        if (!values.attendance_date) {
            nextErrors.attendance_date = 'Attendance date is required.';
        }

        if (!values.status) {
            nextErrors.status = 'Status is required.';
        }

        if (canPickEmployee && !values.user_id) {
            nextErrors.user_id = 'Employee is required.';
        }

        if (values.check_in_time && values.check_out_time && values.check_out_time <= values.check_in_time) {
            nextErrors.check_out_time = 'Check-out time must be after check-in time.';
        }

        return nextErrors;
    };

    const submit = (event) => {
        event.preventDefault();

        const nextErrors = validate();
        if (Object.keys(nextErrors).length > 0) {
            setErrors(nextErrors);
            return;
        }

        onSubmit(values, setErrors);
    };

    return (
        <article className="hrm-modern-surface rounded-2xl p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-extrabold">{editingRecord ? 'Update Attendance' : 'Mark Attendance'}</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Capture attendance details and route entries through approval workflow.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    {editingRecord ? (
                        <button
                            type="button"
                            className="rounded-xl border px-3 py-2 text-sm font-semibold"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={onCancel}
                            disabled={submitting}
                        >
                            Cancel Edit
                        </button>
                    ) : null}
                    <button
                        type="button"
                        className="h-9 w-9 rounded-lg border inline-flex items-center justify-center"
                        style={{ borderColor: 'var(--hr-line)' }}
                        onClick={onClose}
                        aria-label="Close form"
                        disabled={submitting}
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <form className="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4" onSubmit={submit}>
                {canPickEmployee ? (
                    <div className="md:col-span-2 relative">
                        <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }} htmlFor="attendance_form_employee">
                            Employee *
                        </label>
                        <input
                            id="attendance_form_employee"
                            type="text"
                            className={`${inputClassName} mt-1`}
                            style={{ borderColor: errors.user_id ? '#f87171' : 'var(--hr-line)' }}
                            value={employeeKeyword}
                            placeholder="Search employee"
                            onChange={(event) => {
                                setEmployeeKeyword(event.target.value);
                                setField('user_id', '');
                            }}
                            disabled={submitting}
                            autoComplete="off"
                        />
                        {employeeLoading ? <p className="text-xs mt-1" style={{ color: 'var(--hr-text-muted)' }}>Searching employees...</p> : null}
                        {employeeOptions.length > 0 && !values.user_id ? (
                            <div className="absolute z-20 left-0 right-0 mt-1 rounded-xl border p-1 max-h-44 overflow-auto" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                                {employeeOptions.map((employee) => (
                                    <button
                                        key={employee.id}
                                        type="button"
                                        className="w-full text-left rounded-lg px-2 py-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                                        onClick={() => {
                                            setField('user_id', String(employee.id));
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
                        {errors.user_id ? <p className="text-xs text-red-500 mt-1">{errors.user_id}</p> : null}
                    </div>
                ) : null}

                <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }} htmlFor="attendance_form_date">
                        Attendance Date *
                    </label>
                    <input
                        id="attendance_form_date"
                        type="date"
                        className={`${inputClassName} mt-1`}
                        style={{ borderColor: errors.attendance_date ? '#f87171' : 'var(--hr-line)' }}
                        value={values.attendance_date}
                        onChange={(event) => setField('attendance_date', event.target.value)}
                        disabled={submitting}
                    />
                    {errors.attendance_date ? <p className="text-xs text-red-500 mt-1">{errors.attendance_date}</p> : null}
                </div>

                <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }} htmlFor="attendance_form_status">
                        Status *
                    </label>
                    <select
                        id="attendance_form_status"
                        className={`${inputClassName} mt-1`}
                        style={{ borderColor: errors.status ? '#f87171' : 'var(--hr-line)' }}
                        value={values.status}
                        onChange={(event) => setField('status', event.target.value)}
                        disabled={submitting}
                    >
                        {(options?.statuses ?? []).map((statusOption) => (
                            <option key={statusOption.value} value={statusOption.value}>{statusOption.label}</option>
                        ))}
                    </select>
                    {errors.status ? <p className="text-xs text-red-500 mt-1">{errors.status}</p> : null}
                </div>

                <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }} htmlFor="attendance_form_check_in">
                        Check In
                    </label>
                    <input
                        id="attendance_form_check_in"
                        type="time"
                        className={`${inputClassName} mt-1`}
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={values.check_in_time}
                        onChange={(event) => setField('check_in_time', event.target.value)}
                        disabled={submitting}
                    />
                </div>

                <div>
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }} htmlFor="attendance_form_check_out">
                        Check Out
                    </label>
                    <input
                        id="attendance_form_check_out"
                        type="time"
                        className={`${inputClassName} mt-1`}
                        style={{ borderColor: errors.check_out_time ? '#f87171' : 'var(--hr-line)' }}
                        value={values.check_out_time}
                        onChange={(event) => setField('check_out_time', event.target.value)}
                        disabled={submitting}
                    />
                    {errors.check_out_time ? <p className="text-xs text-red-500 mt-1">{errors.check_out_time}</p> : null}
                </div>

                <div className="md:col-span-2">
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }} htmlFor="attendance_form_notes">
                        Notes
                    </label>
                    <textarea
                        id="attendance_form_notes"
                        rows={3}
                        className={`${inputClassName} mt-1 resize-y`}
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={values.notes}
                        onChange={(event) => setField('notes', event.target.value)}
                        disabled={submitting}
                        placeholder="Optional note"
                    />
                </div>

                <div className="md:col-span-2 flex items-center gap-2">
                    <button
                        type="submit"
                        className="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                        style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                        disabled={submitting}
                    >
                        {submitting ? 'Saving...' : (editingRecord ? 'Update Attendance' : 'Save Attendance')}
                    </button>
                    {editingRecord ? (
                        <button
                            type="button"
                            className="rounded-xl border px-4 py-2 text-sm font-semibold"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={onCancel}
                            disabled={submitting}
                        >
                            Cancel
                        </button>
                    ) : null}
                </div>
            </form>
        </article>
    );
}

function convertTo24(timeLabel) {
    const normalized = String(timeLabel || '').trim();
    const match = normalized.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);

    if (!match) {
        return '';
    }

    let hours = Number(match[1]);
    const minutes = match[2];
    const period = match[3].toUpperCase();

    if (period === 'AM') {
        if (hours === 12) {
            hours = 0;
        }
    } else if (hours < 12) {
        hours += 12;
    }

    return `${String(hours).padStart(2, '0')}:${minutes}`;
}
