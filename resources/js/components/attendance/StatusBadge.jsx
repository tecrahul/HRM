import React from 'react';

const ATTENDANCE_STATUS_CLASSES = {
    present: 'text-emerald-800 bg-emerald-200/60 dark:text-white dark:bg-emerald-500/45',
    absent: 'text-red-800 bg-red-200/60 dark:text-white dark:bg-red-500/45',
    late: 'text-amber-800 bg-amber-200/65 dark:text-white dark:bg-amber-500/45',
    on_leave: 'text-blue-800 bg-blue-200/65 dark:text-white dark:bg-blue-500/45',
    leave: 'text-blue-800 bg-blue-200/65 dark:text-white dark:bg-blue-500/45',
    half_day: 'text-yellow-800 bg-yellow-200/65 dark:text-white dark:bg-yellow-500/45',
    remote: 'text-sky-800 bg-sky-200/65 dark:text-white dark:bg-sky-500/45',
};

const APPROVAL_STATUS_CLASSES = {
    pending: 'text-amber-800 bg-amber-200/70 dark:text-white dark:bg-amber-500/45',
    approved: 'text-emerald-800 bg-emerald-200/65 dark:text-white dark:bg-emerald-500/45',
    rejected: 'text-red-800 bg-red-200/65 dark:text-white dark:bg-red-500/45',
    locked: 'text-slate-700 bg-slate-300/70 dark:text-white dark:bg-slate-500/65',
};

export function StatusBadge({ type = 'attendance', value, label }) {
    const normalized = String(value || '').toLowerCase();
    const colorClass = type === 'approval'
        ? (APPROVAL_STATUS_CLASSES[normalized] ?? APPROVAL_STATUS_CLASSES.pending)
        : (ATTENDANCE_STATUS_CLASSES[normalized] ?? ATTENDANCE_STATUS_CLASSES.present);

    return (
        <span
            className={`rounded-full px-2 py-1 text-[11px] font-bold uppercase tracking-[0.08em] ${colorClass}`}
        >
            {label || normalized}
        </span>
    );
}
