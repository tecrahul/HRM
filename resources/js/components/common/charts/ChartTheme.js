// Shared chart design system: colors, spacing, timing
export const CHART_COLORS = [
  '#0ea5e9', // sky/blue
  '#10b981', // emerald
  '#f59e0b', // amber
  '#ef4444', // red
  '#8b5cf6', // violet
  '#06b6d4', // cyan
  '#22c55e', // green
  '#f97316', // orange
  '#94a3b8', // slate
];

export const CHART_TYPOGRAPHY = {
  fontFamily: 'Inter, Manrope, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, sans-serif',
  labelSize: 11,
  valueSize: 12,
};

export const CHART_ANIM = {
  duration: 420, // 300–600ms window
  easing: 'ease-out',
  css: 'opacity 420ms ease-out, transform 420ms ease-out',
};

export const CHART_STYLE = {
  radius: 8,
  borderRadius: 8,
  gridStroke: 'rgb(148 163 184 / 0.25)',
  axisStroke: 'rgb(148 163 184 / 0.35)',
};

export const getVar = (name, fallback) => {
  if (typeof window === 'undefined') return fallback;
  const v = getComputedStyle(document.documentElement).getPropertyValue(name);
  return (v && v.trim()) || fallback;
};

export const CHART_PALETTE = (custom) => {
  if (Array.isArray(custom) && custom.length > 0) return custom;
  return CHART_COLORS;
};

