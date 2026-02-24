import React, { useMemo } from 'react';
import { AnalyticsPieCard } from './AnalyticsPieCard';

const TYPE_COLORS = {
  casual: '#0ea5e9',
  sick: '#f97316',
  earned: '#10b981',
  unpaid: '#64748b',
  maternity: '#9333ea',
  paternity: '#06b6d4',
  other: '#94a3b8',
};

export default function LeaveTypeBreakdown({ dataset, delay = 0 }) {
  const data = useMemo(() => (
    (Array.isArray(dataset?.items) ? dataset.items : []).map((it) => ({
      key: String(it.key || it.leave_type || it.label),
      label: String(it.label || it.leave_type || it.key || ''),
      count: Number(it.count ?? it.value ?? 0),
      color: TYPE_COLORS[String(it.key || it.leave_type)] || TYPE_COLORS.other,
    }))
  ), [dataset]);

  const total = useMemo(() => data.reduce((acc, d) => acc + Number(d.count || 0), 0), [data]);

  return (
    <AnalyticsPieCard
      title="Leave Breakdown (Type)"
      data={data}
      total={total}
      showLegend={true}
      showCenterTotal={true}
      colors={TYPE_COLORS}
      delay={delay}
    />
  );
}

