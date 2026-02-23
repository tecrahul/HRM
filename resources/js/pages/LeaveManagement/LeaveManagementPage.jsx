import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { LeaveApi } from '../../services/LeaveApi';
import { useLeave } from '../../hooks/useLeave';
import { LeaveForm } from '../../components/leave/LeaveForm';
import { LeaveFilters } from '../../components/leave/LeaveFilters';
import { LeaveList } from '../../components/leave/LeaveList';
import { LeaveModal } from '../../components/leave/LeaveModal';
import { QuickInfoCard } from '../../components/common/QuickInfoCard';
import { QuickInfoGrid } from '../../components/common/QuickInfoGrid';

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
                    className="rounded-xl border px-4 py-3 text-sm text-left shadow-lg"
                    style={{
                        borderColor: toast.tone === 'danger' ? 'rgb(248 113 113 / 0.45)' : 'rgb(16 185 129 / 0.45)',
                        background: toast.tone === 'danger'
                            ? 'linear-gradient(120deg, rgb(254 226 226 / 0.95), rgb(254 242 242 / 0.95))'
                            : 'linear-gradient(120deg, rgb(220 252 231 / 0.95), rgb(236 253 245 / 0.95))',
                    }}
                    onClick={() => onDismiss(toast.id)}
                >
                    {toast.message}
                </button>
            ))}
        </div>
    );
}

function ClipboardIcon() {
    return (
        <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <rect x="8" y="3" width="8" height="4" rx="1.2" />
            <path d="M9 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3" />
        </svg>
    );
}

function PendingIcon() {
    return (
        <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v6l4 2" />
        </svg>
    );
}

function ApprovedIcon() {
    return (
        <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="m8.5 12.5 2.2 2.2 4.8-5.2" />
        </svg>
    );
}

function RejectedIcon() {
    return (
        <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="m9 9 6 6" />
            <path d="m15 9-6 6" />
        </svg>
    );
}

function BalanceIcon() {
    return (
        <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M4 7h16" />
            <path d="M6 4h12a2 2 0 0 1 2 2v10a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V6a2 2 0 0 1 2-2Z" />
            <path d="M8 12h8" />
        </svg>
    );
}

function LeaveManagementPage({ payload }) {
    const formRef = useRef(null);
    const api = useMemo(() => new LeaveApi({
        routes: payload.routes ?? {},
        csrfToken: payload.csrfToken,
    }), [payload.csrfToken, payload.routes]);
    const defaultLeaveFilters = payload.defaults?.leaveFilters ?? {};

    const {
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
        createLeave,
        updateLeave,
        deleteLeave,
        approveLeave,
        rejectLeave,
        bulkApproveLeaves,
    } = useLeave(api, payload);

    const [editingLeave, setEditingLeave] = useState(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formResetSeed, setFormResetSeed] = useState(0);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [rejectTarget, setRejectTarget] = useState(null);
    const [rejectNote, setRejectNote] = useState('');
    const [toasts, setToasts] = useState(() => {
        const flashStatus = payload.flash?.status;
        const flashError = payload.flash?.error;
        const initial = [];
        if (flashStatus) {
            initial.push({ id: Date.now(), tone: 'success', message: flashStatus });
        }
        if (flashError) {
            initial.push({ id: Date.now() + 1, tone: 'danger', message: flashError });
        }
        return initial;
    });
    const searchEmployees = useCallback((query) => api.searchEmployees(query), [api]);

    useEffect(() => {
        fetchLeaves({}, meta.currentPage || 1, true).catch(() => {});
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (!formOpen) {
            return;
        }

        const timer = window.setTimeout(() => {
            formRef.current?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }, 120);

        return () => window.clearTimeout(timer);
    }, [editingLeave?.id, formOpen]);

    const pushToast = (message, tone = 'success') => {
        const id = Date.now() + Math.floor(Math.random() * 1000);
        setToasts((prev) => [...prev, { id, tone, message }]);
        window.setTimeout(() => {
            setToasts((prev) => prev.filter((toast) => toast.id !== id));
        }, 3800);
    };

    const dismissToast = (id) => {
        setToasts((prev) => prev.filter((toast) => toast.id !== id));
    };

    const openCreateForm = () => {
        setEditingLeave(null);
        setFormResetSeed((prev) => prev + 1);
        setFormOpen(true);
    };

    const openEditForm = (leave) => {
        setEditingLeave(leave);
        setFormOpen(true);
    };

    const closeForm = () => {
        if (submitting) {
            return;
        }

        setFormOpen(false);
        setEditingLeave(null);
        setFormResetSeed((prev) => prev + 1);
    };

    const handleApplyFilters = (nextFilters) => {
        fetchLeaves(nextFilters, 1).catch(() => {});
    };

    const handleResetFilters = () => {
        fetchLeaves({
            q: '',
            status: 'all',
            date_from: defaultLeaveFilters.date_from ?? '',
            date_to: defaultLeaveFilters.date_to ?? '',
            employee_id: '',
            range_mode: 'absolute',
            range_preset: '',
        }, 1).catch(() => {});
    };

    const handleSubmitLeave = async (formData, _values, setFormErrors) => {
        try {
            if (editingLeave) {
                const result = await updateLeave(editingLeave.id, formData);
                pushToast(result?.message || 'Leave request updated successfully.');
            } else {
                const result = await createLeave(formData);
                pushToast(result?.message || 'Leave request submitted successfully.');
            }

            setFormOpen(false);
            setEditingLeave(null);
            setFormResetSeed((prev) => prev + 1);
        } catch (apiError) {
            if (apiError?.errors && typeof setFormErrors === 'function') {
                const mapped = Object.entries(apiError.errors).reduce((acc, [field, messages]) => ({
                    ...acc,
                    [field === 'user_id' ? 'employeeId' : field === 'leave_type' ? 'leaveType' : field === 'day_type'
                        ? 'dayType'
                        : field === 'half_day_session'
                            ? 'halfDaySession'
                            : field === 'start_date'
                                ? 'startDate'
                                : field === 'end_date'
                                    ? 'endDate'
                                    : field === 'assign_note'
                                        ? 'assignNote'
                                        : field]: Array.isArray(messages) ? messages[0] : messages,
                }), {});
                setFormErrors(mapped);
            }
            pushToast(apiError.message || 'Unable to process leave request.', 'danger');
        }
    };

    const handleDelete = async () => {
        if (!deleteTarget) {
            return;
        }

        try {
            const result = await deleteLeave(deleteTarget.id);
            pushToast(result?.message || 'Leave request cancelled successfully.');
            setDeleteTarget(null);
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to delete leave request.', 'danger');
        }
    };

    const handleApprove = async (leave) => {
        try {
            const result = await approveLeave(leave.id);
            pushToast(result?.message || 'Leave request approved successfully.');
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to approve leave request.', 'danger');
        }
    };

    const openRejectModal = (leave) => {
        setRejectTarget(leave);
        setRejectNote('');
    };

    const handleReject = async () => {
        if (!rejectTarget) {
            return;
        }

        if (rejectNote.trim() === '') {
            setError('Please add a rejection note before rejecting leave.');
            return;
        }

        try {
            const result = await rejectLeave(rejectTarget.id, rejectNote.trim());
            pushToast(result?.message || 'Leave request rejected successfully.');
            setRejectTarget(null);
            setRejectNote('');
            setError('');
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to reject leave request.', 'danger');
        }
    };

    const handleBulkApprove = async (leaveIds) => {
        if (!Array.isArray(leaveIds) || leaveIds.length === 0) {
            return;
        }

        try {
            await bulkApproveLeaves(leaveIds);
            pushToast(`${leaveIds.length} leave request(s) approved successfully.`);
        } catch (apiError) {
            pushToast(apiError.message || 'Bulk approval failed for one or more requests.', 'danger');
        }
    };

    const statsCards = payload.capabilities?.isEmployee
        ? [
            {
                label: 'Total Requests',
                value: stats.total ?? 0,
                note: 'All leave applications',
                icon: <ClipboardIcon />,
                color: 'neutral',
            },
            {
                label: 'Pending',
                value: stats.pending ?? 0,
                note: 'Awaiting approval',
                icon: <PendingIcon />,
                color: 'warning',
            },
            {
                label: 'Approved',
                value: stats.approved ?? 0,
                note: 'Accepted requests',
                icon: <ApprovedIcon />,
                color: 'success',
            },
            {
                label: 'Remaining Days',
                value: Number(stats.remainingDays ?? 0).toFixed(1),
                note: 'Current leave balance',
                icon: <BalanceIcon />,
                color: 'primary',
            },
        ]
        : [
            {
                label: 'Total Requests',
                value: stats.total ?? 0,
                note: 'All tracked requests',
                icon: <ClipboardIcon />,
                color: 'neutral',
            },
            {
                label: 'Pending',
                value: stats.pending ?? 0,
                note: 'Need review action',
                icon: <PendingIcon />,
                color: 'warning',
            },
            {
                label: 'Approved',
                value: stats.approved ?? 0,
                note: 'Approved entries',
                icon: <ApprovedIcon />,
                color: 'success',
            },
            {
                label: 'Rejected',
                value: stats.rejected ?? 0,
                note: 'Rejected entries',
                icon: <RejectedIcon />,
                color: 'error',
            },
        ];

    return (
        <div className="space-y-5">
            <ToastStack toasts={toasts} onDismiss={dismissToast} />

            <section className="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 className="text-xl font-extrabold">Leave Management</h2>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Modular leave workflows for requests, approvals, and operational tracking.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    {loading || submitting ? (
                        <span className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                            Syncing data...
                        </span>
                    ) : null}
                    {payload.capabilities?.canCreate ? (
                        <button
                            type="button"
                            className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                            style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                            onClick={openCreateForm}
                            disabled={submitting}
                        >
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M12 5v14" />
                                <path d="M5 12h14" />
                            </svg>
                            + Leave Request
                        </button>
                    ) : null}
                </div>
            </section>

            <QuickInfoGrid>
                {statsCards.map((statCard) => (
                    <QuickInfoCard
                        key={statCard.label}
                        title={statCard.label}
                        value={statCard.value}
                        secondaryInfo={statCard.note}
                        icon={statCard.icon}
                        color={statCard.color}
                    />
                ))}
            </QuickInfoGrid>

            {error ? (
                <section className="hrm-modern-surface rounded-2xl p-4">
                    <p className="text-sm font-semibold text-red-600">{error}</p>
                </section>
            ) : null}

            {initialLoading ? (
                <section className="hrm-modern-surface rounded-2xl p-8 text-center">
                    <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Loading leave workspace...</p>
                </section>
            ) : (
                <>
                    {payload.capabilities?.canCreate ? (
                        <section
                            ref={formRef}
                            className="overflow-hidden"
                            style={{
                                maxHeight: formOpen ? '2400px' : '0px',
                                opacity: formOpen ? 1 : 0,
                                transform: formOpen ? 'translateY(0)' : 'translateY(-8px)',
                                transition: 'max-height 360ms ease, opacity 260ms ease, transform 260ms ease',
                                pointerEvents: formOpen ? 'auto' : 'none',
                            }}
                        >
                            <LeaveForm
                                key={editingLeave ? `edit-${editingLeave.id}` : `create-${formResetSeed}`}
                                capabilities={payload.capabilities}
                                options={payload.options}
                                currentUser={payload.currentUser}
                                remainingDays={stats.remainingDays ?? 0}
                                submitting={submitting}
                                editingLeave={editingLeave}
                                onCancelEdit={closeForm}
                                onClose={closeForm}
                                onSearchEmployees={searchEmployees}
                                onSubmit={handleSubmitLeave}
                            />
                        </section>
                    ) : (
                        <section className="hrm-modern-surface rounded-2xl p-4">
                            <p className="text-sm font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                                You have read-only access to leave records.
                            </p>
                        </section>
                    )}

                    <LeaveFilters
                        filters={filters}
                        statusOptions={payload.options?.statuses ?? []}
                        canFilterByEmployee={Boolean(payload.capabilities?.canFilterByEmployee)}
                        onSearchEmployees={searchEmployees}
                        loading={loading}
                        onApply={handleApplyFilters}
                        onReset={handleResetFilters}
                    />

                    <LeaveList
                        leaves={leaves}
                        meta={meta}
                        loading={loading}
                        submitting={submitting}
                        canReview={Boolean(payload.capabilities?.canReview)}
                        onApprove={handleApprove}
                        onRejectRequest={openRejectModal}
                        onDeleteRequest={setDeleteTarget}
                        onEditRequest={openEditForm}
                        onPageChange={(page) => fetchLeaves({}, page).catch(() => {})}
                        onBulkApprove={handleBulkApprove}
                    />
                </>
            )}

            <LeaveModal
                open={Boolean(deleteTarget)}
                title="Confirm Delete"
                confirmLabel="Delete Request"
                confirmTone="danger"
                busy={submitting}
                onConfirm={handleDelete}
                onCancel={() => setDeleteTarget(null)}
            >
                {deleteTarget
                    ? `Delete leave request for ${deleteTarget.employee?.name}? This action will mark it as cancelled.`
                    : 'Confirm delete action.'}
            </LeaveModal>

            <LeaveModal
                open={Boolean(rejectTarget)}
                title="Reject Leave Request"
                confirmLabel="Reject Request"
                confirmTone="warning"
                busy={submitting}
                onConfirm={handleReject}
                onCancel={() => {
                    setRejectTarget(null);
                    setRejectNote('');
                    setError('');
                }}
            >
                <div className="space-y-3">
                    <p>
                        {rejectTarget
                            ? `Provide a rejection note for ${rejectTarget.employee?.name}.`
                            : 'Provide rejection note.'}
                    </p>
                    <textarea
                        rows={4}
                        className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent resize-y"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={rejectNote}
                        onChange={(event) => setRejectNote(event.target.value)}
                        placeholder="Reason for rejection..."
                        disabled={submitting}
                    />
                </div>
            </LeaveModal>
        </div>
    );
}

export function mountLeaveManagementPage() {
    const rootElement = document.getElementById('leave-management-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        return;
    }

    createRoot(rootElement).render(<LeaveManagementPage payload={payload} />);
}
