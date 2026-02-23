import React from 'react';

const BADGE_STYLES = {
    active: {
        light: { color: '#166534', background: 'rgb(134 239 172 / 0.34)' },
        dark: { color: '#ffffff', background: 'rgb(16 185 129 / 0.42)' },
    },
    inactive: {
        light: { color: '#374151', background: 'rgb(209 213 219 / 0.56)' },
        dark: { color: '#ffffff', background: 'rgb(107 114 128 / 0.56)' },
    },
    upcoming: {
        light: { color: '#1d4ed8', background: 'rgb(147 197 253 / 0.34)' },
        dark: { color: '#ffffff', background: 'rgb(59 130 246 / 0.45)' },
    },
    past: {
        light: { color: '#4b5563', background: 'rgb(229 231 235 / 0.7)' },
        dark: { color: '#ffffff', background: 'rgb(75 85 99 / 0.62)' },
    },
    public: {
        light: { color: '#1d4ed8', background: 'rgb(191 219 254 / 0.52)' },
        dark: { color: '#ffffff', background: 'rgb(37 99 235 / 0.5)' },
    },
    company: {
        light: { color: '#92400e', background: 'rgb(253 230 138 / 0.5)' },
        dark: { color: '#ffffff', background: 'rgb(217 119 6 / 0.5)' },
    },
    optional: {
        light: { color: '#7c2d12', background: 'rgb(254 215 170 / 0.55)' },
        dark: { color: '#ffffff', background: 'rgb(234 88 12 / 0.48)' },
    },
};

export function StatusBadge({ value, label, isDark = false }) {
    const key = String(value || '').toLowerCase();
    const styles = BADGE_STYLES[key] ?? BADGE_STYLES.inactive;

    return (
        <span
            className="rounded-full px-2 py-1 text-[11px] font-bold uppercase tracking-[0.08em]"
            style={isDark ? styles.dark : styles.light}
        >
            {label || key}
        </span>
    );
}
