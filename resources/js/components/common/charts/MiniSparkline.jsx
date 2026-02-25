import React, { useEffect, useMemo, useState } from 'react';
import { ResponsiveContainer, LineChart, Line, Tooltip } from 'recharts';
import { CHART_ANIM } from './ChartTheme';
import { ChartTooltip } from './ChartTooltip';

// data: array of numbers or { value }
export const MiniSparkline = React.memo(function MiniSparkline({
  data = [],
  height = 28,
  color = '#0ea5e9',
  showTooltip = false,
  className = '',
}) {
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);

  const ds = useMemo(() => (
    (Array.isArray(data) ? data : []).map((d, i) => ({ idx: i, value: typeof d === 'number' ? d : Number(d?.value ?? 0) }))
  ), [data]);

  if (ds.length < 2) return null;

  return (
    <div className={className} style={{ height, transition: CHART_ANIM.css, opacity: mounted ? 1 : 0, transform: mounted ? 'scale(1)' : 'scale(0.98)' }}>
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={ds} margin={{ top: 0, right: 0, bottom: 0, left: 0 }}>
          {showTooltip ? (
            <Tooltip isAnimationActive content={<ChartTooltip valueFormatter={(v) => Number(v ?? 0).toLocaleString()} />} />
          ) : null}
          <Line
            type="monotone"
            dataKey="value"
            stroke={color}
            strokeWidth={2}
            dot={false}
            isAnimationActive
            animationDuration={CHART_ANIM.duration}
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
});

export default MiniSparkline;

