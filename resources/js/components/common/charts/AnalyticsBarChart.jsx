import React, { useMemo, useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';
import { CHART_ANIM, CHART_PALETTE, CHART_STYLE } from './ChartTheme';
import { ChartTooltip } from './ChartTooltip';

// Props: data [{label, seriesA, seriesB...}], series [{key,label,color?}], stacked, height
export const AnalyticsBarChart = React.memo(function AnalyticsBarChart({
  data = [],
  series = [],
  stacked = false,
  height = 300,
  palette,
  tooltipTitle,
  onBarClick,
  valueFormatter = (v) => Number(v ?? 0).toLocaleString(),
  className = '',
}) {
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);

  const ds = Array.isArray(data) ? data : [];
  const cols = CHART_PALETTE(palette);
  const [hidden, setHidden] = useState(() => new Set());
  const toggle = (key) => setHidden((prev) => {
    const next = new Set(prev);
    if (next.has(key)) next.delete(key); else next.add(key);
    return next;
  });

  const effectiveSeries = useMemo(() => (
    (Array.isArray(series) && series.length > 0 ? series : Object.keys(ds[0] || {}).filter((k) => k !== 'label').map((k, i) => ({ key: k, label: k, color: cols[i % cols.length] })))
  ), [series, ds, cols]);

  return (
    <div
      className={className}
      style={{ height, transition: CHART_ANIM.css, opacity: mounted ? 1 : 0, transform: mounted ? 'scale(1)' : 'scale(0.98)' }}
    >
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={ds}>
          <CartesianGrid stroke={CHART_STYLE.gridStroke} vertical={false} />
          <XAxis dataKey="label" stroke={CHART_STYLE.axisStroke} tick={{ fontSize: 11 }} />
          <YAxis stroke={CHART_STYLE.axisStroke} tick={{ fontSize: 11 }} />
          <Tooltip content={<ChartTooltip title={tooltipTitle} valueFormatter={valueFormatter} />} />
          <Legend onClick={(e) => toggle(e.dataKey)} />
          {effectiveSeries.map((s, i) => (
            <Bar
              key={s.key}
              dataKey={s.key}
              name={s.label}
              stackId={stacked ? 'stack' : undefined}
              fill={s.color || cols[i % cols.length]}
              isAnimationActive
              animationDuration={CHART_ANIM.duration}
              hide={hidden.has(s.key)}
              radius={4}
              onClick={(entry) => onBarClick && onBarClick({ seriesKey: s.key, entry })}
            />
          ))}
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
});

export default AnalyticsBarChart;
