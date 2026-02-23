import React from 'react';

const textInputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';

const statusToggleShellStyles = (isActive, isDarkMode) => {
    if (isDarkMode) {
        return {
            borderColor: 'rgb(100 116 139 / 0.55)',
            background: isActive ? 'rgb(16 185 129 / 0.18)' : 'rgb(107 114 128 / 0.28)',
        };
    }

    return {
        borderColor: 'var(--hr-line)',
        background: isActive ? 'rgb(209 250 229)' : 'rgb(229 231 235)',
    };
};

const statusToggleTrackStyles = (isActive, isDarkMode) => ({
    background: isActive
        ? (isDarkMode ? 'rgb(16 185 129 / 0.86)' : '#10b981')
        : (isDarkMode ? 'rgb(107 114 128 / 0.9)' : '#6b7280'),
});

const statusToggleLabelStyles = (isDarkMode) => ({
    color: isDarkMode ? '#f8fafc' : '#111827',
});

function Field({
    label,
    name,
    value,
    onChange,
    error,
    required = false,
    type = 'text',
    placeholder,
}) {
    return (
        <div className="flex flex-col gap-2">
            <label htmlFor={name} className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                {label}{required ? ' *' : ''}
            </label>
            <input
                id={name}
                name={name}
                type={type}
                value={value}
                onChange={(event) => onChange(name, event.target.value)}
                placeholder={placeholder}
                className={textInputClassName}
                style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
            />
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );
}

export function HolidayForm({
    mode,
    values,
    errors,
    branches,
    isDarkMode,
    onFieldChange,
    onSubmit,
    onCancel,
    onClose,
    saving,
}) {
    return (
        <article className="hrm-modern-surface rounded-2xl p-5">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-extrabold">{mode === 'edit' ? 'Update Holiday' : 'Create Holiday'}</h3>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Configure holiday details, scope, date range, and active state.
                    </p>
                </div>
                <button
                    type="button"
                    className="h-9 w-9 rounded-lg border inline-flex items-center justify-center"
                    style={{ borderColor: 'var(--hr-line)' }}
                    onClick={onClose}
                    aria-label="Close form"
                    disabled={saving}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <form className="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4" onSubmit={onSubmit}>
                <Field
                    label="Holiday Name"
                    name="name"
                    value={values.name}
                    onChange={onFieldChange}
                    error={errors.name}
                    required
                    placeholder="Founder's Day"
                />

                <div className="flex flex-col gap-2">
                    <label htmlFor="holiday_type" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Holiday Type *
                    </label>
                    <select
                        id="holiday_type"
                        name="holiday_type"
                        className={textInputClassName}
                        style={{ borderColor: errors.holiday_type ? '#f87171' : 'var(--hr-line)' }}
                        value={values.holiday_type}
                        onChange={(event) => onFieldChange('holiday_type', event.target.value)}
                    >
                        <option value="public">Public Holiday</option>
                        <option value="company">Company Holiday</option>
                        <option value="optional">Optional Holiday</option>
                    </select>
                    {errors.holiday_type ? <p className="text-xs text-red-500">{errors.holiday_type}</p> : null}
                </div>

                <Field
                    label="Holiday Date"
                    name="holiday_date"
                    value={values.holiday_date}
                    onChange={onFieldChange}
                    error={errors.holiday_date}
                    required
                    type="date"
                />

                <Field
                    label="End Date"
                    name="end_date"
                    value={values.end_date}
                    onChange={onFieldChange}
                    error={errors.end_date}
                    type="date"
                    placeholder="Optional"
                />

                <div className="flex flex-col gap-2">
                    <label htmlFor="branch_id" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Branch
                    </label>
                    <select
                        id="branch_id"
                        name="branch_id"
                        className={textInputClassName}
                        style={{ borderColor: errors.branch_id ? '#f87171' : 'var(--hr-line)' }}
                        value={values.branch_id}
                        onChange={(event) => onFieldChange('branch_id', event.target.value)}
                    >
                        <option value="">All Branches</option>
                        {(branches ?? []).map((branch) => (
                            <option key={branch.id} value={String(branch.id)}>{branch.name}</option>
                        ))}
                    </select>
                    {errors.branch_id ? <p className="text-xs text-red-500">{errors.branch_id}</p> : null}
                </div>

                <div className="flex flex-col gap-2">
                    <p className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Status
                    </p>
                    <button
                        type="button"
                        className="w-fit rounded-full px-1.5 py-1 inline-flex items-center gap-2 border"
                        style={statusToggleShellStyles(values.is_active, isDarkMode)}
                        onClick={() => onFieldChange('is_active', !values.is_active)}
                        aria-pressed={values.is_active}
                    >
                        <span
                            className={`h-5 w-9 rounded-full inline-flex items-center px-0.5 transition-all ${values.is_active ? 'justify-end' : 'justify-start'}`}
                            style={statusToggleTrackStyles(values.is_active, isDarkMode)}
                        >
                            <span className="h-4 w-4 rounded-full bg-white shadow" />
                        </span>
                        <span className="text-xs font-semibold" style={statusToggleLabelStyles(isDarkMode)}>
                            {values.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </button>
                    {errors.is_active ? <p className="text-xs text-red-500">{errors.is_active}</p> : null}
                </div>

                <div className="md:col-span-2 flex flex-col gap-2">
                    <label htmlFor="description" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows={4}
                        className={`${textInputClassName} resize-y`}
                        style={{ borderColor: errors.description ? '#f87171' : 'var(--hr-line)' }}
                        value={values.description}
                        onChange={(event) => onFieldChange('description', event.target.value)}
                        placeholder="Holiday notes, applicable teams, or policy references"
                    />
                    {errors.description ? <p className="text-xs text-red-500">{errors.description}</p> : null}
                </div>

                <div className="md:col-span-2 flex items-center gap-3 pt-1">
                    <button
                        type="submit"
                        className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                        disabled={saving}
                    >
                        {saving
                            ? (mode === 'edit' ? 'Updating...' : 'Creating...')
                            : (mode === 'edit' ? 'Update Holiday' : 'Create Holiday')}
                    </button>
                    <button
                        type="button"
                        className="rounded-xl px-4 py-2.5 text-sm font-semibold border"
                        style={{ borderColor: 'var(--hr-line)' }}
                        onClick={onCancel}
                        disabled={saving}
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </article>
    );
}
