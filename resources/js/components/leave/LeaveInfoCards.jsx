import React from 'react';

function ClipboardIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <rect x="8" y="3" width="8" height="4" rx="1.2" />
            <path d="M9 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3" />
        </svg>
    );
}

function PendingIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v6l4 2" />
        </svg>
    );
}

function ApprovedIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="m8.5 12.5 2.2 2.2 4.8-5.2" />
        </svg>
    );
}

function RejectedIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="12" cy="12" r="9" />
            <path d="m9 9 6 6" />
            <path d="m15 9-6 6" />
        </svg>
    );
}

function BalanceIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M4 7h16" />
            <path d="M6 4h12a2 2 0 0 1 2 2v10a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V6a2 2 0 0 1 2-2Z" />
            <path d="M8 12h8" />
        </svg>
    );
}

// Theme configurations for light and dark modes
const CARD_THEMES = {
    blue: {
        light: {
            background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
            borderColor: 'rgba(59, 130, 246, 0.2)',
            accentBg: 'radial-gradient(circle, #3b82f6 0%, transparent 70%)',
            titleColor: '#1e40af',
            valueColor: '#1e3a8a',
            subtitleColor: '#3b82f6',
            iconBg: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
        },
        dark: {
            background: 'linear-gradient(135deg, rgba(30, 58, 138, 0.3) 0%, rgba(30, 64, 175, 0.2) 100%)',
            borderColor: 'rgba(59, 130, 246, 0.3)',
            accentBg: 'radial-gradient(circle, rgba(59, 130, 246, 0.4) 0%, transparent 70%)',
            titleColor: '#93c5fd',
            valueColor: '#bfdbfe',
            subtitleColor: '#60a5fa',
            iconBg: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
        },
    },
    amber: {
        light: {
            background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
            borderColor: 'rgba(245, 158, 11, 0.2)',
            accentBg: 'radial-gradient(circle, #f59e0b 0%, transparent 70%)',
            titleColor: '#b45309',
            valueColor: '#78350f',
            subtitleColor: '#d97706',
            iconBg: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
        },
        dark: {
            background: 'linear-gradient(135deg, rgba(120, 53, 15, 0.3) 0%, rgba(146, 64, 14, 0.2) 100%)',
            borderColor: 'rgba(245, 158, 11, 0.3)',
            accentBg: 'radial-gradient(circle, rgba(245, 158, 11, 0.4) 0%, transparent 70%)',
            titleColor: '#fcd34d',
            valueColor: '#fde68a',
            subtitleColor: '#fbbf24',
            iconBg: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
        },
    },
    emerald: {
        light: {
            background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
            borderColor: 'rgba(16, 185, 129, 0.2)',
            accentBg: 'radial-gradient(circle, #10b981 0%, transparent 70%)',
            titleColor: '#047857',
            valueColor: '#064e3b',
            subtitleColor: '#059669',
            iconBg: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        },
        dark: {
            background: 'linear-gradient(135deg, rgba(6, 78, 59, 0.3) 0%, rgba(4, 120, 87, 0.2) 100%)',
            borderColor: 'rgba(16, 185, 129, 0.3)',
            accentBg: 'radial-gradient(circle, rgba(16, 185, 129, 0.4) 0%, transparent 70%)',
            titleColor: '#6ee7b7',
            valueColor: '#a7f3d0',
            subtitleColor: '#34d399',
            iconBg: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        },
    },
    rose: {
        light: {
            background: 'linear-gradient(135deg, #fff1f2 0%, #fecdd3 100%)',
            borderColor: 'rgba(244, 63, 94, 0.2)',
            accentBg: 'radial-gradient(circle, #f43f5e 0%, transparent 70%)',
            titleColor: '#be123c',
            valueColor: '#881337',
            subtitleColor: '#e11d48',
            iconBg: 'linear-gradient(135deg, #f43f5e 0%, #e11d48 100%)',
        },
        dark: {
            background: 'linear-gradient(135deg, rgba(136, 19, 55, 0.3) 0%, rgba(159, 18, 57, 0.2) 100%)',
            borderColor: 'rgba(244, 63, 94, 0.3)',
            accentBg: 'radial-gradient(circle, rgba(244, 63, 94, 0.4) 0%, transparent 70%)',
            titleColor: '#fda4af',
            valueColor: '#fecdd3',
            subtitleColor: '#fb7185',
            iconBg: 'linear-gradient(135deg, #f43f5e 0%, #e11d48 100%)',
        },
    },
    purple: {
        light: {
            background: 'linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%)',
            borderColor: 'rgba(139, 92, 246, 0.2)',
            accentBg: 'radial-gradient(circle, #8b5cf6 0%, transparent 70%)',
            titleColor: '#5b21b6',
            valueColor: '#4c1d95',
            subtitleColor: '#7c3aed',
            iconBg: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
        },
        dark: {
            background: 'linear-gradient(135deg, rgba(76, 29, 149, 0.3) 0%, rgba(91, 33, 182, 0.2) 100%)',
            borderColor: 'rgba(139, 92, 246, 0.3)',
            accentBg: 'radial-gradient(circle, rgba(139, 92, 246, 0.4) 0%, transparent 70%)',
            titleColor: '#c4b5fd',
            valueColor: '#ddd6fe',
            subtitleColor: '#a78bfa',
            iconBg: 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
        },
    },
};

function InfoCard({ title, value, subtitle, icon, theme = 'blue', isDarkMode = false }) {
    const colors = CARD_THEMES[theme]?.[isDarkMode ? 'dark' : 'light'] || CARD_THEMES.blue.light;

    return (
        <article
            className="relative overflow-hidden rounded-2xl p-5 border transition-all duration-200 hover:shadow-lg"
            style={{
                background: colors.background,
                borderColor: colors.borderColor,
            }}
        >
            <div
                className="absolute top-0 right-0 w-24 h-24 opacity-10"
                style={{
                    background: colors.accentBg,
                    transform: 'translate(30%, -30%)',
                }}
            />
            <div className="flex items-start justify-between gap-3 relative z-10">
                <div className="min-w-0 flex-1">
                    <p
                        className="text-xs uppercase tracking-[0.12em] font-bold"
                        style={{ color: colors.titleColor }}
                    >
                        {title}
                    </p>
                    <p
                        className="mt-3 text-4xl font-black truncate"
                        style={{ color: colors.valueColor }}
                        title={String(value)}
                    >
                        {value}
                    </p>
                    <p
                        className="mt-1 text-xs font-medium"
                        style={{ color: colors.subtitleColor }}
                    >
                        {subtitle}
                    </p>
                </div>
                <span
                    className="h-12 w-12 rounded-xl flex items-center justify-center shadow-sm shrink-0"
                    style={{
                        background: colors.iconBg,
                        color: 'white',
                    }}
                >
                    {icon}
                </span>
            </div>
        </article>
    );
}

export function LeaveInfoCards({ stats, isEmployee = false, isDarkMode = false }) {
    if (isEmployee) {
        // Employee view: Total, Pending, Approved, Remaining Days
        return (
            <section className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <InfoCard
                    title="Total Requests"
                    value={stats?.total ?? 0}
                    subtitle="All leave applications"
                    icon={<ClipboardIcon />}
                    theme="blue"
                    isDarkMode={isDarkMode}
                />
                <InfoCard
                    title="Pending"
                    value={stats?.pending ?? 0}
                    subtitle="Awaiting approval"
                    icon={<PendingIcon />}
                    theme="amber"
                    isDarkMode={isDarkMode}
                />
                <InfoCard
                    title="Approved"
                    value={stats?.approved ?? 0}
                    subtitle="Accepted requests"
                    icon={<ApprovedIcon />}
                    theme="emerald"
                    isDarkMode={isDarkMode}
                />
                <InfoCard
                    title="Leave Balance"
                    value={Number(stats?.remainingDays ?? 0).toFixed(1)}
                    subtitle="Remaining days"
                    icon={<BalanceIcon />}
                    theme="purple"
                    isDarkMode={isDarkMode}
                />
            </section>
        );
    }

    // Admin/HR view: Total, Pending, Approved, Rejected
    return (
        <section className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <InfoCard
                title="Total Requests"
                value={stats?.total ?? 0}
                subtitle="All tracked requests"
                icon={<ClipboardIcon />}
                theme="blue"
                isDarkMode={isDarkMode}
            />
            <InfoCard
                title="Pending"
                value={stats?.pending ?? 0}
                subtitle="Need review action"
                icon={<PendingIcon />}
                theme="amber"
                isDarkMode={isDarkMode}
            />
            <InfoCard
                title="Approved"
                value={stats?.approved ?? 0}
                subtitle="Approved entries"
                icon={<ApprovedIcon />}
                theme="emerald"
                isDarkMode={isDarkMode}
            />
            <InfoCard
                title="Rejected"
                value={stats?.rejected ?? 0}
                subtitle="Rejected entries"
                icon={<RejectedIcon />}
                theme="rose"
                isDarkMode={isDarkMode}
            />
        </section>
    );
}
