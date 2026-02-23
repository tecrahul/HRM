import React, { useEffect, useState } from 'react';

const DEFAULT_FILTERS = {
    q: '',
    year: String(new Date().getFullYear()),
    branch_id: '',
    holiday_type: 'all',
    status: 'all',
    sort: 'date_asc',
};

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
    });

    useEffect(() => {
        setDraft({
            ...DEFAULT_FILTERS,
            ...defaults,
            ...filters,
            year: String(filters?.year ?? defaults?.year ?? new Date().getFullYear()),
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
        });
    };

    const clearFilters = () => {
        const reset = {
            ...DEFAULT_FILTERS,
            ...defaults,
            year: String(defaults?.year ?? new Date().getFullYear()),
        };
        setDraft(reset);
        onClear();
    };

    return (
        <section className="hrm-modern-surface rounded-2xl p-4">
            <form className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3" onSubmit={applyFilters}>
                <div className="xl:col-span-2">
                    <label htmlFor="holiday_search" className="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style={{ color: 'var(--hr-text-muted)' }}>
                        Search
                    </label>
                    <input
                        id="holiday_search"
                        type="search"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={draft.q}
                        onChange={(event) => setField('q', event.target.value)}
                        placeholder="Search holiday name"
                    />
                </div>

                <div>
                    <label htmlFor="holiday_year" className="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style={{ color: 'var(--hr-text-muted)' }}>
                        Year
                    </label>
                    <select
                        id="holiday_year"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={String(draft.year)}
                        onChange={(event) => setField('year', event.target.value)}
                    >
                        {Object.entries(yearOptions ?? {}).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="holiday_branch" className="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style={{ color: 'var(--hr-text-muted)' }}>
                        Branch
                    </label>
                    <select
                        id="holiday_branch"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={draft.branch_id}
                        onChange={(event) => setField('branch_id', event.target.value)}
                    >
                        <option value="">All Branches</option>
                        {(branches ?? []).map((branch) => (
                            <option key={branch.id} value={String(branch.id)}>{branch.name}</option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="holiday_type" className="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style={{ color: 'var(--hr-text-muted)' }}>
                        Holiday Type
                    </label>
                    <select
                        id="holiday_type"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={draft.holiday_type}
                        onChange={(event) => setField('holiday_type', event.target.value)}
                    >
                        <option value="all">All Types</option>
                        <option value="public">Public Holiday</option>
                        <option value="company">Company Holiday</option>
                        <option value="optional">Optional Holiday</option>
                    </select>
                </div>

                <div>
                    <label htmlFor="holiday_status" className="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style={{ color: 'var(--hr-text-muted)' }}>
                        Status
                    </label>
                    <select
                        id="holiday_status"
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={draft.status}
                        onChange={(event) => setField('status', event.target.value)}
                    >
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div className="xl:col-span-6 flex items-center gap-2 justify-start">
                    <button
                        type="button"
                        className="rounded-xl border px-3.5 py-2.5 text-sm font-semibold"
                        style={{ borderColor: 'var(--hr-line)' }}
                        onClick={clearFilters}
                        disabled={loading}
                    >
                        Clear
                    </button>
                    <button
                        type="submit"
                        className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                        disabled={loading}
                    >
                        {loading ? 'Applying...' : 'Apply Filters'}
                    </button>
                </div>
            </form>
        </section>
    );
}
