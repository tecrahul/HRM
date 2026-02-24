import React, { useMemo } from 'react';
import { AnalyticsPieCard } from './AnalyticsPieCard';

const STATUS_COLORS = {
  approved: '#16a34a',
  pending: '#f59e0b',
  rejected: '#ef4444',
  other: '#64748b',
};

export default function LeaveStatusBreakdown({ dataset, delay = 0 }) {
  const data = useMemo(() => (
    (Array.isArray(dataset?.items) ? dataset.items : []).map((it) => ({
      key: String(it.key || it.status || it.label),
      label: String(it.label || it.status || it.key || ''),
      count: Number(it.count ?? it.value ?? 0),
      color: STATUS_COLORS[String(it.key || it.status)] || STATUS_COLORS.other,
    }))
  ), [dataset]);

  const total = useMemo(() => data.reduce((acc, d) => acc + Number(d.count || 0), 0), [data]);

  return (
    <AnalyticsPieCard
      title="Leave Breakdown (Status)"
      data={data}
      total={total}
      showLegend={true}
      showCenterTotal={true}
      colors={STATUS_COLORS}
      delay={delay}
    />
  );
}

