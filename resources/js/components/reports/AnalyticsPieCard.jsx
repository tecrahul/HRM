import React, { useEffect, useMemo, useState } from 'react';
import { AnalyticsDonutChart } from '../common/charts/AnalyticsDonutChart';
import { ChartLegend } from '../common/charts/ChartLegend';

const countUp = (toValue, setValue, duration = 900, fromValue = 0) => {
  const start = performance.now();
  const diff = toValue - fromValue;
  const step = (now) => {
    const t = Math.min(1, (now - start) / duration);
    const eased = 1 - Math.pow(1 - t, 3);
    setValue(Math.round(fromValue + diff * eased));
    if (t < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
};

export function AnalyticsPieCard({
  title,
  data = [],
  total = 0,
  showLegend = true,
  showCenterTotal = true,
  colors = {},
  height = 260,
  delay = 0,
  financialSummary = null, // { items: [{label, value, prefix, decimals}], animate: true }
}) {
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);

  const normalized = useMemo(() => (
    (Array.isArray(data) ? data : []).map((d, idx) => ({
      key: String(d.key || d.label || idx),
      label: String(d.label || d.key || ''),
      value: Number(d.value ?? d.count ?? 0),
      color: colors[String(d.key)] || d.color,
    }))
  ), [data, colors]);

  const sum = useMemo(() => normalized.reduce((acc, d) => acc + Number(d.value || 0), 0), [normalized]);
  const grandTotal = Number(total || sum || 0);

  const [hidden, setHidden] = useState(new Set());
  const displayed = useMemo(() => normalized.filter((d) => !hidden.has(d.key)), [normalized, hidden]);
  const legendItems = useMemo(() => {
    if (!showLegend) return [];
    return normalized.map((d) => ({
      key: d.key,
      label: d.label,
      color: d.color || '#64748b',
      value: d.value,
      pct: sum > 0 ? ((d.value / sum) * 100).toFixed(1) : '0.0',
      visible: !hidden.has(d.key),
    }));
  }, [normalized, hidden, showLegend, sum]);

  return (
    <article className={`ui-section transition-opacity duration-500 ${mounted ? 'opacity-100' : 'opacity-0'}`} style={{ minHeight: financialSummary ? 380 : 320 }}>
      <div className="ui-section-head">
        <div>
          <h3 className="ui-section-title">{title}</h3>
          <p className="ui-section-subtitle">Interactive breakdown</p>
        </div>
      </div>

      <div className={`mt-4 grid gap-6 ${showLegend ? 'lg:grid-cols-[minmax(0,1fr)_240px]' : 'lg:grid-cols-1'} items-start`}>
        <AnalyticsDonutChart data={displayed} height={height} total={grandTotal} showCenterTotal={showCenterTotal} tooltipTitle={title} />
        {showLegend ? (
          <ChartLegend
            items={legendItems}
            onToggle={(key) => setHidden((prev) => { const next = new Set(prev); if (next.has(key)) next.delete(key); else next.add(key); return next; })}
          />
        ) : null}
      </div>

      {financialSummary && (
        <div className="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
          {financialSummary.items.map((it, idx) => (
            <div key={idx} className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
              <p className="ui-kpi-label">{it.label}</p>
              <AnimatedValue value={it.value} prefix={it.prefix || ''} decimals={it.decimals ?? 2} delay={80 + idx * 120} />
            </div>
          ))}
        </div>
      )}
    </article>
  );
}

function AnimatedValue({ value, prefix = '', decimals = 2, delay = 0 }) {
  const [display, setDisplay] = useState(0);
  useEffect(() => {
    const timeout = setTimeout(() => countUp(value, (v) => setDisplay(v), 900), delay);
    return () => clearTimeout(timeout);
  }, [value, delay]);
  return (
    <p className="text-lg font-extrabold mt-1">{prefix}{Number(display).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals })}</p>
  );
}

export default AnalyticsPieCard;
