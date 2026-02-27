import React from 'react';

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

export function HolidayCalendarHeader({ year, month, onPrev, onNext, onToday }) {
    const monthIndex = Math.max(1, Math.min(12, parseInt(month || new Date().getMonth() + 1, 10))) - 1;
    const label = `${monthNames[monthIndex]} ${year}`;

    return (
        <div className="flex items-center justify-between gap-2">
            <h3 className="font-extrabold leading-tight text-base md:text-lg">{label}</h3>
            <div className="flex items-center gap-1.5">
                <button
                    type="button"
                    className="rounded-xl border px-2.5 py-1.5 text-xs md:text-sm font-semibold"
                    style={{ borderColor: 'var(--hr-line)' }}
                    onClick={onToday}
                >
                    Today
                </button>
                <button
                    type="button"
                    className="rounded-xl border px-2.5 py-1.5 text-xs md:text-sm font-semibold"
                    style={{ borderColor: 'var(--hr-line)' }}
                    onClick={onPrev}
                    title="Previous month"
                    aria-label="Previous month"
                >
                    ←
                </button>
                <button
                    type="button"
                    className="rounded-xl border px-2.5 py-1.5 text-xs md:text-sm font-semibold"
                    style={{ borderColor: 'var(--hr-line)' }}
                    onClick={onNext}
                    title="Next month"
                    aria-label="Next month"
                >
                    →
                </button>
            </div>
        </div>
    );
}
