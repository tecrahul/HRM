import React, { useMemo } from 'react';
import { AnalyticsPieCard } from './AnalyticsPieCard';

const ATTENDANCE_COLORS = {
  present: '#16a34a',
  remote: '#0284c7',
  half_day: '#d97706',
  on_leave: '#f97316',
  late: '#c026d3',
  absent: '#dc2626',
  other: '#64748b',
};

export function AttendanceBreakdown({ dataset, delay = 0 }) {
  const colors = ATTENDANCE_COLORS;
  const normalized = useMemo(() => (
    (Array.isArray(dataset?.items) ? dataset.items : []).map((it) => ({
      key: String(it.key || it.status || it.label),
      label: String(it.label || it.status || it.key || ''),
      count: Number(it.count ?? it.value ?? 0),
      color: colors[String(it.key || it.status)] || colors.other,
    }))
  ), [dataset]);

  const total = useMemo(() => normalized.reduce((acc, d) => acc + Number(d.count || 0), 0), [normalized]);

  return (
    <AnalyticsPieCard
      title="Attendance Breakdown"
      data={normalized}
      total={total}
      showLegend={true}
      showCenterTotal={true}
      colors={colors}
      delay={delay}
    />
  );
}

export default AttendanceBreakdown;

