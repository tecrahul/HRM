import React from 'react';
import { CHART_TYPOGRAPHY } from './ChartTheme';

// Reusable tooltip renderer for Recharts
// Props: title (optional), showPercentage, formatter (value,label)=>{value,label,meta}
export const ChartTooltip = React.memo(function ChartTooltip({
  active,
  payload,
  label,
  title,
  showPercentage = false,
  valueFormatter = (v) => v,
  labelFormatter = (l) => l,
}) {
  if (!active || !payload || payload.length === 0) return null;

  const item = payload[0] || {};
  const raw = Number(item.value ?? item.payload?.value ?? 0);
  const total = Number(item.payload?.__total ?? item.payload?.total ?? 0);
  const pct = total > 0 ? ((raw / total) * 100).toFixed(1) : null;
  const resolvedLabel = labelFormatter(item.name ?? label ?? '');
  const resolvedValue = valueFormatter(raw);

  return (
    <div
      className="rounded-lg shadow-lg"
      style={{
        background: 'var(--hr-surface-strong)',
        border: '1px solid var(--hr-line)',
        padding: '8px 10px',
        minWidth: 140,
        color: 'var(--hr-text-main)',
      }}
    >
      {title ? (
        <div style={{ fontSize: 12, fontWeight: 700, marginBottom: 4 }}>{title}</div>
      ) : null}
      <div style={{ display: 'flex', justifyContent: 'space-between', gap: 8 }}>
        <div style={{ fontSize: CHART_TYPOGRAPHY.labelSize, color: 'var(--hr-text-muted)', fontWeight: 600 }}>
          {resolvedLabel}
        </div>
        <div style={{ fontSize: CHART_TYPOGRAPHY.valueSize, fontWeight: 800 }}>{resolvedValue}</div>
      </div>
      {showPercentage && pct !== null ? (
        <div style={{ marginTop: 2, fontSize: 11, color: 'var(--hr-text-muted)' }}>{pct}%</div>
      ) : null}
      {item.payload?.meta ? (
        <div style={{ marginTop: 6, fontSize: 11 }}>{String(item.payload.meta)}</div>
      ) : null}
    </div>
  );
});

export default ChartTooltip;

