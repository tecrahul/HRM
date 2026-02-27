import React from 'react';

export function ViewToggle({ value = 'list', onChange, disabled = false }) {
    const set = (next) => {
        if (disabled || next === value) return;
        if (typeof onChange === 'function') onChange(next);
    };

    const base = 'inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border';

    return (
        <div className="inline-flex items-center gap-1 rounded-xl border p-1" style={{ borderColor: 'var(--hr-line)' }}>
            <button
                type="button"
                className={`${base}`}
                style={{
                    borderColor: value === 'list' ? '#0ea5a4' : 'transparent',
                    background: value === 'list' ? 'rgb(15 118 110 / 0.14)' : 'transparent',
                }}
                onClick={() => set('list')}
                disabled={disabled}
            >
                List View
            </button>
            <button
                type="button"
                className={`${base}`}
                style={{
                    borderColor: value === 'calendar' ? '#0ea5a4' : 'transparent',
                    background: value === 'calendar' ? 'rgb(15 118 110 / 0.14)' : 'transparent',
                }}
                onClick={() => set('calendar')}
                disabled={disabled}
            >
                Calendar View
            </button>
        </div>
    );
}

