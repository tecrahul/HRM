import React from 'react';
import { QuickInfoCard } from '../common/QuickInfoCard';
import { QuickInfoGrid } from '../common/QuickInfoGrid';

function UsersIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="8.5" cy="7" r="4" />
            <path d="M20 8v6" />
            <path d="M23 11h-6" />
        </svg>
    );
}

function PresentIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="m8.5 12.5 2.2 2.2 4.8-5.2" />
        </svg>
    );
}

function AbsentIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="m9 9 6 6" />
            <path d="m15 9-6 6" />
        </svg>
    );
}

function PendingIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v6l4 2" />
        </svg>
    );
}

function DepartmentIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M3 21h18" />
            <path d="M6 21V7l6-4 6 4v14" />
            <path d="M9 10h.01" />
            <path d="M15 10h.01" />
            <path d="M9 14h.01" />
            <path d="M15 14h.01" />
        </svg>
    );
}

function BranchIcon() {
    return (
        <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M3 21h18" />
            <path d="M5 21V8l7-5 7 5v13" />
            <path d="M9 12h6" />
        </svg>
    );
}

const formatEmployeeCode = (userId) => {
    const numericId = Number(userId);

    if (!Number.isFinite(numericId) || numericId <= 0) {
        return 'N/A';
    }

    return `EMP-${String(Math.floor(numericId)).padStart(6, '0')}`;
};

export function AttendanceInfoCards({ stats, canApprove, currentUser = {}, isEmployeeOnly = false }) {
    const cards = isEmployeeOnly
        ? [
            {
                label: 'Employee ID',
                value: formatEmployeeCode(currentUser.id),
                color: 'neutral',
                icon: <UsersIcon />,
            },
            {
                label: 'Department',
                value: currentUser.department || 'N/A',
                color: 'success',
                icon: <DepartmentIcon />,
            },
            {
                label: 'Branch',
                value: currentUser.branch || 'N/A',
                color: 'primary',
                icon: <BranchIcon />,
            },
            {
                label: 'Pending Requests',
                value: stats.pendingApprovals ?? 0,
                color: 'warning',
                icon: <PendingIcon />,
            },
        ]
        : [
            {
                label: 'Total Employees',
                value: stats.totalEmployees ?? 0,
                color: 'neutral',
                icon: <UsersIcon />,
            },
            {
                label: 'Present Today',
                value: stats.presentToday ?? 0,
                color: 'success',
                icon: <PresentIcon />,
                comparisonValue: stats.presentTodayChange,
                comparisonType: (Number(stats.presentTodayChange ?? 0) >= 0) ? 'increase' : 'decrease',
                showChart: Array.isArray(stats.presentTrend),
                chartData: stats.presentTrend ?? [],
            },
            {
                label: 'Absent Today',
                value: stats.absentToday ?? 0,
                color: 'error',
                icon: <AbsentIcon />,
                comparisonValue: stats.absentTodayChange,
                comparisonType: (Number(stats.absentTodayChange ?? 0) >= 0) ? 'increase' : 'decrease',
                showChart: Array.isArray(stats.absentTrend),
                chartData: stats.absentTrend ?? [],
            },
            {
                label: canApprove ? 'Pending Approvals' : 'Pending Requests',
                value: stats.pendingApprovals ?? 0,
                color: 'warning',
                icon: <PendingIcon />,
            },
        ];

    return (
        <QuickInfoGrid>
            {cards.map((card) => (
                <QuickInfoCard
                    key={card.label}
                    title={card.label}
                    value={card.value}
                    icon={card.icon}
                    color={card.color}
                    comparisonValue={card.comparisonValue}
                    comparisonType={card.comparisonType}
                    showChart={Boolean(card.showChart)}
                    chartData={card.chartData}
                />
            ))}
        </QuickInfoGrid>
    );
}
