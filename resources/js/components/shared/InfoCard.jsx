import React from 'react';
import { QuickInfoCard } from '../common/QuickInfoCard';

const COLOR_MAP = {
    slate: 'neutral',
    gray: 'neutral',
    blue: 'primary',
    emerald: 'success',
    amber: 'warning',
    red: 'error',
    rose: 'error',
};

export function InfoCard({ icon, label, value, tone = 'slate', note = '' }) {
    return (
        <QuickInfoCard
            icon={icon}
            title={label}
            value={value}
            color={COLOR_MAP[tone] ?? 'neutral'}
            secondaryInfo={note}
        />
    );
}
