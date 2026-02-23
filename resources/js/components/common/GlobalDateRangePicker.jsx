import React, { useMemo } from 'react';
import {
    DATE_RANGE_PRESET_OPTIONS,
    resolveDateRange,
    toDateInput,
} from '../../utils/dateRange';

const inputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';

export function GlobalDateRangePicker({
    idPrefix = 'global_date_range',
    value = {},
    onChange,
    disabled = false,
    presets = DATE_RANGE_PRESET_OPTIONS,
    defaultMode = 'relative',
    defaultPreset = 'today',
    defaultFrom = '',
    defaultTo = '',
    minToDate = '',
    showModeToggle = true,
    forceMode = '',
    toBeforeFromStrategy = 'swap',
}) {
    const today = useMemo(() => toDateInput(new Date()), []);
    const forcedMode = String(forceMode || '').toLowerCase();
    const mode = forcedMode === 'absolute' || forcedMode === 'relative'
        ? forcedMode
        : (String(value.range_mode || defaultMode).toLowerCase() === 'absolute' ? 'absolute' : 'relative');
    const preset = String(value.range_preset || defaultPreset).toLowerCase();

    const absoluteFrom = value.date_from || defaultFrom || today;
    const absoluteTo = value.date_to || defaultTo || absoluteFrom || today;

    const emit = (partial) => {
        if (typeof onChange !== 'function') {
            return;
        }

        onChange(partial);
    };

    const setMode = (nextMode) => {
        const resolved = resolveDateRange(
            {
                range_mode: nextMode,
                range_preset: preset,
                date_from: value.date_from,
                date_to: value.date_to,
            },
            {
                defaultMode,
                defaultPreset,
                defaultFrom: defaultFrom || today,
                defaultTo: defaultTo || today,
                minToDate,
                toBeforeFromStrategy,
            },
        );

        emit({
            ...resolved,
            use_date_range: '1',
            attendance_date: resolved.date_from || today,
        });
    };

    const setPreset = (nextPreset) => {
        const resolved = resolveDateRange(
            {
                range_mode: 'relative',
                range_preset: nextPreset,
                date_from: value.date_from,
                date_to: value.date_to,
            },
            {
                defaultMode,
                defaultPreset: nextPreset,
                defaultFrom: defaultFrom || today,
                defaultTo: defaultTo || today,
                minToDate,
                toBeforeFromStrategy,
            },
        );

        emit({
            ...resolved,
            use_date_range: '1',
            attendance_date: resolved.date_from || today,
        });
    };

    const setAbsolute = (field, fieldValue) => {
        const resolved = resolveDateRange(
            {
                range_mode: 'absolute',
                range_preset: preset,
                date_from: field === 'date_from' ? fieldValue : absoluteFrom,
                date_to: field === 'date_to' ? fieldValue : absoluteTo,
            },
            {
                defaultMode,
                defaultPreset,
                defaultFrom: defaultFrom || today,
                defaultTo: defaultTo || today,
                minToDate,
                toBeforeFromStrategy,
            },
        );

        emit({
            ...resolved,
            use_date_range: '1',
            attendance_date: resolved.date_from || today,
        });
    };

    return (
        <div className="space-y-3">
            {showModeToggle && forcedMode === '' ? (
                <div className="inline-flex rounded-xl border p-1" style={{ borderColor: 'var(--hr-line)' }}>
                    <button
                        type="button"
                        className={`rounded-lg px-3 py-1.5 text-xs font-semibold ${
                            mode === 'relative'
                                ? 'bg-teal-500/15 text-teal-700 dark:text-teal-300'
                                : ''
                        }`}
                        onClick={() => setMode('relative')}
                        disabled={disabled}
                    >
                        Relative
                    </button>
                    <button
                        type="button"
                        className={`rounded-lg px-3 py-1.5 text-xs font-semibold ${
                            mode === 'absolute'
                                ? 'bg-teal-500/15 text-teal-700 dark:text-teal-300'
                                : ''
                        }`}
                        onClick={() => setMode('absolute')}
                        disabled={disabled}
                    >
                        Absolute
                    </button>
                </div>
            ) : null}

            {mode === 'relative' ? (
                <select
                    id={`${idPrefix}_preset`}
                    className={inputClassName}
                    style={{ borderColor: 'var(--hr-line)' }}
                    value={preset}
                    onChange={(event) => setPreset(event.target.value)}
                    disabled={disabled}
                >
                    {presets.map((option) => (
                        <option key={option.value} value={option.value}>{option.label}</option>
                    ))}
                </select>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label
                            htmlFor={`${idPrefix}_from`}
                            className="mb-2 block text-xs font-semibold uppercase tracking-[0.08em]"
                            style={{ color: 'var(--hr-text-muted)' }}
                        >
                            From
                        </label>
                        <input
                            id={`${idPrefix}_from`}
                            type="date"
                            className={inputClassName}
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={absoluteFrom}
                            onChange={(event) => setAbsolute('date_from', event.target.value)}
                            disabled={disabled}
                        />
                    </div>
                    <div>
                        <label
                            htmlFor={`${idPrefix}_to`}
                            className="mb-2 block text-xs font-semibold uppercase tracking-[0.08em]"
                            style={{ color: 'var(--hr-text-muted)' }}
                        >
                            To
                        </label>
                        <input
                            id={`${idPrefix}_to`}
                            type="date"
                            className={inputClassName}
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={absoluteTo}
                            min={absoluteFrom || minToDate || undefined}
                            onChange={(event) => setAbsolute('date_to', event.target.value)}
                            disabled={disabled}
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
