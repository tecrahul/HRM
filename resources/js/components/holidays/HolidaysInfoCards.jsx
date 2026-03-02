import React, { useMemo } from 'react';

function CalendarIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M8 2v4" />
            <path d="M16 2v4" />
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M3 10h18" />
        </svg>
    );
}

function UpcomingIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M12 8v4l3 3" />
            <circle cx="12" cy="12" r="10" />
        </svg>
    );
}

function PastIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
            <path d="M3 3v5h5" />
            <path d="M12 7v5l4 2" />
        </svg>
    );
}

function NextHolidayIcon() {
    return (
        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
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

function formatNextHolidayDate(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        const options = { month: 'short', day: 'numeric' };
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const holidayDate = new Date(date);
        holidayDate.setHours(0, 0, 0, 0);
        const diffDays = Math.ceil((holidayDate - today) / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return 'Today';
        if (diffDays === 1) return 'Tomorrow';
        if (diffDays <= 7) return `In ${diffDays} days`;
        return date.toLocaleDateString('en-US', options);
    } catch {
        return '';
    }
}

export function HolidaysInfoCards({ stats, holidays = [], isDarkMode = false }) {
    const nextHoliday = useMemo(() => {
        if (!Array.isArray(holidays) || holidays.length === 0) return null;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const upcoming = holidays
            .filter(h => {
                if (!h.holiday_date) return false;
                const holidayDate = new Date(h.holiday_date);
                holidayDate.setHours(0, 0, 0, 0);
                return holidayDate >= today && h.is_active !== false;
            })
            .sort((a, b) => new Date(a.holiday_date) - new Date(b.holiday_date));

        return upcoming[0] || null;
    }, [holidays]);

    const colors = CARD_THEMES.purple[isDarkMode ? 'dark' : 'light'];

    return (
        <section className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <InfoCard
                title="Total Holidays"
                value={stats?.total ?? 0}
                subtitle="This year"
                icon={<CalendarIcon />}
                theme="blue"
                isDarkMode={isDarkMode}
            />
            <InfoCard
                title="Upcoming"
                value={stats?.upcoming ?? 0}
                subtitle="Days off ahead"
                icon={<UpcomingIcon />}
                theme="emerald"
                isDarkMode={isDarkMode}
            />
            <InfoCard
                title="Past"
                value={stats?.past ?? 0}
                subtitle="Holidays enjoyed"
                icon={<PastIcon />}
                theme="amber"
                isDarkMode={isDarkMode}
            />

            {/* Next Holiday Card - Custom layout for holiday name */}
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
                            Next Holiday
                        </p>
                        {nextHoliday ? (
                            <>
                                <p
                                    className="mt-3 text-lg font-black truncate"
                                    style={{ color: colors.valueColor }}
                                    title={nextHoliday.name}
                                >
                                    {nextHoliday.name}
                                </p>
                                <p
                                    className="mt-1 text-xs font-medium"
                                    style={{ color: colors.subtitleColor }}
                                >
                                    {formatNextHolidayDate(nextHoliday.holiday_date)}
                                </p>
                            </>
                        ) : (
                            <>
                                <p
                                    className="mt-3 text-lg font-black"
                                    style={{ color: colors.valueColor }}
                                >
                                    —
                                </p>
                                <p
                                    className="mt-1 text-xs font-medium"
                                    style={{ color: colors.subtitleColor }}
                                >
                                    No upcoming holidays
                                </p>
                            </>
                        )}
                    </div>
                    <span
                        className="h-12 w-12 rounded-xl flex items-center justify-center shadow-sm shrink-0"
                        style={{
                            background: colors.iconBg,
                            color: 'white',
                        }}
                    >
                        <NextHolidayIcon />
                    </span>
                </div>
            </article>
        </section>
    );
}
