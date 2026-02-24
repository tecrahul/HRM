import React, { useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import AttendanceBreakdown from './AttendanceBreakdown';
import PayrollBreakdown from './PayrollBreakdown';
import LeaveStatusBreakdown from './LeaveStatusBreakdown';
import LeaveTypeBreakdown from './LeaveTypeBreakdown';

const parsePayload = (node) => {
  if (!node) return null;
  const raw = node.dataset.payload;
  if (!raw) return null;
  try { return JSON.parse(raw); } catch { return null; }
};

function ReportsAnalyticsApp({ payload }) {
  const sections = useMemo(() => ({
    attendance: Boolean(payload?.sections?.attendance),
    leave: Boolean(payload?.sections?.leave),
    payroll: Boolean(payload?.sections?.payroll),
  }), [payload]);

  const attendance = payload?.attendance ?? { items: [] };
  const leaveStatus = payload?.leaveStatus ?? { items: [] };
  const leaveType = payload?.leaveType ?? { items: [] };
  const payroll = payload?.payroll ?? { items: [] };
  const financials = payload?.financials ?? { net: 0, deductions: 0 };

  const tiles = [];
  // Row 1: Attendance, Payroll
  if (sections.attendance) tiles.push(
    <AttendanceBreakdown key="attendance" dataset={attendance} delay={60} />
  );
  if (sections.payroll) tiles.push(
    <PayrollBreakdown key="payroll" statusDataset={payroll} financials={financials} delay={120} />
  );
  // Row 2: Leave (Status), Leave (Type)
  if (sections.leave) {
    tiles.push(
      <LeaveStatusBreakdown key="leave-status" dataset={leaveStatus} delay={180} />,
      <LeaveTypeBreakdown key="leave-type" dataset={leaveType} delay={240} />,
    );
  }

  return (
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-5 auto-rows-fr">
      {tiles}
    </div>
  );
}

export function mountReportsAnalytics() {
  const root = document.getElementById('reports-analytics-root');
  if (!root) return;
  const payload = parsePayload(root) || {};
  createRoot(root).render(<ReportsAnalyticsApp payload={payload} />);
}

export default ReportsAnalyticsApp;
