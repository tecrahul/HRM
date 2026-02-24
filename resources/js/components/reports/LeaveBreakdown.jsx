import React, { useMemo } from 'react';
import { AnalyticsPieCard } from './AnalyticsPieCard';

const STATUS_COLORS = {
  approved: '#16a34a',
  pending: '#f59e0b',
  rejected: '#ef4444',
  other: '#64748b',
};

const TYPE_COLORS = {
  casual: '#0ea5e9',
  sick: '#f97316',
  earned: '#10b981',
  unpaid: '#64748b',
  maternity: '#9333ea',
  paternity: '#06b6d4',
  other: '#94a3b8',
};

export function LeaveBreakdown({ statusDataset, typeDataset, delay = 0 }) {
  const statusData = useMemo(() => (
    (Array.isArray(statusDataset?.items) ? statusDataset.items : []).map((it) => ({
      key: String(it.key || it.status || it.label),
      label: String(it.label || it.status || it.key || ''),
      count: Number(it.count ?? it.value ?? 0),
      color: STATUS_COLORS[String(it.key || it.status)] || STATUS_COLORS.other,
    }))
  ), [statusDataset]);

  const typeData = useMemo(() => (
    (Array.isArray(typeDataset?.items) ? typeDataset.items : []).map((it) => ({
      key: String(it.key || it.leave_type || it.label),
      label: String(it.label || it.leave_type || it.key || ''),
      count: Number(it.count ?? it.value ?? 0),
      color: TYPE_COLORS[String(it.key || it.leave_type)] || TYPE_COLORS.other,
    }))
  ), [typeDataset]);

  const statusTotal = useMemo(() => statusData.reduce((acc, d) => acc + Number(d.count || 0), 0), [statusData]);
  const typeTotal = useMemo(() => typeData.reduce((acc, d) => acc + Number(d.count || 0), 0), [typeData]);

  return (
    <article className="ui-section">
      <div className="ui-section-head">
        <div>
          <h3 className="ui-section-title">Leave Breakdown</h3>
          <p className="ui-section-subtitle">Status and type distribution</p>
        </div>
      </div>

      <div className="mt-4 grid grid-cols-1 gap-6">
        <AnalyticsPieCard
          title="Leave by Status"
          data={statusData}
          total={statusTotal}
          showLegend={true}
          showCenterTotal={true}
          colors={STATUS_COLORS}
          delay={delay}
        />

        <AnalyticsPieCard
          title="Leave by Type"
          data={typeData}
          total={typeTotal}
          showLegend={true}
          showCenterTotal={true}
          colors={TYPE_COLORS}
          delay={delay + 120}
        />
      </div>
    </article>
  );
}

export default LeaveBreakdown;

