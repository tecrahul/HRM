import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { AttendanceApi } from '../../services/AttendanceApi';
import { useAttendance } from '../../hooks/useAttendance';
import { AttendanceHeader } from '../../components/attendance/AttendanceHeader';
import { AttendanceInfoCards } from '../../components/attendance/AttendanceInfoCards';
import { AttendanceFilters } from '../../components/attendance/AttendanceFilters';
import { AttendanceTable } from '../../components/attendance/AttendanceTable';
import { AttendanceForm } from '../../components/attendance/AttendanceForm';
import { AttendanceApprovalModal } from '../../components/attendance/AttendanceApprovalModal';
import { AttendanceCorrectionModal } from '../../components/attendance/AttendanceCorrectionModal';
import { PunchPanel } from '../../components/attendance/PunchPanel';
import { usePunchAttendance } from '../../components/attendance/usePunchAttendance';

const TODAY = new Date().toISOString().slice(0, 10);
const MONTH_START = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);

const EMPTY_FILTERS = {
    status: '',
    approval_status: '',
    attendance_date: TODAY,
    use_date_range: '1',
    date_from: MONTH_START,
    date_to: TODAY,
    range_mode: 'absolute',
    range_preset: '',
    department: '',
    branch: '',
    employee_id: '',
};

const parsePayload = (root) => {
    if (!root) {
        return null;
    }

    const raw = root.dataset.payload;
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return null;
    }
};

function ToastStack({ toasts, onDismiss }) {
    if (toasts.length === 0) {
        return null;
    }

    return (
        <div className="fixed top-4 right-4 z-[2300] flex flex-col gap-2">
            {toasts.map((toast) => (
                <button
                    key={toast.id}
                    type="button"
                    className={`rounded-xl border px-4 py-3 text-sm text-left shadow-lg ${
                        toast.tone === 'danger'
                            ? 'border-red-400/50 bg-red-100 text-red-900 dark:border-red-500/60 dark:bg-red-500/20 dark:text-red-100'
                            : 'border-emerald-400/50 bg-emerald-100 text-emerald-900 dark:border-emerald-500/60 dark:bg-emerald-500/20 dark:text-emerald-100'
                    }`}
                    onClick={() => onDismiss(toast.id)}
                >
                    {toast.message}
                </button>
            ))}
        </div>
    );
}

function AttendancePage({ payload }) {
    const formRef = useRef(null);

    const api = useMemo(() => new AttendanceApi({
        routes: payload.routes ?? {},
        csrfToken: payload.csrfToken,
    }), [payload.csrfToken, payload.routes]);

    const {
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
    } = useAttendance(api, payload);

    const capabilities = payload.capabilities ?? {};

    const [formOpen, setFormOpen] = useState(false);
    const [editingRecord, setEditingRecord] = useState(null);
    const [formResetKey, setFormResetKey] = useState(0);

    const [deleteTarget, setDeleteTarget] = useState(null);
    const [rejectTarget, setRejectTarget] = useState(null);
    const [rejectReason, setRejectReason] = useState('');
    const [rejectError, setRejectError] = useState('');

    const [correctionTarget, setCorrectionTarget] = useState(null);

    const [monthAction, setMonthAction] = useState(null);
    const [unlockReason, setUnlockReason] = useState('');
    const [unlockError, setUnlockError] = useState('');

    const [toasts, setToasts] = useState(() => {
        const initial = [];
        if (payload.flash?.status) {
            initial.push({ id: Date.now(), tone: 'success', message: payload.flash.status });
        }
        if (payload.flash?.error) {
            initial.push({ id: Date.now() + 1, tone: 'danger', message: payload.flash.error });
        }
        return initial;
    });

    useEffect(() => {
        fetchAttendance({}, meta.currentPage || 1, true).catch(() => {});
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (!formOpen) {
            return;
        }

        const timer = window.setTimeout(() => {
            formRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);

        return () => window.clearTimeout(timer);
    }, [formOpen, editingRecord?.id]);

    const pushToast = (message, tone = 'success') => {
        const id = Date.now() + Math.floor(Math.random() * 1000);
        setToasts((prev) => [...prev, { id, tone, message }]);
        window.setTimeout(() => {
            setToasts((prev) => prev.filter((toast) => toast.id !== id));
        }, 3500);
    };

    const dismissToast = (id) => {
        setToasts((prev) => prev.filter((toast) => toast.id !== id));
    };

    const closeForm = () => {
        if (submitting) {
            return;
        }

        setFormOpen(false);
        setEditingRecord(null);
        setFormResetKey((prev) => prev + 1);
    };

    const openCreateForm = () => {
        setEditingRecord(null);
        setFormResetKey((prev) => prev + 1);
        setFormOpen(true);
    };

    // Open create form when navigated with action=create or action=mark
    useEffect(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const action = String(params.get('action') || '');
            if (Boolean(capabilities?.canCreate) && (action === 'create' || action === 'mark')) {
                openCreateForm();
            }
        } catch (_e) {}
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [capabilities?.canCreate]);

    const openEditForm = (record) => {
        setEditingRecord(record);
        setFormOpen(true);
    };

    const mapErrors = (apiError, setFormErrors) => {
        if (!apiError?.errors || typeof setFormErrors !== 'function') {
            return;
        }

        setFormErrors(Object.entries(apiError.errors).reduce((acc, [field, messages]) => ({
            ...acc,
            [field]: Array.isArray(messages) ? messages[0] : messages,
        }), {}));
    };

    const handleSaveAttendance = async (values, setFormErrors) => {
        try {
            if (editingRecord) {
                const response = await updateAttendance(editingRecord.id, values);
                pushToast(response?.message || 'Attendance updated successfully.');
            } else {
                const response = await createAttendance(values);
                pushToast(response?.message || 'Attendance marked successfully.');
            }

            closeForm();
        } catch (apiError) {
            mapErrors(apiError, setFormErrors);
            pushToast(apiError.message || 'Unable to save attendance.', 'danger');
        }
    };

    const handleApplyFilters = (nextFilters, exportOnly = false) => {
        if (exportOnly && payload.routes?.exportCsv) {
            const params = new URLSearchParams();
            Object.entries(nextFilters).forEach(([key, value]) => {
                if (String(value || '').trim() !== '') {
                    params.set(key, String(value));
                }
            });
            window.location.href = `${payload.routes.exportCsv}?${params.toString()}`;
            return;
        }

        fetchAttendance(nextFilters, 1).catch((apiError) => {
            pushToast(apiError.message || 'Unable to apply filters.', 'danger');
        });
    };

    const handleResetFilters = () => {
        fetchAttendance(EMPTY_FILTERS, 1).catch((apiError) => {
            pushToast(apiError.message || 'Unable to reset filters.', 'danger');
        });
    };

    const handleDelete = async () => {
        if (!deleteTarget) {
            return;
        }

        try {
            const response = await deleteAttendance(deleteTarget.id);
            pushToast(response?.message || 'Attendance deleted successfully.');
            setDeleteTarget(null);
            if (editingRecord?.id === deleteTarget.id) {
                closeForm();
            }
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to delete attendance.', 'danger');
        }
    };

    const handleApprove = async (record) => {
        try {
            const response = await approveAttendance(record.id);
            pushToast(response?.message || 'Attendance approved successfully.');
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to approve attendance.', 'danger');
        }
    };

    const handleReject = async () => {
        if (!rejectTarget) {
            return;
        }

        if (rejectReason.trim() === '') {
            setRejectError('Rejection reason is required.');
            return;
        }

        try {
            const response = await rejectAttendance(rejectTarget.id, rejectReason.trim());
            pushToast(response?.message || 'Attendance rejected successfully.');
            setRejectTarget(null);
            setRejectReason('');
            setRejectError('');
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to reject attendance.', 'danger');
        }
    };

    const handleCorrectionSubmit = async (payloadBody) => {
        if (!correctionTarget) {
            return;
        }

        try {
            const response = await submitCorrection(correctionTarget.id, payloadBody);
            pushToast(response?.message || 'Correction request submitted.');
            setCorrectionTarget(null);
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to submit correction request.', 'danger');
        }
    };

    const handleMonthAction = async () => {
        if (!monthAction?.month) {
            return;
        }

        try {
            if (monthAction.type === 'lock') {
                const response = await lockMonth(monthAction.month);
                pushToast(response?.message || 'Month locked successfully.');
                setMonthAction(null);
                return;
            }

            if (unlockReason.trim() === '') {
                setUnlockError('Unlock reason is required.');
                return;
            }

            const response = await unlockMonth(monthAction.month, unlockReason.trim());
            pushToast(response?.message || 'Month unlocked successfully.');
            setMonthAction(null);
            setUnlockReason('');
            setUnlockError('');
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to process month action.', 'danger');
        }
    };

    const handlePunchIn = async () => {
        try {
            const response = await checkIn();
            pushToast(response?.message || 'Checked in successfully.');
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to punch in.', 'danger');
        }
    };

    const handlePunchOut = async () => {
        try {
            const response = await checkOut();
            pushToast(response?.message || 'Checked out successfully.');
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to punch out.', 'danger');
        }
    };

    // Punch panel state for non-admin users
    const [punchOpen, setPunchOpen] = useState(false);
    const punchCtrl = usePunchAttendance({
        api,
        punch,
        onSuccess: async (msg) => {
            // Refresh data and show a toast
            try {
                await fetchAttendance({}, meta.currentPage || 1);
            } catch (_) {}
            if (msg) {
                pushToast(msg);
            }
        },
        onClose: () => setPunchOpen(false),
    });

    return (
        <div className="space-y-5">
            <ToastStack toasts={toasts} onDismiss={dismissToast} />

            <AttendanceHeader
                canCreate={Boolean(capabilities.canCreate)}
                canEdit={Boolean(capabilities.canEdit)}
                canApprove={Boolean(capabilities.canApprove)}
                pendingApprovals={stats.pendingApprovals ?? 0}
                punch={punch}
                onOpenForm={openCreateForm}
                onOpenPunchPanel={() => setPunchOpen(true)}
                onPunchIn={handlePunchIn}
                onPunchOut={handlePunchOut}
                submitting={submitting}
                punchLink={(punch?.nextAction === 'check_in')
                    ? (payload.routes?.punchInPage || '#')
                    : (punch?.nextAction === 'check_out')
                        ? (payload.routes?.punchOutPage || '#')
                        : (payload.routes?.smartPunchPage || '#')}
            />

            {!Boolean(capabilities.isEmployeeOnly) && (
                <AttendanceInfoCards
                    stats={stats}
                    canApprove={Boolean(capabilities.canApprove)}
                    currentUser={payload.currentUser}
                    isEmployeeOnly={false}
                />
            )}

            {error ? (
                <section className="hrm-modern-surface rounded-2xl p-4">
                    <p className="text-sm font-semibold text-red-700 dark:text-red-300">{error}</p>
                </section>
            ) : null}

            {initialLoading ? (
                <section className="hrm-modern-surface rounded-2xl p-8 text-center">
                    <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Loading attendance workspace...</p>
                </section>
            ) : (
                <>
                    <section
                        ref={formRef}
                        className="overflow-hidden"
                        style={{
                            maxHeight: formOpen ? '1600px' : '0px',
                            opacity: formOpen ? 1 : 0,
                            transform: formOpen ? 'translateY(0)' : 'translateY(-8px)',
                            transition: 'max-height 360ms ease, opacity 260ms ease, transform 260ms ease',
                            pointerEvents: formOpen ? 'auto' : 'none',
                        }}
                    >
                        <AttendanceForm
                            key={editingRecord ? `edit-${editingRecord.id}` : `create-${formResetKey}`}
                            capabilities={capabilities}
                            options={options}
                            currentUser={payload.currentUser}
                            editingRecord={editingRecord}
                            submitting={submitting}
                            onSubmit={handleSaveAttendance}
                            onCancel={closeForm}
                            onClose={closeForm}
                            onSearchEmployees={(query) => api.searchEmployees(query)}
                        />
                    </section>

                    <AttendanceFilters
                        filters={filters}
                        options={options}
                        permissions={capabilities}
                        loading={loading}
                        submitting={submitting}
                        selectedMonthLocked={Boolean(locks.selectedMonthLocked)}
                        onFilterChange={() => {}}
                        onApply={handleApplyFilters}
                        onClear={handleResetFilters}
                        onLockMonth={(month) => setMonthAction({ type: 'lock', month })}
                        onUnlockMonth={(month) => setMonthAction({ type: 'unlock', month })}
                        onSearchEmployees={(query, scopedFilters) => api.searchEmployees(query, scopedFilters)}
                    />

                    <AttendanceTable
                        records={records}
                        meta={meta}
                        loading={loading}
                        submitting={submitting}
                        capabilities={capabilities}
                        onEdit={openEditForm}
                        onDelete={setDeleteTarget}
                        onApprove={handleApprove}
                        onReject={(record) => {
                            setRejectTarget(record);
                            setRejectReason('');
                            setRejectError('');
                        }}
                        onRequestCorrection={setCorrectionTarget}
                        onPageChange={(page) => fetchAttendance({}, page).catch(() => {})}
                    />
                </>
            )}

            <AttendanceApprovalModal
                open={Boolean(deleteTarget)}
                title="Delete Attendance"
                description={deleteTarget ? `Delete attendance for ${deleteTarget.employee?.name}?` : ''}
                busy={submitting}
                confirmLabel="Delete"
                confirmTone="danger"
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            />

            <AttendanceApprovalModal
                open={Boolean(rejectTarget)}
                title="Reject Attendance"
                description={rejectTarget ? `Provide rejection reason for ${rejectTarget.employee?.name}.` : ''}
                busy={submitting}
                confirmLabel="Reject"
                confirmTone="warning"
                onConfirm={handleReject}
                onCancel={() => {
                    setRejectTarget(null);
                    setRejectReason('');
                    setRejectError('');
                }}
            >
                <div className="space-y-2">
                    <textarea
                        rows={4}
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent resize-y"
                        style={{ borderColor: rejectError ? '#f87171' : 'var(--hr-line)' }}
                        value={rejectReason}
                        onChange={(event) => {
                            setRejectReason(event.target.value);
                            if (rejectError) {
                                setRejectError('');
                            }
                        }}
                        placeholder="Reason for rejection..."
                        disabled={submitting}
                    />
                    {rejectError ? <p className="text-xs text-red-500 dark:text-red-300">{rejectError}</p> : null}
                </div>
            </AttendanceApprovalModal>

            <AttendanceCorrectionModal
                open={Boolean(correctionTarget)}
                record={correctionTarget}
                busy={submitting}
                onSubmit={handleCorrectionSubmit}
                onCancel={() => setCorrectionTarget(null)}
            />

            <AttendanceApprovalModal
                open={Boolean(monthAction)}
                title={monthAction?.type === 'unlock' ? 'Unlock Month' : 'Lock Month'}
                description={monthAction?.type === 'unlock'
                    ? 'Unlocking allows attendance updates for this month.'
                    : 'Locked months cannot be edited.'}
                busy={submitting}
                confirmLabel={monthAction?.type === 'unlock' ? 'Unlock Month' : 'Lock Month'}
                confirmTone={monthAction?.type === 'unlock' ? 'primary' : 'warning'}
                onConfirm={handleMonthAction}
                onCancel={() => {
                    setMonthAction(null);
                    setUnlockReason('');
                    setUnlockError('');
                }}
            >
                {monthAction?.type === 'unlock' ? (
                    <div className="space-y-2">
                        <textarea
                            rows={3}
                            className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent resize-y"
                            style={{ borderColor: unlockError ? '#f87171' : 'var(--hr-line)' }}
                            value={unlockReason}
                            onChange={(event) => {
                                setUnlockReason(event.target.value);
                                if (unlockError) {
                                    setUnlockError('');
                                }
                            }}
                            placeholder="Unlock reason"
                            disabled={submitting}
                        />
                        {unlockError ? <p className="text-xs text-red-500 dark:text-red-300">{unlockError}</p> : null}
                    </div>
                ) : null}
            </AttendanceApprovalModal>

            {/* Soft punch panel for self users without edit/create admin perms */}
            <PunchPanel
                open={punchOpen}
                punch={punch}
                submitting={punchCtrl.submitting}
                successMessage={punchCtrl.successMessage}
                errorMessage={punchCtrl.error}
                reason={punchCtrl.reason}
                onReasonChange={punchCtrl.setReason}
                onSubmit={punchCtrl.submit}
                onClose={() => setPunchOpen(false)}
            />
        </div>
    );
}

export function mountAttendancePage() {
    const rootElement = document.getElementById('attendance-page-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        return;
    }

    createRoot(rootElement).render(<AttendancePage payload={payload} />);
}
