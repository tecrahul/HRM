import React, { useEffect, useMemo, useRef, useState } from 'react';

// Lazy-load Chart.js from CDN when needed
let chartJsPromise = null;
const ensureChartJs = () => {
  if (typeof window !== 'undefined' && window.Chart) {
    return Promise.resolve(window.Chart);
  }
  if (chartJsPromise) return chartJsPromise;
  chartJsPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
    script.async = true;
    script.onload = () => resolve(window.Chart);
    script.onerror = reject;
    document.head.appendChild(script);
  });
  return chartJsPromise;
};

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
  const canvasRef = useRef(null);
  const chartRef = useRef(null);
  const [displayTotal, setDisplayTotal] = useState(0);
  const [mounted, setMounted] = useState(false);

  const labels = useMemo(() => data.map((d) => String(d.label || d.key)), [data]);
  const values = useMemo(() => data.map((d) => Number(d.value ?? d.count ?? 0)), [data]);
  const palette = useMemo(() => data.map((d) => colors[String(d.key)] || d.color || '#64748b'), [data, colors]);
  const sum = useMemo(() => (Array.isArray(values) ? values.reduce((a, b) => a + b, 0) : 0), [values]);

  useEffect(() => {
    setMounted(true);
    if (showCenterTotal) {
      setTimeout(() => countUp(total || sum, setDisplayTotal, 900), 80 + delay);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [total, sum]);

  useEffect(() => {
    let destroyed = false;
    let chartInstance = null;

    const setupChart = async () => {
      const Chart = await ensureChartJs();
      if (destroyed || !canvasRef.current) return;

      const ctx = canvasRef.current.getContext('2d');
      if (!ctx) return;

      // Custom plugin for center total label
      const centerText = {
        id: 'centerText',
        afterDraw(chart, args, pluginOptions) {
          if (!showCenterTotal) return;
          const { width, height } = chart;
          const { ctx } = chart;
          ctx.save();
          const text = String(displayTotal);
          ctx.font = '700 16px Inter, Manrope, system-ui, sans-serif';
          ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--hr-text-main') || '#0f172a';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText(text, width / 2, height / 2);
          ctx.font = '500 11px Inter, Manrope, system-ui, sans-serif';
          ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--hr-text-muted') || '#64748b';
          ctx.fillText('total', width / 2, height / 2 + 18);
          ctx.restore();
        },
      };

      const options = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          delay,
          duration: 900,
          easing: 'easeOutQuart',
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            enabled: true,
            backgroundColor: 'rgba(15, 23, 42, 0.85)',
            padding: 10,
            titleColor: '#fff',
            bodyColor: '#e2e8f0',
            callbacks: {
              label(ctx) {
                const value = Number(ctx.parsed || 0);
                const pct = sum > 0 ? ((value / sum) * 100).toFixed(1) : '0.0';
                return `${ctx.label}: ${value} (${pct}%)`;
              },
            },
          },
        },
        hover: { mode: 'nearest', intersect: true },
      };

      chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [
            {
              data: values,
              backgroundColor: palette,
              borderWidth: 0,
              hoverOffset: 8,
            },
          ],
        },
        options: {
          ...options,
          cutout: '62%',
        },
        plugins: [centerText],
      });

      chartRef.current = chartInstance;
    };

    setupChart();

    return () => {
      destroyed = true;
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [labels.join('|'), values.join('|'), palette.join('|'), sum, displayTotal]);

  const legendItems = useMemo(() => {
    if (!showLegend) return [];
    return data.map((d, idx) => {
      const count = Number(d.value ?? d.count ?? 0);
      const pct = sum > 0 ? ((count / sum) * 100) : 0;
      return {
        key: String(d.key || d.label || idx),
        label: String(d.label || d.key || ''),
        count,
        pct: pct.toFixed(1),
        color: palette[idx] || '#64748b',
      };
    });
  }, [data, sum, palette, showLegend]);

  return (
    <article
      className={`ui-section transition-opacity duration-500 ${mounted ? 'opacity-100' : 'opacity-0'}`}
      style={{ minHeight: financialSummary ? 380 : 320 }}
    >
      <div className="ui-section-head">
        <div>
          <h3 className="ui-section-title">{title}</h3>
          <p className="ui-section-subtitle">Interactive breakdown</p>
        </div>
      </div>

      <div className={`mt-4 grid gap-6 ${showLegend ? 'lg:grid-cols-[minmax(0,1fr)_240px]' : 'lg:grid-cols-1'} items-start`}>
        <div className="relative" style={{ height }}>
          <canvas ref={canvasRef} role="img" style={{ cursor: 'pointer' }} />
        </div>

        {showLegend && (
          <div className="space-y-2 text-sm">
            <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>Legend</p>
            <ul className="space-y-1.5">
              {legendItems.map((item) => (
                <li key={item.key} className="flex items-center justify-between gap-3 py-1">
                  <div className="flex items-center gap-2 font-semibold">
                    <span className="h-2.5 w-2.5 rounded-full" style={{ background: item.color }}></span>
                    {item.label}
                  </div>
                  <div className="flex items-baseline gap-2">
                    <span>{item.count}</span>
                    <span className="text-[0.65rem]" style={{ color: 'var(--hr-text-muted)' }}>{item.pct}%</span>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        )}
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

