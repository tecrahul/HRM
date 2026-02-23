import React from 'react';
import { QuickInfoCard } from '../common/QuickInfoCard';

const COLOR_MAP = {
    slate: 'neutral',
    emerald: 'success',
    red: 'error',
    amber: 'warning',
};

export function InfoCard({ icon, label, value, tone = 'slate' }) {
    return (
        <QuickInfoCard
            icon={icon}
            title={label}
            value={value}
            color={COLOR_MAP[tone] ?? 'neutral'}
        />
    );
}
