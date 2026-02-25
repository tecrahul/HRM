import React, { useMemo, useState, useRef, useEffect } from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from 'recharts';
import { CHART_ANIM, CHART_PALETTE } from './ChartTheme';
import { ChartTooltip } from './ChartTooltip';

// Props:
// data: [{ key, label, value, color?, meta? }]
// height, onSliceClick, showCenterTotal, total, palette
export const AnalyticsDonutChart = React.memo(function AnalyticsDonutChart({
  data = [],
  height = 260,
  total: totalProp,
  palette,
  showCenterTotal = true,
  onSliceClick,
  tooltipTitle,
  className = '',
}) {
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);

  const clean = useMemo(() => (
    (Array.isArray(data) ? data : [])
      .map((d, idx) => ({
        key: String(d.key ?? d.label ?? idx),
        label: String(d.label ?? d.key ?? ''),
        value: Number(d.value ?? 0),
        color: d.color,
        meta: d.meta,
      }))
  ), [data]);

  const total = useMemo(() => {
    if (typeof totalProp === 'number') return totalProp;
    return clean.reduce((acc, d) => acc + (Number.isFinite(d.value) ? d.value : 0), 0);
  }, [clean, totalProp]);

  const colors = CHART_PALETTE(palette);
  const [active, setActive] = useState(null);

  return (
    <div
      className={className}
      style={{
        height,
        transition: CHART_ANIM.css,
        opacity: mounted ? 1 : 0,
        transform: mounted ? 'scale(1)' : 'scale(0.98)',
      }}
    >
      <ResponsiveContainer width="100%" height="100%">
        <PieChart>
          <Tooltip
            isAnimationActive
            content={<ChartTooltip title={tooltipTitle} showPercentage valueFormatter={(v) => v.toLocaleString()} />}
          />
          <Pie
            data={clean.map((d, i) => ({ ...d, fill: d.color || colors[i % colors.length], __total: total }))}
            dataKey="value"
            nameKey="label"
            innerRadius={height * 0.26}
            outerRadius={height * 0.38}
            paddingAngle={1.5}
            isAnimationActive
            animationDuration={CHART_ANIM.duration}
            onMouseEnter={(_, idx) => setActive(idx)}
            onMouseLeave={() => setActive(null)}
            onClick={(entry) => onSliceClick && onSliceClick(entry)}
          >
            {clean.map((entry, idx) => (
              <Cell
                key={`cell-${entry.key}`}
                fill={entry.color || colors[idx % colors.length]}
                opacity={active === null || active === idx ? 1 : 0.5}
                stroke="none"
              />
            ))}
          </Pie>
        </PieChart>
      </ResponsiveContainer>
      {showCenterTotal ? (
        <div className="pointer-events-none select-none" style={{
          position: 'relative',
          marginTop: -height * 0.62,
          height: 0,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
        }}>
          <div style={{ textAlign: 'center' }}>
            <div style={{ fontSize: 16, fontWeight: 800, color: 'var(--hr-text-main)' }}>{Number(total).toLocaleString()}</div>
            <div style={{ fontSize: 11, fontWeight: 600, color: 'var(--hr-text-muted)' }}>total</div>
          </div>
        </div>
      ) : null}
    </div>
  );
});

export default AnalyticsDonutChart;

