import React from 'react';

const STATUS_STYLES = {
    pending: {
        color: '#92400e',
        background: 'rgb(253 230 138 / 0.42)',
    },
    approved: {
        color: '#166534',
        background: 'rgb(134 239 172 / 0.32)',
    },
    rejected: {
        color: '#991b1b',
        background: 'rgb(254 202 202 / 0.38)',
    },
    cancelled: {
        color: '#334155',
        background: 'rgb(203 213 225 / 0.45)',
    },
};

export function LeaveStatusBadge({ status, label }) {
    const normalized = String(status || '').toLowerCase();
    const styles = STATUS_STYLES[normalized] ?? STATUS_STYLES.pending;

    return (
        <span
            className="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1"
            style={styles}
        >
            {label || normalized}
        </span>
    );
}
