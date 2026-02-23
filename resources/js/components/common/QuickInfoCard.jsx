import React, { useMemo } from 'react';

const COLOR_THEMES = {
    primary: {
        borderColor: 'rgb(14 165 233 / 0.3)',
        background: 'var(--hr-surface)',
        iconBackground: 'rgb(14 165 233 / 0.15)',
        iconColor: '#0369a1',
        chartColor: '#0284c7',
    },
    success: {
        borderColor: 'rgb(16 185 129 / 0.3)',
        background: 'var(--hr-surface)',
        iconBackground: 'rgb(16 185 129 / 0.16)',
        iconColor: '#047857',
        chartColor: '#059669',
    },
    warning: {
        borderColor: 'rgb(245 158 11 / 0.34)',
        background: 'var(--hr-surface)',
        iconBackground: 'rgb(245 158 11 / 0.18)',
        iconColor: '#b45309',
        chartColor: '#d97706',
    },
    error: {
        borderColor: 'rgb(239 68 68 / 0.32)',
        background: 'var(--hr-surface)',
        iconBackground: 'rgb(239 68 68 / 0.16)',
        iconColor: '#b91c1c',
        chartColor: '#dc2626',
    },
    neutral: {
        borderColor: 'var(--hr-line)',
        background: 'var(--hr-surface)',
        iconBackground: 'rgb(100 116 139 / 0.16)',
        iconColor: '#475569',
        chartColor: '#64748b',
    },
};

const COLOR_ALIASES = {
    slate: 'neutral',
    gray: 'neutral',
    blue: 'primary',
    emerald: 'success',
    amber: 'warning',
    red: 'error',
    rose: 'error',
    danger: 'error',
    info: 'primary',
    default: 'neutral',
    violet: 'primary',
    sky: 'primary',
};

const normalizeColor = (color) => {
    const key = String(color || 'neutral').toLowerCase();

    if (COLOR_THEMES[key]) {
        return key;
    }

    return COLOR_ALIASES[key] ?? 'neutral';
};

const normalizeTrendType = (comparisonType) => {
    const key = String(comparisonType || '').toLowerCase();

    if (key === 'decrease' || key === 'down') {
        return 'decrease';
    }

    if (key === 'increase' || key === 'up') {
        return 'increase';
    }

    return null;
};

const trendStyles = {
    increase: {
        color: '#15803d',
        background: 'rgb(134 239 172 / 0.34)',
        symbol: '↑',
    },
    decrease: {
        color: '#b91c1c',
        background: 'rgb(252 165 165 / 0.34)',
        symbol: '↓',
    },
};

const formatComparisonValue = (comparisonValue) => {
    if (typeof comparisonValue === 'number' && Number.isFinite(comparisonValue)) {
        return `${Math.abs(comparisonValue).toFixed(1)}%`;
    }

    const raw = String(comparisonValue ?? '').trim();
    return raw;
};

const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

const buildSparkline = (data, width = 128, height = 28, padding = 2) => {
    const clean = data
        .map((entry) => Number(entry))
        .filter((value) => Number.isFinite(value));

    if (clean.length < 2) {
        return '';
    }

    const min = Math.min(...clean);
    const max = Math.max(...clean);
    const span = max - min || 1;
    const step = (width - (padding * 2)) / (clean.length - 1);

    return clean
        .map((value, index) => {
            const x = padding + (step * index);
            const normalized = (value - min) / span;
            const y = height - padding - ((height - (padding * 2)) * clamp(normalized, 0, 1));
            return `${x.toFixed(2)},${y.toFixed(2)}`;
        })
        .join(' ');
};

export function QuickInfoCard({
    title,
    value,
    icon = null,
    color = 'neutral',
    comparisonValue,
    comparisonType,
    showChart = false,
    chartData = [],
    secondaryInfo = '',
    className = '',
}) {
    const resolvedColor = normalizeColor(color);
    const styles = COLOR_THEMES[resolvedColor];
    const trendType = normalizeTrendType(comparisonType);
    const comparisonLabel = formatComparisonValue(comparisonValue);
    const hasComparison = trendType !== null && comparisonLabel !== '';

    const chartPoints = useMemo(
        () => (showChart ? buildSparkline(Array.isArray(chartData) ? chartData : []) : ''),
        [chartData, showChart],
    );

    return (
        <article
            className={`quick-info-card rounded-2xl border p-4 shadow-sm h-full ${className}`.trim()}
            style={{
                borderColor: styles.borderColor,
                background: styles.background,
            }}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="quick-info-title text-[11px] font-bold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                        {title}
                    </p>
                    <div className="mt-2 flex flex-wrap items-center gap-2">
                        <p className="quick-info-value text-2xl font-extrabold leading-none" style={{ color: 'var(--hr-text-main)' }}>
                            {value}
                        </p>
                        {hasComparison ? (
                            <span
                                className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                style={{
                                    color: trendStyles[trendType].color,
                                    background: trendStyles[trendType].background,
                                }}
                            >
                                <span aria-hidden="true">{trendStyles[trendType].symbol}</span>
                                {comparisonLabel}
                            </span>
                        ) : null}
                    </div>
                </div>

                {icon ? (
                    <span
                        className="quick-info-icon inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        style={{
                            background: styles.iconBackground,
                            color: styles.iconColor,
                        }}
                    >
                        {icon}
                    </span>
                ) : null}
            </div>

            {secondaryInfo ? (
                <p className="mt-2 text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                    {secondaryInfo}
                </p>
            ) : null}

            {showChart && chartPoints !== '' ? (
                <div className="mt-2 h-7">
                    <svg viewBox="0 0 128 28" className="h-full w-full" preserveAspectRatio="none" aria-hidden="true">
                        <polyline
                            fill="none"
                            stroke={styles.chartColor}
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            points={chartPoints}
                        />
                    </svg>
                </div>
            ) : null}
        </article>
    );
}
