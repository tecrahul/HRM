import React, { useEffect, useState } from 'react';

export default function SidebarTooltip({ anchorRect, label, visible }) {
  const [style, setStyle] = useState({});

  useEffect(() => {
    if (!anchorRect || !visible) return;
    const top = Math.round(anchorRect.top + anchorRect.height / 2);
    const left = Math.round(anchorRect.right + 8);
    setStyle({ top: `${top}px`, left: `${left}px` });
  }, [anchorRect, visible]);

  if (!visible) return null;

  return (
    <div className="fixed z-[1100] pointer-events-none" style={style}>
      <div className="px-2.5 py-1.5 text-xs font-semibold rounded-md shadow-md" style={{ background: 'rgba(15,23,42,0.92)', color: '#e2e8f0' }}>
        {label}
      </div>
    </div>
  );
}

