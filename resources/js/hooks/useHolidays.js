import { useCallback, useMemo, useState } from 'react';

const INITIAL_META = {
    currentPage: 1,
    lastPage: 1,
    perPage: 12,
    total: 0,
    from: 0,
    to: 0,
};

export function useHolidays(api, initialPayload = {}) {
    const [holidays, setHolidays] = useState(initialPayload.holidays?.data ?? []);
    const [meta, setMeta] = useState(initialPayload.holidays?.meta ?? INITIAL_META);
    const [stats, setStats] = useState(initialPayload.stats ?? { total: 0, upcoming: 0, past: 0 });
    const [filters, setFilters] = useState(initialPayload.filters ?? {
        q: '',
        year: new Date().getFullYear(),
        branch_id: '',
        holiday_type: 'all',
        status: 'all',
        sort: 'date_asc',
    });
    const [loading, setLoading] = useState(false);
    const [initialLoading, setInitialLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const queryParams = useMemo(() => ({
        q: filters.q || undefined,
        year: filters.year || undefined,
        branch_id: filters.branch_id || undefined,
        holiday_type: filters.holiday_type || 'all',
        status: filters.status || 'all',
        sort: filters.sort || 'date_asc',
    }), [filters.branch_id, filters.holiday_type, filters.q, filters.sort, filters.status, filters.year]);

    const fetchHolidays = useCallback(async (overrides = {}, page = 1, useInitialLoader = false) => {
        if (!api) {
            return null;
        }

        setError('');
        if (useInitialLoader) {
            setInitialLoading(true);
        } else {
            setLoading(true);
        }

        try {
            const response = await api.getHolidays({
                ...queryParams,
                ...overrides,
                page,
                per_page: meta.perPage || 12,
            });

            setHolidays(response.data ?? []);
            setMeta(response.meta ?? INITIAL_META);
            setStats(response.stats ?? { total: 0, upcoming: 0, past: 0 });
            setFilters((prev) => ({
                ...prev,
                ...(response.filters ?? {}),
                ...overrides,
            }));

            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to load holidays.');
            throw apiError;
        } finally {
            setLoading(false);
            setInitialLoading(false);
        }
    }, [api, meta.perPage, queryParams]);

    const createHoliday = useCallback(async (payload) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.createHoliday(payload);
            await fetchHolidays({}, 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to create holiday.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchHolidays]);

    const updateHoliday = useCallback(async (holidayId, payload) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.updateHoliday(holidayId, payload);
            await fetchHolidays({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to update holiday.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchHolidays, meta.currentPage]);

    const deleteHoliday = useCallback(async (holidayId) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.deleteHoliday(holidayId);
            const nextPage = holidays.length === 1 && (meta.currentPage || 1) > 1
                ? (meta.currentPage || 1) - 1
                : (meta.currentPage || 1);
            await fetchHolidays({}, nextPage);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to delete holiday.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchHolidays, holidays.length, meta.currentPage]);

    return {
        holidays,
        meta,
        stats,
        filters,
        loading,
        initialLoading,
        submitting,
        error,
        setError,
        fetchHolidays,
        createHoliday,
        updateHoliday,
        deleteHoliday,
    };
}
