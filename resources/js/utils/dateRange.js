const pad = (value) => String(value).padStart(2, '0');

export const toDateInput = (date) => {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
        return '';
    }

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

export const parseDateInput = (value) => {
    const raw = String(value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return null;
    }

    const parsed = new Date(`${raw}T00:00:00`);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const addDays = (source, days) => {
    const next = new Date(source);
    next.setDate(next.getDate() + days);
    return next;
};

const startOfMonth = (source) => new Date(source.getFullYear(), source.getMonth(), 1);
const endOfMonth = (source) => new Date(source.getFullYear(), source.getMonth() + 1, 0);

export const DATE_RANGE_PRESET_OPTIONS = [
    { value: 'all_time', label: 'All Time' },
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'last_7_days', label: 'Last 7 Days' },
    { value: 'last_30_days', label: 'Last 30 Days' },
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
];

export const ATTENDANCE_DATE_RANGE_PRESET_OPTIONS = [
    { value: 'today', label: 'Today' },
    { value: 'last_7_days', label: 'Last 7 Days' },
    { value: 'last_30_days', label: 'Last 30 Days' },
    { value: 'this_month', label: 'This Month' },
];

export const resolveRelativeDateRange = (preset, now = new Date()) => {
    const today = toDateInput(now);
    const key = String(preset || '').trim().toLowerCase();

    switch (key) {
    case 'all_time':
        return { date_from: '', date_to: '' };
    case 'today':
        return { date_from: today, date_to: today };
    case 'yesterday': {
        const yesterday = toDateInput(addDays(now, -1));
        return { date_from: yesterday, date_to: yesterday };
    }
    case 'last_7_days':
        return { date_from: toDateInput(addDays(now, -6)), date_to: today };
    case 'last_30_days':
        return { date_from: toDateInput(addDays(now, -29)), date_to: today };
    case 'this_month':
        return { date_from: toDateInput(startOfMonth(now)), date_to: today };
    case 'last_month': {
        const previousMonth = addDays(startOfMonth(now), -1);
        return {
            date_from: toDateInput(startOfMonth(previousMonth)),
            date_to: toDateInput(endOfMonth(previousMonth)),
        };
    }
    default:
        return { date_from: today, date_to: today };
    }
};

export const resolveDateRange = (
    {
        range_mode: rangeMode,
        range_preset: rangePreset,
        date_from: dateFrom,
        date_to: dateTo,
    },
    {
        defaultMode = 'relative',
        defaultPreset = 'today',
        defaultFrom = '',
        defaultTo = '',
        now = new Date(),
        minToDate = '',
        toBeforeFromStrategy = 'swap',
    } = {},
) => {
    const mode = String(rangeMode || defaultMode).toLowerCase() === 'absolute' ? 'absolute' : 'relative';
    const preset = String(rangePreset || defaultPreset).toLowerCase();

    if (mode === 'relative') {
        const resolved = resolveRelativeDateRange(preset, now);
        return {
            range_mode: 'relative',
            range_preset: preset,
            date_from: resolved.date_from,
            date_to: resolved.date_to,
        };
    }

    const fallbackDate = toDateInput(now);
    const fallbackFrom = String(defaultFrom || fallbackDate).trim();
    const fallbackTo = String(defaultTo || fallbackFrom).trim();

    const parsedFrom = parseDateInput(dateFrom);
    const parsedTo = parseDateInput(dateTo);

    let normalizedFrom = parsedFrom ? toDateInput(parsedFrom) : fallbackFrom;
    let normalizedTo = parsedTo ? toDateInput(parsedTo) : (normalizedFrom || fallbackTo);

    if (normalizedFrom !== '' && normalizedTo !== '' && normalizedFrom > normalizedTo) {
        if (toBeforeFromStrategy === 'clamp_to_from') {
            normalizedTo = normalizedFrom;
        } else {
            [normalizedFrom, normalizedTo] = [normalizedTo, normalizedFrom];
        }
    }

    if (minToDate !== '' && normalizedTo !== '' && normalizedTo < minToDate) {
        normalizedTo = normalizedFrom && normalizedFrom > minToDate ? normalizedFrom : minToDate;
    }

    if (normalizedFrom !== '' && normalizedTo !== '' && normalizedFrom > normalizedTo) {
        if (toBeforeFromStrategy === 'clamp_to_from') {
            normalizedTo = normalizedFrom;
        } else {
            normalizedFrom = normalizedTo;
        }
    }

    return {
        range_mode: 'absolute',
        range_preset: preset || defaultPreset,
        date_from: normalizedFrom,
        date_to: normalizedTo,
    };
};
