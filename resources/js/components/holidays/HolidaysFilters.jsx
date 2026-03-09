import React, { useEffect, useState } from 'react';
import { MonthSelector } from '../shared/MonthSelector';

const DEFAULT_FILTERS = {
    q: '',
    year: String(new Date().getFullYear()),
    month: '',
    branch_id: '',
    holiday_type: 'all',
    status: 'all',
    sort: 'date_asc',
};

function FilterIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
        </svg>
    );
}

export function HolidaysFilters({
    filters,
    defaults,
    yearOptions,
    branches,
    onApply,
    onClear,
    loading,
}) {
    const [draft, setDraft] = useState({
        ...DEFAULT_FILTERS,
        ...defaults,
        ...filters,
        year: String(filters?.year ?? defaults?.year ?? new Date().getFullYear()),
        month: String(filters?.month ?? defaults?.month ?? ''),
    });

    useEffect(() => {
        setDraft({
            ...DEFAULT_FILTERS,
            ...defaults,
            ...filters,
            year: String(filters?.year ?? defaults?.year ?? new Date().getFullYear()),
            month: String(filters?.month ?? defaults?.month ?? ''),
        });
    }, [defaults, filters]);

    const setField = (field, value) => {
        setDraft((prev) => ({
            ...prev,
            [field]: value,
        }));
    };

    const applyFilters = (event) => {
        event.preventDefault();
        onApply({
            ...draft,
            year: Number.parseInt(String(draft.year), 10) || new Date().getFullYear(),
            month: String(draft.month || ''),
        });
    };

    const clearFilters = () => {
        const reset = {
            ...DEFAULT_FILTERS,
            ...defaults,
            year: String(defaults?.year ?? new Date().getFullYear()),
            month: String(defaults?.month ?? ''),
        };
        setDraft(reset);
        onClear();
    };

    const hasActiveFilters = () => {
        const defaultYear = String(defaults?.year ?? new Date().getFullYear());
        return (
            draft.year !== defaultYear ||
            draft.month !== '' ||
            draft.holiday_type !== 'all' ||
            draft.status !== 'all'
        );
    };

    return (
        <section className="hrm-modern-surface rounded-2xl p-5">
            {/* Header */}
            <div className="flex items-center gap-2 mb-4">
                <span
                    className="h-8 w-8 rounded-lg flex items-center justify-center"
                    style={{ background: 'var(--hr-accent-soft)', color: 'var(--hr-accent)' }}
                >
                    <FilterIcon />
                </span>
                <div>
                    <h3 className="text-sm font-bold">Filters</h3>
                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>Refine your holiday list</p>
                </div>
            </div>

            <form onSubmit={applyFilters}>
                {/* Filter Grid - Professional 5-column layout */}
                <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    {/* Year */}
                    <div>
                        <label htmlFor="holiday_year" className="block text-[11px] font-semibold uppercase tracking-[0.08em] mb-1.5" style={{ color: 'var(--hr-text-muted)' }}>
                            Year
                        </label>
                        <select
                            id="holiday_year"
                            className="w-full rounded-lg border px-3 py-2 text-sm bg-transparent"
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={String(draft.year)}
                            onChange={(event) => setField('year', event.target.value)}
                        >
                            {Object.entries(yearOptions ?? {}).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>

                    {/* Month */}
                    <div>
                        <label htmlFor="holiday_month" className="block text-[11px] font-semibold uppercase tracking-[0.08em] mb-1.5" style={{ color: 'var(--hr-text-muted)' }}>
                            Month
                        </label>
                        <MonthSelector id="holiday_month" value={draft.month} onChange={(v) => setField('month', v)} />
                    </div>

                    {/* Holiday Type */}
                    <div>
                        <label htmlFor="holiday_type" className="block text-[11px] font-semibold uppercase tracking-[0.08em] mb-1.5" style={{ color: 'var(--hr-text-muted)' }}>
                            Type
                        </label>
                        <select
                            id="holiday_type"
                            className="w-full rounded-lg border px-3 py-2 text-sm bg-transparent"
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={draft.holiday_type}
                            onChange={(event) => setField('holiday_type', event.target.value)}
                        >
                            <option value="all">All Types</option>
                            <option value="public">Public</option>
                            <option value="company">Company</option>
                            <option value="optional">Optional</option>
                        </select>
                    </div>

                    {/* Status */}
                    <div>
                        <label htmlFor="holiday_status" className="block text-[11px] font-semibold uppercase tracking-[0.08em] mb-1.5" style={{ color: 'var(--hr-text-muted)' }}>
                            Status
                        </label>
                        <select
                            id="holiday_status"
                            className="w-full rounded-lg border px-3 py-2 text-sm bg-transparent"
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={draft.status}
                            onChange={(event) => setField('status', event.target.value)}
                        >
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="mt-4 flex items-center justify-end gap-3">
                    {hasActiveFilters() && (
                        <button
                            type="button"
                            className="text-xs font-medium underline-offset-2 hover:underline"
                            style={{ color: 'var(--hr-text-muted)' }}
                            onClick={clearFilters}
                            disabled={loading}
                        >
                            Reset filters
                        </button>
                    )}
                    <button
                        type="submit"
                        className="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                        disabled={loading}
                    >
                        {loading ? (
                            <>
                                <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <circle cx="12" cy="12" r="10" strokeOpacity="0.25" />
                                    <path d="M12 2a10 10 0 0 1 10 10" />
                                </svg>
                                Applying...
                            </>
                        ) : (
                            'Apply Filters'
                        )}
                    </button>
                </div>
            </form>
        </section>
    );
}
