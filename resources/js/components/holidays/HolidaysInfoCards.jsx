import React from 'react';
import { QuickInfoCard } from '../common/QuickInfoCard';
import { QuickInfoGrid } from '../common/QuickInfoGrid';

function CalendarIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M8 2v4" />
            <path d="M16 2v4" />
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M3 10h18" />
        </svg>
    );
}

function ArrowUpIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="m12 19 7-7-7-7" />
            <path d="M5 12h14" />
        </svg>
    );
}

function ArrowDownIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="m12 5-7 7 7 7" />
            <path d="M5 12h14" />
        </svg>
    );
}

export function HolidaysInfoCards({ stats }) {
    return (
        <QuickInfoGrid>
            <QuickInfoCard
                icon={<CalendarIcon />}
                title="Total Holidays"
                value={stats?.total ?? 0}
                color="neutral"
            />
            <QuickInfoCard
                icon={<ArrowUpIcon />}
                title="Upcoming"
                value={stats?.upcoming ?? 0}
                color="primary"
            />
            <QuickInfoCard
                icon={<ArrowDownIcon />}
                title="Past"
                value={stats?.past ?? 0}
                color="warning"
            />
        </QuickInfoGrid>
    );
}
