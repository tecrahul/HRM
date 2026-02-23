import React, { useEffect, useMemo, useState } from 'react';
import { calculateTotalDays, validateLeaveForm } from '../../utils/validation';

const EMPTY_FORM = {
    employeeId: '',
    leaveType: '',
    dayType: 'full_day',
    halfDaySession: '',
    startDate: '',
    endDate: '',
    totalDays: 0,
    reason: '',
    attachment: null,
    status: 'pending',
    assignNote: '',
};

const inputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';

export function LeaveForm({
    capabilities,
    options,
    currentUser,
    remainingDays = 0,
    submitting = false,
    onSubmit,
    onSearchEmployees,
    editingLeave = null,
    onCancelEdit,
    onClose,
}) {
    const isManagement = Boolean(capabilities?.canAssign);
    const [values, setValues] = useState(EMPTY_FORM);
    const [errors, setErrors] = useState({});
    const [employeeQuery, setEmployeeQuery] = useState('');
    const [employeeOptions, setEmployeeOptions] = useState([]);
    const [employeeLoading, setEmployeeLoading] = useState(false);

    useEffect(() => {
        if (!editingLeave) {
            setValues((prev) => ({
                ...EMPTY_FORM,
                dayType: 'full_day',
                employeeId: isManagement ? '' : String(currentUser?.id || ''),
                status: isManagement ? 'pending' : '',
            }));
            setErrors({});
            setEmployeeQuery('');
            return;
        }

        setValues({
            employeeId: String(editingLeave.employee?.id || ''),
            leaveType: editingLeave.leaveType || '',
            dayType: editingLeave.dayType || 'full_day',
            halfDaySession: editingLeave.halfDaySession || '',
            startDate: editingLeave.startDateIso || '',
            endDate: editingLeave.endDateIso || '',
            totalDays: Number(editingLeave.totalDays || 0),
            reason: editingLeave.reason || '',
            attachment: null,
            status: editingLeave.status || 'pending',
            assignNote: editingLeave.reviewNote || '',
        });
        setErrors({});
        setEmployeeQuery(editingLeave.employee?.name || '');
    }, [currentUser?.id, editingLeave, isManagement]);

    useEffect(() => {
        const computedDays = calculateTotalDays(values);
        setValues((prev) => {
            if (prev.totalDays === computedDays) {
                return prev;
            }

            return {
                ...prev,
                totalDays: computedDays,
            };
        });
    }, [values.dayType, values.endDate, values.startDate]);

    useEffect(() => {
        if (!isManagement) {
            return;
        }

        const keyword = employeeQuery.trim();
        if (keyword.length < 2) {
            setEmployeeOptions([]);
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
    }, [employeeQuery, isManagement, onSearchEmployees]);

    const leaveTypeOptions = options?.leaveTypes ?? [];
    const dayTypeOptions = options?.dayTypes ?? [];
    const halfDayOptions = options?.halfDaySessions ?? [];
    const statusOptions = options?.createStatuses ?? [];

    const selectedEmployee = useMemo(
        () => employeeOptions.find((employee) => String(employee.id) === String(values.employeeId)) || null,
        [employeeOptions, values.employeeId]
    );

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

    const handleSubmit = (event) => {
        event.preventDefault();

        const validationErrors = validateLeaveForm(values, {
            isManagement,
            remainingDays,
        });

        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            return;
        }

        const formData = new FormData();
        formData.append('leave_type', values.leaveType);
        formData.append('day_type', values.dayType);
        formData.append('start_date', values.startDate);
        formData.append('end_date', values.endDate);
        formData.append('reason', values.reason);

        if (values.halfDaySession) {
            formData.append('half_day_session', values.halfDaySession);
        }

        if (values.attachment) {
            formData.append('attachment', values.attachment);
        }

        if (isManagement) {
            formData.append('user_id', values.employeeId);
            formData.append('status', values.status);
            if (values.assignNote) {
                formData.append('assign_note', values.assignNote);
            }
        }

        onSubmit(formData, values, setErrors);
    };

    return (
        <article className="hrm-modern-surface rounded-2xl p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-extrabold">{editingLeave ? 'Update Leave Request' : 'Create Leave Request'}</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Submit leave details with validation, balance checks, and optional supporting attachment.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    {editingLeave ? (
                        <button
                            type="button"
                            className="rounded-xl px-3 py-2 text-sm font-semibold border"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={onCancelEdit}
                            disabled={submitting}
                        >
                            Cancel Edit
                        </button>
                    ) : null}
                    {typeof onClose === 'function' ? (
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
                    ) : null}
                </div>
            </div>

            {!isManagement ? (
                <p className="mt-3 text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                    Leave Balance: {Number(remainingDays).toFixed(1)} days remaining
                </p>
            ) : null}

            <form className="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4" onSubmit={handleSubmit}>
                {isManagement ? (
                    <div className="md:col-span-2 flex flex-col gap-2">
                        <label htmlFor="leave_employee" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Employee *
                        </label>
                        <input
                            id="leave_employee"
                            type="text"
                            className={inputClassName}
                            style={{ borderColor: errors.employeeId ? '#f87171' : 'var(--hr-line)' }}
                            value={selectedEmployee ? selectedEmployee.name : employeeQuery}
                            placeholder="Search employee by name or email"
                            onChange={(event) => {
                                setEmployeeQuery(event.target.value);
                                setField('employeeId', '');
                            }}
                            disabled={submitting}
                            autoComplete="off"
                        />
                        {employeeLoading ? (
                            <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>Searching employees...</p>
                        ) : null}
                        {employeeOptions.length > 0 && !selectedEmployee ? (
                            <div className="rounded-xl border p-2 max-h-40 overflow-auto" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                                {employeeOptions.map((employee) => (
                                    <button
                                        key={employee.id}
                                        type="button"
                                        className="w-full text-left px-2 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                                        onClick={() => {
                                            setField('employeeId', String(employee.id));
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
                        {errors.employeeId ? <p className="text-xs text-red-500">{errors.employeeId}</p> : null}
                    </div>
                ) : null}

                <div className="flex flex-col gap-2">
                    <label htmlFor="leave_type" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Leave Type *
                    </label>
                    <select
                        id="leave_type"
                        className={inputClassName}
                        style={{ borderColor: errors.leaveType ? '#f87171' : 'var(--hr-line)' }}
                        value={values.leaveType}
                        onChange={(event) => setField('leaveType', event.target.value)}
                        disabled={submitting}
                    >
                        <option value="">Select type</option>
                        {leaveTypeOptions.map((leaveTypeOption) => (
                            <option key={leaveTypeOption.value} value={leaveTypeOption.value}>
                                {leaveTypeOption.label}
                            </option>
                        ))}
                    </select>
                    {errors.leaveType ? <p className="text-xs text-red-500">{errors.leaveType}</p> : null}
                </div>

                <div className="flex flex-col gap-2">
                    <label htmlFor="day_type" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Day Type *
                    </label>
                    <select
                        id="day_type"
                        className={inputClassName}
                        style={{ borderColor: errors.dayType ? '#f87171' : 'var(--hr-line)' }}
                        value={values.dayType}
                        onChange={(event) => {
                            const next = event.target.value;
                            setField('dayType', next);
                            if (next === 'half_day' && values.startDate) {
                                setField('endDate', values.startDate);
                            }
                            if (next !== 'half_day') {
                                setField('halfDaySession', '');
                            }
                        }}
                        disabled={submitting}
                    >
                        {dayTypeOptions.map((dayTypeOption) => (
                            <option key={dayTypeOption.value} value={dayTypeOption.value}>
                                {dayTypeOption.label}
                            </option>
                        ))}
                    </select>
                    {errors.dayType ? <p className="text-xs text-red-500">{errors.dayType}</p> : null}
                </div>

                {values.dayType === 'half_day' ? (
                    <div className="flex flex-col gap-2">
                        <label htmlFor="half_day_session" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Half Day Session *
                        </label>
                        <select
                            id="half_day_session"
                            className={inputClassName}
                            style={{ borderColor: errors.halfDaySession ? '#f87171' : 'var(--hr-line)' }}
                            value={values.halfDaySession}
                            onChange={(event) => setField('halfDaySession', event.target.value)}
                            disabled={submitting}
                        >
                            <option value="">Select session</option>
                            {halfDayOptions.map((halfDayOption) => (
                                <option key={halfDayOption.value} value={halfDayOption.value}>
                                    {halfDayOption.label}
                                </option>
                            ))}
                        </select>
                        {errors.halfDaySession ? <p className="text-xs text-red-500">{errors.halfDaySession}</p> : null}
                    </div>
                ) : null}

                <div className="flex flex-col gap-2">
                    <label htmlFor="start_date" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        From Date *
                    </label>
                    <input
                        id="start_date"
                        type="date"
                        className={inputClassName}
                        style={{ borderColor: errors.startDate ? '#f87171' : 'var(--hr-line)' }}
                        value={values.startDate}
                        onChange={(event) => {
                            const nextStart = event.target.value;
                            setField('startDate', nextStart);
                            if (values.dayType === 'half_day') {
                                setField('endDate', nextStart);
                            }
                        }}
                        disabled={submitting}
                    />
                    {errors.startDate ? <p className="text-xs text-red-500">{errors.startDate}</p> : null}
                </div>

                <div className="flex flex-col gap-2">
                    <label htmlFor="end_date" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        To Date *
                    </label>
                    <input
                        id="end_date"
                        type="date"
                        className={inputClassName}
                        style={{ borderColor: errors.endDate ? '#f87171' : 'var(--hr-line)' }}
                        value={values.endDate}
                        onChange={(event) => setField('endDate', event.target.value)}
                        disabled={submitting || values.dayType === 'half_day'}
                    />
                    {errors.endDate ? <p className="text-xs text-red-500">{errors.endDate}</p> : null}
                </div>

                <div className="flex flex-col gap-2">
                    <label htmlFor="total_days" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Total Days
                    </label>
                    <input
                        id="total_days"
                        type="text"
                        className={inputClassName}
                        style={{ borderColor: errors.totalDays ? '#f87171' : 'var(--hr-line)' }}
                        value={Number(values.totalDays || 0).toFixed(1)}
                        readOnly
                    />
                    {errors.totalDays ? <p className="text-xs text-red-500">{errors.totalDays}</p> : null}
                </div>

                {isManagement ? (
                    <div className="flex flex-col gap-2">
                        <label htmlFor="leave_status" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Status
                        </label>
                        <select
                            id="leave_status"
                            className={inputClassName}
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={values.status}
                            onChange={(event) => setField('status', event.target.value)}
                            disabled={submitting}
                        >
                            {statusOptions.map((statusOption) => (
                                <option key={statusOption.value} value={statusOption.value}>
                                    {statusOption.label}
                                </option>
                            ))}
                        </select>
                    </div>
                ) : null}

                <div className="md:col-span-2 flex flex-col gap-2">
                    <label htmlFor="reason" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Reason *
                    </label>
                    <textarea
                        id="reason"
                        rows={4}
                        className={`${inputClassName} resize-y`}
                        style={{ borderColor: errors.reason ? '#f87171' : 'var(--hr-line)' }}
                        value={values.reason}
                        onChange={(event) => setField('reason', event.target.value)}
                        placeholder="Please provide clear leave reason..."
                        disabled={submitting}
                    />
                    {errors.reason ? <p className="text-xs text-red-500">{errors.reason}</p> : null}
                </div>

                <div className="md:col-span-2 flex flex-col gap-2">
                    <label htmlFor="attachment" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Attachment (Optional)
                    </label>
                    <input
                        id="attachment"
                        type="file"
                        className={`${inputClassName} file:mr-3 file:rounded-lg file:border-0 file:bg-slate-200 file:px-3 file:py-1.5 file:text-xs file:font-semibold`}
                        style={{ borderColor: errors.attachment ? '#f87171' : 'var(--hr-line)' }}
                        accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                        onChange={(event) => setField('attachment', event.target.files?.[0] || null)}
                        disabled={submitting}
                    />
                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                        Accepted formats: PDF, PNG, JPG, DOC, DOCX. Max size: 5MB.
                    </p>
                    {errors.attachment ? <p className="text-xs text-red-500">{errors.attachment}</p> : null}
                </div>

                {isManagement ? (
                    <div className="md:col-span-2 flex flex-col gap-2">
                        <label htmlFor="assign_note" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Assignment Note
                        </label>
                        <textarea
                            id="assign_note"
                            rows={3}
                            className={`${inputClassName} resize-y`}
                            style={{ borderColor: errors.assignNote ? '#f87171' : 'var(--hr-line)' }}
                            value={values.assignNote}
                            onChange={(event) => setField('assignNote', event.target.value)}
                            placeholder="Optional manager/admin note"
                            disabled={submitting}
                        />
                        {errors.assignNote ? <p className="text-xs text-red-500">{errors.assignNote}</p> : null}
                    </div>
                ) : null}

                <div className="md:col-span-2 flex items-center gap-3 pt-1">
                    <button
                        type="submit"
                        className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                        disabled={submitting}
                    >
                        {submitting ? 'Saving...' : (editingLeave ? 'Update Leave' : 'Submit Leave')}
                    </button>
                    {editingLeave ? (
                        <button
                            type="button"
                            className="rounded-xl px-4 py-2.5 text-sm font-semibold border"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={onCancelEdit}
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
