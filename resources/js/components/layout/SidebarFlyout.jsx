import React, { useEffect, useMemo, useRef, useState } from 'react';

export default function SidebarFlyout({ anchorRect, anchorEl, anchorLabelId, items = [], visible, onRequestClose }) {
  const [style, setStyle] = useState({});
  const containerRef = useRef(null);
  const itemRefs = useRef([]);
  const [activeIndex, setActiveIndex] = useState(0);

  useEffect(() => {
    if (!anchorRect || !visible) return;
    const top = Math.round(anchorRect.top);
    const left = Math.round(anchorRect.right + 8);
    setStyle({ top: `${top}px`, left: `${left}px`, minWidth: '220px' });
  }, [anchorRect, visible]);

  useEffect(() => {
    if (!visible || items.length === 0) return;
    // Reset active index to first active or 0
    const idx = Math.max(0, items.findIndex((i) => i.active));
    setActiveIndex(idx);
  }, [visible, items]);

  useEffect(() => {
    if (!visible) return;
    const el = itemRefs.current[activeIndex];
    if (el) {
      el.focus();
    }
  }, [visible, activeIndex]);

  useEffect(() => {
    if (!visible) return;
    const onDocClick = (e) => {
      const target = e.target instanceof Element ? e.target : null;
      const inside = target && containerRef.current && containerRef.current.contains(target);
      const onAnchor = target && anchorEl && anchorEl.contains(target);
      if (!inside && !onAnchor) {
        onRequestClose?.();
      }
    };
    const onKeydown = (e) => {
      if (!visible) return;
      if (e.key === 'Escape' || e.key === 'ArrowLeft') {
        e.preventDefault();
        onRequestClose?.();
        if (anchorEl) {
          anchorEl.focus();
        }
      }
      if (e.key === 'Tab') {
        const lastIndex = items.length - 1;
        if (e.shiftKey && activeIndex === 0) {
          e.preventDefault();
          setActiveIndex(lastIndex);
          return;
        }
        if (!e.shiftKey && activeIndex === lastIndex) {
          e.preventDefault();
          setActiveIndex(0);
          return;
        }
      }
    };
    document.addEventListener('mousedown', onDocClick, true);
    document.addEventListener('keydown', onKeydown);
    return () => {
      document.removeEventListener('mousedown', onDocClick, true);
      document.removeEventListener('keydown', onKeydown);
    };
  }, [visible, anchorEl, activeIndex, items.length, onRequestClose]);

  const onItemKeyDown = (e) => {
    if (!visible) return;
    const lastIndex = items.length - 1;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveIndex((i) => (i + 1 > lastIndex ? 0 : i + 1));
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex((i) => (i - 1 < 0 ? lastIndex : i - 1));
    }
    if (e.key === 'Home') {
      e.preventDefault();
      setActiveIndex(0);
    }
    if (e.key === 'End') {
      e.preventDefault();
      setActiveIndex(lastIndex);
    }
  };

  if (!visible || items.length === 0) return null;

  return (
    <div ref={containerRef} className="fixed z-[1050] transition-all duration-150" style={style} role="menu" aria-labelledby={anchorLabelId || undefined}>
      <div className="rounded-xl border shadow-lg p-2 transition-all duration-150" style={{ background: 'var(--hr-surface)', borderColor: 'var(--hr-line)' }}>
        <ul className="flex flex-col gap-1">
          {items.map((item, idx) => (
            <li key={item.key}>
              <a
                ref={(el) => (itemRefs.current[idx] = el)}
                href={item.url}
                role="menuitem"
                tabIndex={idx === activeIndex ? 0 : -1}
                onKeyDown={onItemKeyDown}
                className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold hover:opacity-90 ${item.active ? 'ring-1' : ''}`}
                style={{ color: 'var(--hr-text-main)', borderColor: 'var(--hr-accent)' }}
              >
                <span className="h-1.5 w-1.5 rounded-full" style={{ background: 'var(--hr-accent)' }} />
                <span>{item.label}</span>
              </a>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
