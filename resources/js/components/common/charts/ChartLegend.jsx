import React, { useMemo } from 'react';

// items: [{ key, label, color, visible }]
export const ChartLegend = React.memo(function ChartLegend({
  items = [],
  onToggle,
  align = 'right',
}) {
  const sorted = useMemo(() => items, [items]);

  return (
    <div className="space-y-2 text-sm">
      <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Legend</p>
      <ul className="space-y-1.5">
        {sorted.map((it) => (
          <li key={it.key} className="flex items-center justify-between gap-3 py-1">
            <button
              type="button"
              className="flex items-center gap-2 font-semibold"
              onClick={() => onToggle && onToggle(it.key)}
              style={{
                opacity: it.visible === false ? 0.45 : 1,
                color: 'var(--hr-text-main)',
                cursor: onToggle ? 'pointer' : 'default',
              }}
            >
              <span className="h-2.5 w-2.5 rounded-full" style={{ background: it.color }}></span>
              {it.label}
            </button>
            {typeof it.value !== 'undefined' || typeof it.pct !== 'undefined' ? (
              <div className="flex items-baseline gap-2">
                {typeof it.value !== 'undefined' ? <span>{it.value}</span> : null}
                {typeof it.pct !== 'undefined' ? (
                  <span className="text-[0.65rem]" style={{ color: 'var(--hr-text-muted)' }}>{it.pct}%</span>
                ) : null}
              </div>
            ) : null}
          </li>
        ))}
      </ul>
    </div>
  );
});

export default ChartLegend;

