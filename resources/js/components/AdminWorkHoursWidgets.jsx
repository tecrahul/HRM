import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { buildDashboardSummaryQuery } from '../services/adminDashboardApi';
import { fetchAvgWorkHours, fetchMonthlyWorkHours } from '../services/workHoursApi';
import { AnalyticsAreaChart, AnalyticsBarChart } from './common/charts';

function LoadingSkeleton() {
  return (
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-5">
      {[0, 1].map((i) => (
        <div key={i} className="rounded-xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
          <div className="animate-pulse">
            <div className="h-4 w-40 rounded bg-slate-300/30" />
            <div className="mt-1 h-3 w-64 rounded bg-slate-300/25" />
            <div className="mt-4 h-8 w-32 rounded bg-slate-300/25" />
            <div className="mt-6 h-56 w-full rounded-xl border bg-slate-300/20" style={{ borderColor: 'var(--hr-line)' }} />
          </div>
        </div>
      ))}
    </div>
  );
}

function FilterDropdown({ value, onChange }) {
  return (
    <select
      className="ui-select"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      aria-label="Time filter"
    >
      <option value="7d">Last 7 Days</option>
      <option value="30d">Last 30 Days</option>
      <option value="month">This Month</option>
      <option value="custom">Custom Range</option>
    </select>
  );
}

function DateInput({ value, onChange, label }) {
  return (
    <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
      {label}
      <input
        type="date"
        className="ui-input mt-1"
        value={value}
        onChange={(e) => onChange(e.target.value)}
      />
    </label>
  );
}

function Hours({ value }) {
  const hours = Number(value || 0).toFixed(1);
  return <>{hours} Hours</>;
}

function Trend({ pct }) {
  if (pct === null || typeof pct === 'undefined') return null;
  const n = Number(pct);
  const positive = n > 0;
  const negative = n < 0;
  const color = positive ? '#16a34a' : negative ? '#dc2626' : 'var(--hr-text-muted)';
  const sign = positive ? '+' : '';
  return (
    <span className="text-xs font-bold" style={{ color }}>
      {sign}{n.toFixed(1)}%
    </span>
  );
}

function AvgWorkHoursCard({ endpoint, baseQuery }) {
  const [range, setRange] = useState('7d');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [payload, setPayload] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const query = useMemo(() => ({ ...baseQuery, range, ...(range === 'custom' ? { from, to } : {}) }), [baseQuery, range, from, to]);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const ctrl = new AbortController();
      const data = await fetchAvgWorkHours(endpoint, query, ctrl.signal);
      setPayload(data);
    } catch (_e) {
      setError('Unable to load Avg Work Hours.');
    } finally {
      setLoading(false);
    }
  }, [endpoint, query]);

  useEffect(() => { load(); }, [load]);

  const series = useMemo(() => {
    const s = Array.isArray(payload?.chart?.series) ? payload.chart.series : [];
    return s.map((d) => ({ label: d.label, avg_hours: Number(d.avg_hours || 0) }));
  }, [payload]);

  return (
    <article className="rounded-xl border p-4 h-full" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
      <div className="ui-section-head">
        <div>
          <h3 className="ui-section-title">Avg Work Hours</h3>
          <p className="ui-section-subtitle">Track the average hours worked across all employees.</p>
        </div>
        <div className="flex items-center gap-2">
          <FilterDropdown value={range} onChange={setRange} />
        </div>
      </div>

      {range === 'custom' ? (
        <div className="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
          <DateInput label="From" value={from} onChange={setFrom} />
          <DateInput label="To" value={to} onChange={setTo} />
          <div className="flex items-end">
            <button type="button" className="ui-btn ui-btn-primary" onClick={load} disabled={loading}>Apply</button>
          </div>
        </div>
      ) : null}

      {error && (
        <div className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">{error}</div>
      )}

      {loading && !payload ? (
        <div className="mt-4 animate-pulse">
          <div className="h-8 w-40 rounded bg-slate-300/25" />
          <div className="mt-6 h-56 w-full rounded-xl border bg-slate-300/20" style={{ borderColor: 'var(--hr-line)' }} />
        </div>
      ) : (
        <>
          <div className="mt-2 flex items-end gap-3">
            <p className="text-3xl font-extrabold" style={{ color: 'var(--hr-text-main)' }}>
              <Hours value={payload?.metric?.valueHours || 0} />
            </p>
            <Trend pct={payload?.metric?.trendPct} />
          </div>
          <div className="mt-4">
            <AnalyticsAreaChart
              data={series}
              series={[{ key: 'avg_hours', label: 'Avg Hours', color: '#0ea5e9' }]}
              height={220}
              smooth
              tooltipTitle="Avg Hours"
            />
          </div>
        </>
      )}
    </article>
  );
}

function WorkHoursMonthlyCard({ endpoint, baseQuery }) {
  const [payload, setPayload] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const ctrl = new AbortController();
      const data = await fetchMonthlyWorkHours(endpoint, baseQuery, ctrl.signal);
      setPayload(data);
    } catch (_e) {
      setError('Unable to load monthly work hours.');
    } finally {
      setLoading(false);
    }
  }, [endpoint, baseQuery]);

  useEffect(() => { load(); }, [load]);

  const series = useMemo(() => {
    const s = Array.isArray(payload?.chart?.series) ? payload.chart.series : [];
    return s.map((d) => ({ label: d.label, work_hours: Number(d.work_hours || 0), overtime_hours: Number(d.overtime_hours || 0) }));
  }, [payload]);

  return (
    <article className="rounded-xl border p-4 h-full" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
      <div className="ui-section-head">
        <div>
          <h3 className="ui-section-title">Work Hours Per Month</h3>
          <p className="ui-section-subtitle">Total work-time and overtime by month.</p>
        </div>
      </div>

      {error && (
        <div className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">{error}</div>
      )}

      {loading && !payload ? (
        <div className="mt-4 animate-pulse">
          <div className="h-4 w-80 rounded bg-slate-300/25" />
          <div className="mt-6 h-56 w-full rounded-xl border bg-slate-300/20" style={{ borderColor: 'var(--hr-line)' }} />
        </div>
      ) : (
        <>
          <div className="mt-2 grid grid-cols-2 gap-3 text-sm">
            <div>
              <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Work-Time</p>
              <p className="mt-1 font-extrabold" style={{ color: 'var(--hr-text-main)' }}>{payload?.summary?.workTimeLabel || '0h 00m'}</p>
            </div>
            <div>
              <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Overtime</p>
              <p className="mt-1 font-extrabold" style={{ color: 'var(--hr-text-main)' }}>{payload?.summary?.overtimeLabel || '0h 00m'}</p>
            </div>
          </div>
          <div className="mt-4">
            <AnalyticsBarChart
              data={series}
              series={[
                { key: 'work_hours', label: 'Work-Time', color: '#0ea5e9' },
                { key: 'overtime_hours', label: 'Overtime', color: '#8b5cf6' },
              ]}
              height={240}
              tooltipTitle="Hours"
              valueFormatter={(v) => {
                const minutes = Math.round(Number(v || 0) * 60);
                const h = Math.floor(minutes / 60);
                const m = minutes % 60;
                return `${h}h ${String(m).padStart(2, '0')}m`;
              }}
            />
          </div>
        </>
      )}
    </article>
  );
}

function AdminWorkHoursWidgets({ avgEndpoint, monthlyEndpoint, initialBranchId = '', initialDepartmentId = '' }) {
  const baseQuery = useMemo(
    () => buildDashboardSummaryQuery({ branchId: initialBranchId, departmentId: initialDepartmentId }),
    [initialBranchId, initialDepartmentId],
  );

  return (
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-5 items-stretch">
      <AvgWorkHoursCard endpoint={avgEndpoint} baseQuery={baseQuery} />
      <WorkHoursMonthlyCard endpoint={monthlyEndpoint} baseQuery={baseQuery} />
    </div>
  );
}

export function mountAdminWorkHoursWidgets() {
  const root = document.getElementById('admin-dashboard-work-hours-root');
  if (!root) return;
  const avgEndpoint = root.dataset.avgEndpoint || '';
  const monthlyEndpoint = root.dataset.monthlyEndpoint || '';
  const initialBranchId = root.dataset.branchId || '';
  const initialDepartmentId = root.dataset.departmentId || '';
  createRoot(root).render(
    <AdminWorkHoursWidgets
      avgEndpoint={avgEndpoint}
      monthlyEndpoint={monthlyEndpoint}
      initialBranchId={initialBranchId}
      initialDepartmentId={initialDepartmentId}
    />,
  );
}
