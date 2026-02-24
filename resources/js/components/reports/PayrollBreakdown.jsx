import React, { useMemo } from 'react';
import { AnalyticsPieCard } from './AnalyticsPieCard';

const PAYROLL_COLORS = {
  paid: '#10b981',
  processed: '#f59e0b',
  draft: '#64748b',
  failed: '#ef4444',
  other: '#94a3b8',
};

export function PayrollBreakdown({ statusDataset, financials, delay = 0 }) {
  const statusData = useMemo(() => (
    (Array.isArray(statusDataset?.items) ? statusDataset.items : []).map((it) => ({
      key: String(it.key || it.status || it.label),
      label: String(it.label || it.status || it.key || ''),
      count: Number(it.count ?? it.value ?? 0),
      color: PAYROLL_COLORS[String(it.key || it.status)] || PAYROLL_COLORS.other,
    }))
  ), [statusDataset]);

  const total = useMemo(() => statusData.reduce((acc, d) => acc + Number(d.count || 0), 0), [statusData]);

  const summary = useMemo(() => {
    const net = Number(financials?.net || 0);
    const deductions = Number(financials?.deductions || 0);
    const totalPayroll = net + deductions;
    return {
      items: [
        { label: 'Net Salary', value: net, prefix: '$', decimals: 2 },
        { label: 'Deductions', value: deductions, prefix: '$', decimals: 2 },
        { label: 'Total Payroll', value: totalPayroll, prefix: '$', decimals: 2 },
      ],
      animate: true,
    };
  }, [financials]);

  return (
    <AnalyticsPieCard
      title="Payroll Breakdown"
      data={statusData}
      total={total}
      showLegend={true}
      showCenterTotal={true}
      colors={PAYROLL_COLORS}
      delay={delay}
      financialSummary={summary}
    />
  );
}

export default PayrollBreakdown;

