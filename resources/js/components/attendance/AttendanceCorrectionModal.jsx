import React, { useEffect, useState } from 'react';
import { AttendanceApprovalModal } from './AttendanceApprovalModal';

export function AttendanceCorrectionModal({ open, record, busy, onSubmit, onCancel }) {
    const [checkInTime, setCheckInTime] = useState('');
    const [checkOutTime, setCheckOutTime] = useState('');
    const [reason, setReason] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        if (!open || !record) {
            setCheckInTime('');
            setCheckOutTime('');
            setReason('');
            setError('');
            return;
        }

        setCheckInTime(record.requestedCheckInIso || '');
        setCheckOutTime(record.requestedCheckOutIso || '');
        setReason(record.correctionReason || '');
        setError('');
    }, [open, record]);

    const submit = () => {
        if (reason.trim() === '') {
            setError('Correction reason is required.');
            return;
        }

        onSubmit({
            check_in_time: checkInTime || '',
            check_out_time: checkOutTime || '',
            reason: reason.trim(),
        });
    };

    return (
        <AttendanceApprovalModal
            open={open}
            title="Request Correction"
            description={record ? `Submit correction request for ${record.attendanceDateLabel}.` : ''}
            busy={busy}
            confirmLabel="Submit Request"
            confirmTone="warning"
            onConfirm={submit}
            onCancel={onCancel}
        >
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="flex flex-col gap-1.5">
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Check In</label>
                    <input
                        type="time"
                        className="rounded-xl border px-3 py-2 text-sm bg-transparent"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={checkInTime}
                        onChange={(event) => setCheckInTime(event.target.value)}
                        disabled={busy}
                    />
                </div>
                <div className="flex flex-col gap-1.5">
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Check Out</label>
                    <input
                        type="time"
                        className="rounded-xl border px-3 py-2 text-sm bg-transparent"
                        style={{ borderColor: 'var(--hr-line)' }}
                        value={checkOutTime}
                        onChange={(event) => setCheckOutTime(event.target.value)}
                        disabled={busy}
                    />
                </div>
                <div className="sm:col-span-2 flex flex-col gap-1.5">
                    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Reason *</label>
                    <textarea
                        rows={3}
                        className="rounded-xl border px-3 py-2 text-sm bg-transparent resize-y"
                        style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
                        value={reason}
                        onChange={(event) => {
                            setReason(event.target.value);
                            if (error) {
                                setError('');
                            }
                        }}
                        placeholder="Why should this attendance be corrected?"
                        disabled={busy}
                    />
                    {error ? <p className="text-xs text-red-500">{error}</p> : null}
                </div>
            </div>
        </AttendanceApprovalModal>
    );
}
