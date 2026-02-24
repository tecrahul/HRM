import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

export function usePunchAttendance({ api, punch, onSuccess, onClose }) {
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [successMessage, setSuccessMessage] = useState('');
    const closeTimer = useRef(null);

    const action = useMemo(() => {
        if (!punch?.canPunchSelf) return null;
        if (punch?.nextAction === 'check_in') return 'in';
        if (punch?.nextAction === 'check_out') return 'out';
        return null;
    }, [punch]);

    const submit = useCallback(async (forcedAction, textReason) => {
        const type = forcedAction || action;
        if (!type) return null;

        setSubmitting(true);
        setError('');
        setSuccessMessage('');

        try {
            // Prefer unified punch endpoint if available; fallback to dedicated routes
            let response;
            if (api?.routes?.punch) {
                response = await api.punch(type, textReason || reason);
            } else if (type === 'in') {
                response = await api.checkIn(textReason || reason);
            } else {
                response = await api.checkOut(textReason || reason);
            }

            const msg = response?.message || (type === 'in' ? 'Punch In recorded successfully' : 'Punch Out recorded successfully');
            setSuccessMessage(msg);
            if (typeof onSuccess === 'function') {
                await onSuccess(msg);
            }

            // Auto close after 3 seconds
            if (closeTimer.current) window.clearTimeout(closeTimer.current);
            closeTimer.current = window.setTimeout(() => {
                if (typeof onClose === 'function') onClose();
                setReason('');
                setSuccessMessage('');
            }, 3000);

            return response;
        } catch (err) {
            setError(err?.message || 'Unable to mark attendance.');
            throw err;
        } finally {
            setSubmitting(false);
        }
    }, [action, api, onClose, onSuccess, reason]);

    useEffect(() => () => {
        if (closeTimer.current) window.clearTimeout(closeTimer.current);
    }, []);

    return {
        action,
        reason,
        setReason,
        submitting,
        successMessage,
        error,
        submit,
    };
}

