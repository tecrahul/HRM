import { useCallback, useMemo, useState } from 'react';

const INITIAL_META = {
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
    from: 0,
    to: 0,
};

const INITIAL_FILTERS = {
    status: '',
    approval_status: '',
    attendance_date: new Date().toISOString().slice(0, 10),
    use_date_range: '1',
    date_from: new Date().toISOString().slice(0, 10),
    date_to: new Date().toISOString().slice(0, 10),
    range_mode: 'absolute',
    range_preset: '',
    department: '',
    branch: '',
    employee_id: '',
};

export function useAttendance(api, initialPayload = {}) {
    const [records, setRecords] = useState(initialPayload.data ?? []);
    const [meta, setMeta] = useState(initialPayload.meta ?? INITIAL_META);
    const [stats, setStats] = useState(initialPayload.stats ?? {});
    const [punch, setPunch] = useState(initialPayload.punch ?? {});
    const [filters, setFilters] = useState(initialPayload.filters ?? INITIAL_FILTERS);
    const [options, setOptions] = useState(initialPayload.options ?? {});
    const [locks, setLocks] = useState(initialPayload.locks ?? { selectedMonthLocked: false });
    const [loading, setLoading] = useState(false);
    const [initialLoading, setInitialLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const listParams = useMemo(() => ({
        status: filters.status || undefined,
        approval_status: filters.approval_status || undefined,
        attendance_date: filters.attendance_date || undefined,
        use_date_range: filters.use_date_range || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        range_mode: filters.range_mode || undefined,
        range_preset: filters.range_preset || undefined,
        department: filters.department || undefined,
        branch: filters.branch || undefined,
        employee_id: filters.employee_id || undefined,
    }), [
        filters.approval_status,
        filters.attendance_date,
        filters.branch,
        filters.date_from,
        filters.date_to,
        filters.department,
        filters.employee_id,
        filters.range_mode,
        filters.range_preset,
        filters.status,
        filters.use_date_range,
    ]);

    const fetchAttendance = useCallback(async (overrides = {}, page = 1, useInitialLoader = false) => {
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
            const response = await api.getAttendance({
                ...listParams,
                ...overrides,
                page,
            });

            setRecords(response.data ?? []);
            setMeta(response.meta ?? INITIAL_META);
            setStats(response.stats ?? {});
            setPunch(response.punch ?? {});
            setOptions(response.options ?? {});
            setLocks(response.locks ?? { selectedMonthLocked: false });
            setFilters((prev) => ({
                ...prev,
                ...(response.filters ?? {}),
                ...overrides,
            }));

            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to load attendance records.');
            throw apiError;
        } finally {
            setLoading(false);
            setInitialLoading(false);
        }
    }, [api, listParams]);

    const createAttendance = useCallback(async (payload) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.createAttendance(payload);
            await fetchAttendance({}, 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to create attendance.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance]);

    const updateAttendance = useCallback(async (attendanceId, payload) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.updateAttendance(attendanceId, payload);
            await fetchAttendance({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to update attendance.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance, meta.currentPage]);

    const deleteAttendance = useCallback(async (attendanceId) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.deleteAttendance(attendanceId);
            const nextPage = records.length === 1 && (meta.currentPage || 1) > 1
                ? (meta.currentPage || 1) - 1
                : (meta.currentPage || 1);
            await fetchAttendance({}, nextPage);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to delete attendance.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance, meta.currentPage, records.length]);

    const approveAttendance = useCallback(async (attendanceId, note = '') => {
        setSubmitting(true);
        setError('');

        const previous = records;
        setRecords((prev) => prev.map((record) => (
            record.id === attendanceId
                ? {
                    ...record,
                    approvalStatus: 'approved',
                    approvalStatusLabel: 'Approved',
                    canApprove: false,
                    canReject: false,
                }
                : record
        )));

        try {
            const response = await api.approveAttendance(attendanceId, note);
            await fetchAttendance({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setRecords(previous);
            setError(apiError.message || 'Unable to approve attendance.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance, meta.currentPage, records]);

    const rejectAttendance = useCallback(async (attendanceId, reason) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.rejectAttendance(attendanceId, reason);
            await fetchAttendance({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to reject attendance.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance, meta.currentPage]);

    const submitCorrection = useCallback(async (attendanceId, payload) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.requestCorrection(attendanceId, payload);
            await fetchAttendance({}, meta.currentPage || 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to submit correction request.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance, meta.currentPage]);

    const lockMonth = useCallback(async (month) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.lockMonth(month);
            await fetchAttendance({}, 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to lock month.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance]);

    const unlockMonth = useCallback(async (month, reason) => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.unlockMonth(month, reason);
            await fetchAttendance({}, 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to unlock month.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance]);

    const checkIn = useCallback(async (notes = '') => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.checkIn(notes);
            await fetchAttendance({}, 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to punch in.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance]);

    const checkOut = useCallback(async (notes = '') => {
        setSubmitting(true);
        setError('');

        try {
            const response = await api.checkOut(notes);
            await fetchAttendance({}, 1);
            return response;
        } catch (apiError) {
            setError(apiError.message || 'Unable to punch out.');
            throw apiError;
        } finally {
            setSubmitting(false);
        }
    }, [api, fetchAttendance]);

    return {
        records,
        meta,
        stats,
        punch,
        filters,
        options,
        locks,
        loading,
        initialLoading,
        submitting,
        error,
        setError,
        setFilters,
        fetchAttendance,
        createAttendance,
        updateAttendance,
        deleteAttendance,
        approveAttendance,
        rejectAttendance,
        submitCorrection,
        lockMonth,
        unlockMonth,
        checkIn,
        checkOut,
    };
}
