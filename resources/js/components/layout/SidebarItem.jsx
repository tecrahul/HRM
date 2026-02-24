import React, { useEffect, useRef, useState } from 'react';
import SidebarTooltip from './SidebarTooltip';
import Icon from '../shared/Icon';

export default function SidebarItem({ item, isCollapsed, onOpenFlyout, activeColor = 'var(--hr-accent)', isFlyoutOpenForKey = false }) {
  const ref = useRef(null);
  const anchorRef = useRef(null);
  const [hover, setHover] = useState(false);
  const [anchorRect, setAnchorRect] = useState(null);
  const hasChildren = !!(item?.children && item.children.length > 0);
  const hasActiveChild = hasChildren && item.children.some((c) => c.active);
  const [open, setOpen] = useState(hasChildren && (item.active || hasActiveChild));

  useEffect(() => {
    if (!hover || !ref.current) return;
    const rect = ref.current.getBoundingClientRect();
    setAnchorRect(rect);
  }, [hover]);

  // Keep submenu visibility in sync with sidebar state and active route
  useEffect(() => {
    if (isCollapsed) {
      setOpen(false);
      return;
    }
    if (hasChildren && (item.active || hasActiveChild)) {
      setOpen(true);
    }
  }, [isCollapsed, item.active, hasActiveChild, hasChildren]);

  const onMouseEnter = () => {
    setHover(true);
    if (isCollapsed && item.children && item.children.length > 0 && ref.current) {
      const rect = ref.current.getBoundingClientRect();
      onOpenFlyout(item, rect, anchorRef.current, `sidebar-item-${item.key}`);
    }
  };
  const onMouseLeave = () => {
    setHover(false);
  };

  const onKeyDown = (e) => {
    if (!isCollapsed) return;
    const hasChildren = !!(item.children && item.children.length > 0);
    if (!hasChildren) return;
    if (e.key === 'ArrowRight' || e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      const rect = ref.current?.getBoundingClientRect();
      onOpenFlyout(item, rect, anchorRef.current, `sidebar-item-${item.key}`);
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const rect = ref.current?.getBoundingClientRect();
      onOpenFlyout(item, rect, anchorRef.current, `sidebar-item-${item.key}`);
    }
  };

  const linkContent = (
    <div className={`relative flex items-center ${isCollapsed ? 'justify-center' : 'justify-start gap-3'} w-full` }>
      <span className={`inline-flex items-center justify-center rounded-xl ${item.active ? 'ring-2 ring-offset-0' : ''}`}
            style={{ width: 40, height: 40, color: item.active ? activeColor : 'var(--hr-text-main)', borderColor: activeColor }}>
        <Icon name={item.icon} />
        {item.badge > 0 && (
          <span className="absolute -top-0 -right-0 h-4 min-w-4 px-1 text-[10px] leading-4 text-white rounded-full text-center"
                style={{ background: '#ef4444' }}>{Math.min(item.badge, 99)}</span>
        )}
      </span>
      {!isCollapsed && (
        <>
          <span className="truncate">{item.label}</span>
          {hasChildren && (
            <svg
              aria-hidden="true"
              className={`h-4 w-4 ml-auto transition-transform ${open ? 'rotate-180' : ''}`}
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
            >
              <path d="m6 9 6 6 6-6"></path>
            </svg>
          )}
        </>
      )}
    </div>
  );

  return (
    <li ref={ref} onMouseEnter={onMouseEnter} onMouseLeave={onMouseLeave}>
      <a
        id={`sidebar-item-${item.key}`}
        ref={anchorRef}
        href={item.url}
        className={`block rounded-xl px-2 py-1.5 ${item.active ? 'bg-transparent' : ''}`}
        style={{ color: 'var(--hr-text-main)' }}
        aria-haspopup={hasChildren ? 'menu' : undefined}
        aria-expanded={hasChildren ? (isCollapsed ? (isFlyoutOpenForKey ? 'true' : 'false') : (open ? 'true' : 'false')) : undefined}
        aria-controls={!isCollapsed && hasChildren ? `submenu-${item.key}` : undefined}
        onKeyDown={onKeyDown}
        onClick={(e) => {
          // In expanded mode, clicking a parent with children toggles submenu instead of navigating
          if (!isCollapsed && hasChildren) {
            e.preventDefault();
            setOpen((v) => !v);
          }
        }}
      >
        {linkContent}
      </a>
      {!isCollapsed && hasChildren && (
        <ul id={`submenu-${item.key}`} className={`flex flex-col gap-1 mt-1 ${open ? '' : 'hidden'}`} role="menu" aria-labelledby={`sidebar-item-${item.key}`}>
          {item.children.map((child) => (
            <li key={child.key}>
              <a
                href={child.url}
                className={`flex items-center gap-2 rounded-lg pl-10 pr-3 py-2 text-xs font-semibold hover:opacity-90 ${child.active ? 'ring-1' : ''}`}
                style={{ color: 'var(--hr-text-main)', borderColor: 'var(--hr-accent)' }}
                role="menuitem"
              >
                <span className="h-1.5 w-1.5 rounded-full" style={{ background: 'var(--hr-accent)' }} />
                <span className="truncate">{child.label}</span>
              </a>
            </li>
          ))}
        </ul>
      )}
      {isCollapsed && (
        <SidebarTooltip anchorRect={anchorRect} label={item.label} visible={hover} />
      )}
    </li>
  );
}
