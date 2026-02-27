import React, { useMemo, useState } from 'react';

const weekdayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const typeColors = {
    public: { bg: 'rgba(14, 165, 104, 0.18)', border: 'rgba(16, 185, 129, 0.55)', text: '#065f46' },
    company: { bg: 'rgba(59, 130, 246, 0.18)', border: 'rgba(59, 130, 246, 0.55)', text: '#1e3a8a' },
    optional: { bg: 'rgba(234, 179, 8, 0.18)', border: 'rgba(234, 179, 8, 0.55)', text: '#78350f' },
};

function startOfMonth(year, month) {
    return new Date(year, month - 1, 1);
}

function endOfMonth(year, month) {
    return new Date(year, month, 0);
}

function addDays(date, days) {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    return d;
}

function sameYMD(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

function inRange(date, start, end) {
    return date >= start && date <= end;
}

// Parse YYYY-MM-DD as a local date to avoid UTC offset shifting
function parseLocalYMD(ymd) {
    if (!ymd) return null;
    const parts = String(ymd).split('-');
    if (parts.length !== 3) return null;
    const y = Number.parseInt(parts[0], 10);
    const m = Number.parseInt(parts[1], 10);
    const d = Number.parseInt(parts[2], 10);
    if (Number.isNaN(y) || Number.isNaN(m) || Number.isNaN(d)) return null;
    return new Date(y, m - 1, d);
}

function normalizeHoliday(holiday) {
    const start = parseLocalYMD(holiday.holiday_date) || new Date(0);
    const endRaw = holiday.end_date ? parseLocalYMD(holiday.end_date) : start;
    const end = endRaw && endRaw >= start ? endRaw : start;
    return { ...holiday, _start: start, _end: end };
}

export function HolidayCalendarView({
    holidays = [],
    year,
    month,
    onSelectHoliday,
    // Optional: URL to Holidays module index for quick add
    holidayIndexUrl = '/modules/holidays',
}) {
    const today = new Date();
    const first = startOfMonth(year, month);
    const last = endOfMonth(year, month);

    // Compute grid start (previous Sunday) and end (next Saturday)
    const gridStart = addDays(first, -first.getDay());
    const gridEnd = addDays(last, 6 - last.getDay());

    const days = [];
    for (let d = new Date(gridStart); d <= gridEnd; d = addDays(d, 1)) {
        days.push(new Date(d));
    }

    const normalized = useMemo(() => holidays.map(normalizeHoliday), [holidays]);

    const eventsByDate = useMemo(() => {
        const map = new Map();
        for (const day of days) {
            map.set(day.toDateString(), []);
        }
        for (const h of normalized) {
            const start = h._start;
            const end = h._end;
            // iterate through the visible grid and assign occurrences when overlapping
            for (const day of days) {
                if (inRange(day, start, end)) {
                    map.get(day.toDateString()).push(h);
                }
            }
        }
        return map;
    }, [days, normalized]);

    function HolidayBadge({ h }) {
        const palette = typeColors[h.holiday_type] || typeColors.public;
        const [hover, setHover] = useState(false);
        const tooltip = (
            <div
                className="absolute z-[5] mt-1 w-56 rounded-xl border text-xs shadow-xl p-2"
                style={{
                    borderColor: 'var(--hr-line)',
                    background: 'var(--hr-surface)',
                    display: hover ? 'block' : 'none',
                }}
            >
                <div className="font-semibold">{h.name}</div>
                <div className="mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                    <div>Type: {h.holidayTypeLabel}</div>
                    <div>Branch: {h.branchName || 'All Branches'}</div>
                    <div>Status: {h.statusLabel}</div>
                </div>
            </div>
        );

        return (
            <div className="relative">
                <button
                    type="button"
                    className="w-full text-left truncate rounded-lg px-2 py-1 text-xs font-semibold"
                    style={{
                        background: palette.bg,
                        color: palette.text,
                        border: `1px solid ${palette.border}`,
                    }}
                    onMouseEnter={() => setHover(true)}
                    onMouseLeave={() => setHover(false)}
                    onClick={() => onSelectHoliday?.(h)}
                    title={`${h.name} • ${h.holidayTypeLabel}`}
                >
                    {h.name}
                </button>
                {tooltip}
            </div>
        );
    }

    return (
        <div className="mt-3">
            <div
                className="rounded-2xl p-2 border"
                style={{
                    borderColor: 'var(--hr-line)',
                    // Subtle modern gradient canvas for the calendar grid
                    background:
                        'radial-gradient(1000px circle at 0% 0%, rgb(16 185 129 / 0.12), transparent 42%), ' +
                        'radial-gradient(800px circle at 100% 100%, rgb(234 179 8 / 0.12), transparent 42%)',
                }}
            >
                <div className="grid grid-cols-7 text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                    {weekdayNames.map((w) => (
                        <div key={w} className="py-2 px-2">{w}</div>
                    ))}
                </div>
                <div className="grid grid-cols-7 gap-px rounded-xl overflow-hidden border" style={{ borderColor: 'var(--hr-line)' }}>
                    {days.map((day, idx) => {
                        const isCurrentMonth = day.getMonth() === (month - 1);
                        const isToday = sameYMD(day, today);
                        const k = day.toDateString();
                        const evs = eventsByDate.get(k) || [];
                        const visible = evs.slice(0, 2);
                        const more = evs.length - visible.length;

                        const y = day.getFullYear();
                        const m = String(day.getMonth() + 1).padStart(2, '0');
                        const d = String(day.getDate()).padStart(2, '0');
                        const dateParam = `${y}-${m}-${d}`;
                        const addHolidayHref = `${String(holidayIndexUrl || '/modules/holidays')}?action=create&holiday_date=${dateParam}`;

                        return (
                            <div
                                key={`${k}-${idx}`}
                                className="min-h-[118px] p-2 transition-colors"
                                style={{
                                    background: 'linear-gradient(180deg, var(--hr-surface) 0%, var(--hr-surface-strong) 100%)',
                                    opacity: isCurrentMonth ? 1 : 0.65,
                                }}
                            >
                                <div className="flex items-center justify-between">
                                    <div className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                                        {day.getDate()}
                                    </div>
                                    {isToday ? (
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style={{ background: 'rgb(59 130 246 / 0.14)', color: '#1e3a8a' }}>Today</span>
                                    ) : null}
                                </div>
                                <div className="mt-1 space-y-1">
                                    {visible.map((h) => (
                                        <HolidayBadge key={`${h.id}-${k}`} h={h} />
                                    ))}
                                    {more > 0 ? (
                                        <div className="text-[10px] font-semibold" style={{ color: 'var(--hr-text-muted)' }}>+{more} more</div>
                                    ) : null}
                                    {evs.length === 0 && holidayIndexUrl ? (
                                        <div className="pt-1">
                                            <a
                                                href={addHolidayHref}
                                                className="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold hover:opacity-90"
                                                style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}
                                                aria-label={`Add holiday on ${dateParam}`}
                                            >
                                                <span className="leading-none">＋</span>
                                                Add Holiday
                                            </a>
                                        </div>
                                    ) : null}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
