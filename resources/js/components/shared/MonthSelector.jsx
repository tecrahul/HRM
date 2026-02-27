import React from 'react';

const MONTHS = [
    { value: '', label: 'All Months' },
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

export function MonthSelector({ id = 'month', value = '', onChange, disabled = false }) {
    return (
        <select
            id={id}
            className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent"
            style={{ borderColor: 'var(--hr-line)' }}
            value={String(value ?? '')}
            onChange={(e) => onChange?.(e.target.value)}
            disabled={disabled}
        >
            {MONTHS.map((m) => (
                <option key={m.value} value={m.value}>{m.label}</option>
            ))}
        </select>
    );
}

export { MONTHS };

