// Utility helpers: export CSV and PNG (SVG snapshot)

export function exportDataAsCsv(filename, rows, headers) {
  const headerKeys = headers?.map((h) => h.key) || Object.keys(rows[0] || {});
  const headerLabels = headers?.map((h) => h.label) || headerKeys;
  const csv = [headerLabels.join(',')]
    .concat(rows.map((row) => headerKeys.map((k) => JSON.stringify(row[k] ?? '')).join(',')))
    .join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename.endsWith('.csv') ? filename : `${filename}.csv`;
  link.click();
  URL.revokeObjectURL(link.href);
}

export async function exportChartAsPng(filename, container) {
  if (!container) return;
  const svg = container.querySelector('svg');
  if (!svg) return;
  const serializer = new XMLSerializer();
  const svgString = serializer.serializeToString(svg);
  const blob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const img = new Image();
  const { width, height } = svg.getBoundingClientRect();
  await new Promise((resolve, reject) => {
    img.onload = resolve;
    img.onerror = reject;
    img.src = url;
  });
  const canvas = document.createElement('canvas');
  canvas.width = Math.max(1, Math.floor(width));
  canvas.height = Math.max(1, Math.floor(height));
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--hr-surface') || '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.drawImage(img, 0, 0);
  URL.revokeObjectURL(url);
  const png = canvas.toDataURL('image/png');
  const link = document.createElement('a');
  link.href = png;
  link.download = filename.endsWith('.png') ? filename : `${filename}.png`;
  link.click();
}

