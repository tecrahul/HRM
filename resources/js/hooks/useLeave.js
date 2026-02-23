import { useCallback, useMemo, useState } from 'react';

export function useLeave(api, payload) {
    const initialList = payload?.leaves ?? {};
    const defaultLeaveFilters = payload?.defaults?.leaveFilters ?? {};
    const initialMeta = initialList.meta ?? {
        currentPage: 1,
        lastPage: 1,
        perPage: 12,
        total: 0,
        from: 0,
        to: 0,
    };

    const [leaves, setLeaves] = useState(initialList.data ?? []);
    const [meta, setMeta] = useState(initialMeta);
    const [stats, setStats] = useState(payload?.stats ?? {});
    const [filters, setFilters] = useState(payload?.filters ?? {
        q: '',
        status: 'all',
        date_from: defaultLeaveFilters.date_from ?? '',
        date_to: defaultLeaveFilters.date_to ?? '',
        employee_id: '',
        range_mode: 'absolute',
        range_preset: '',
    });
    const [loading, setLoading] = useState(false);
    const [initialLoading, setInitialLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const queryParams = useMemo(() => ({
        q: filters.q || undefined,
        status: filters.status || 'all',
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        range_mode: filters.range_mode || undefined,
        range_preset: filters.range_preset || undefined,
        employee_id: filters.employee_id || undefined,
    }), [
        filters.date_from,
        filters.date_to,
        filters.employee_id,
        filters.q,
        filters.range_mode,
        filters.range_preset,
        filters.status,
    ]);

    const fetchLeaves = useCallback(async (overrides = {}, page = 1, useInitialLoader = false) => {
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
            const mergedFilters = {
                ...queryParams,
                ...overrides,
            };

            const response = await api.getLeaves({
                ...mergedFilters,
                page,
                per_page: meta.perPage || 12,
            });

            setLeaves(response?.data ?? []);
            setMeta(response?.meta ?? initialMeta);
            setStats(response?.stats ?? {});
            setFilters((prev) => ({
                ...prev,
                ...(response?.filters ?? {}),
                ...overrides,
            }));

            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to load leave requests.');
            throw apiError;
        } finally {
            setLoading(false);
            setInitialLoading(false);
        }
    }, [api, initialMeta, meta.perPage, queryParams]);

    const updateFilters = useCallback((partial) => {
        setFilters((prev) => ({
            ...prev,
            ...partial,
        }));
    }, []);

    const createLeave = useCallback(async (payloadBody) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.createLeave(payloadBody);
            await fetchLeaves({}, 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to create leave request.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchLeaves]);

    const updateLeave = useCallback(async (leaveId, payloadBody) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.updateLeave(leaveId, payloadBody);
            await fetchLeaves({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to update leave request.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchLeaves, meta.currentPage]);

    const deleteLeave = useCallback(async (leaveId) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.deleteLeave(leaveId);
            const nextPage = leaves.length === 1 && (meta.currentPage || 1) > 1
                ? (meta.currentPage || 1) - 1
                : (meta.currentPage || 1);
            await fetchLeaves({}, nextPage);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to delete leave request.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchLeaves, leaves.length, meta.currentPage]);

    const approveLeave = useCallback(async (leaveId, note = '') => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.approveLeave(leaveId, note);
            await fetchLeaves({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to approve leave request.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchLeaves, meta.currentPage]);

    const rejectLeave = useCallback(async (leaveId, note) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.rejectLeave(leaveId, note);
            await fetchLeaves({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to reject leave request.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchLeaves, meta.currentPage]);

    const bulkApproveLeaves = useCallback(async (leaveIds) => {
        setSubmitting(true);
        setError('');

        try {
            const responses = await Promise.all(leaveIds.map((leaveId) => api.approveLeave(leaveId)));
            await fetchLeaves({}, meta.currentPage || 1);
            return responses;
        } catch (apiError) {
            setError(apiError.message || 'Unable to approve selected leave requests.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchLeaves, meta.currentPage]);

    return {
        leaves,
        meta,
        stats,
        filters,
        loading,
        initialLoading,
        submitting,
        error,
        setError,
        fetchLeaves,
        updateFilters,
        createLeave,
        updateLeave,
        deleteLeave,
        approveLeave,
        rejectLeave,
        bulkApproveLeaves,
    };
}
