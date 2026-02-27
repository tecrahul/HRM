import React, { useEffect, useMemo, useState } from 'react';
import SidebarItem from './SidebarItem';
import SidebarFlyout from './SidebarFlyout';

export default function Sidebar({ payload }) {
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [openSections, setOpenSections] = useState({});
  const [flyout, setFlyout] = useState({ open: false, anchorRect: null, items: [], parentKey: null, anchorEl: null, anchorLabelId: null });

  const brand = payload?.brand || {};
  const items = payload?.items || [];

  useEffect(() => {
    const shell = document.getElementById('hrmModernShell');
    if (!shell) return;
    const update = () => setIsCollapsed(shell.classList.contains('is-collapsed'));
    update();
    const observer = new MutationObserver(update);
    observer.observe(shell, { attributes: true, attributeFilter: ['class'] });
    return () => observer.disconnect();
  }, []);

  const topItems = useMemo(() => items.filter((i) => !i.section), [items]);
  const sections = useMemo(() => {
    const map = {};
    for (const it of items) {
      if (it.section) {
        if (!map[it.section]) map[it.section] = [];
        map[it.section].push(it);
      }
    }
    return map;
  }, [items]);

  // Initialize open sections: open the one that has an active item
  useEffect(() => {
    const next = {};
    let anyActive = false;
    Object.entries(sections).forEach(([section, arr]) => {
      const hasActive = arr.some((i) => i.active || (Array.isArray(i.children) && i.children.some((c) => c.active)));
      if (hasActive) { next[section] = true; anyActive = true; }
    });
    // If none active, open the first section
    if (!anyActive) {
      const first = Object.keys(sections)[0];
      if (first) next[first] = true;
    }
    setOpenSections(next);
  }, [sections]);

  const openFlyout = (item, anchorRect, anchorEl = null, anchorLabelId = null) => {
    if (!item.children || item.children.length === 0) return;
    setFlyout({ open: true, anchorRect, items: item.children, parentKey: item.key, anchorEl, anchorLabelId });
  };
  const closeFlyout = () => setFlyout({ open: false, anchorRect: null, items: [], parentKey: null, anchorEl: null, anchorLabelId: null });

  useEffect(() => {
    if (!isCollapsed) closeFlyout();
  }, [isCollapsed]);

  return (
    <div className="flex flex-col gap-4 h-full">
      <div className="w-full pt-2 px-1">
        <div className="flex flex-col items-center justify-center">
          {brand.logo ? (
            isCollapsed ? (
              <img src={brand.logo} alt={`${brand.name} logo`} className="h-8 w-8 object-contain" />
            ) : (
              <img
                src={brand.logo}
                alt={`${brand.name} logo`}
                style={{ width: '12rem', height: '3rem', objectFit: 'contain' }}
              />
            )
          ) : (
            <span className="h-8 w-8 rounded-lg grid place-items-center" style={{ background: 'var(--hr-accent-soft)' }}>
              <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="7" height="7" rx="2"></rect><rect x="14" y="3" width="7" height="7" rx="2"></rect><rect x="3" y="14" width="7" height="7" rx="2"></rect><rect x="14" y="14" width="7" height="7" rx="2"></rect></svg>
            </span>
          )}
          {!isCollapsed && (
            <div className="mt-2 text-center">
              <p className="text-[11px] uppercase tracking-[0.16em] font-bold" style={{ color: 'var(--hr-text-muted)' }}>HR Suite</p>
              <h1 className="text-sm font-extrabold tracking-tight">{brand.name}</h1>
              {brand.tagline && <p className="text-xs mt-1" style={{ color: 'var(--hr-text-muted)' }}>{brand.tagline}</p>}
            </div>
          )}
        </div>
      </div>

      <nav className="flex-1 min-h-0">
        <div className="hrm-sidebar-scroll pr-1" style={{ maxHeight: 'calc(100vh - 220px)' }}>
          <ul className="flex flex-col gap-1">
            {topItems.map((it) => (
              <SidebarItem
                key={it.key}
                item={it}
                isCollapsed={isCollapsed}
                onOpenFlyout={openFlyout}
                isFlyoutOpenForKey={flyout.open && flyout.parentKey === it.key}
                level={1}
              />
            ))}
          </ul>

          {Object.keys(sections).map((section) => (
            <div key={section} className="mt-4">
              {!isCollapsed && (
                <button type="button" className="w-full flex items-center justify-between px-2 py-1 mb-1.5 text-[10px] font-bold uppercase tracking-[0.16em]"
                        style={{ color: 'var(--hr-text-muted)' }}
                        onClick={() => {
                          // Only one major group expanded at a time
                          setOpenSections((prev) => {
                            const next = {};
                            Object.keys(prev).forEach((k) => { next[k] = false; });
                            next[section] = !prev[section];
                            return next;
                          });
                        }}
                >
                  <span>{section}</span>
                  <svg className={`h-3 w-3 transition-transform ${openSections[section] ? 'rotate-180' : ''}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="m6 9 6 6 6-6"></path></svg>
                </button>
              )}
              <ul className={`flex flex-col gap-1 mt-1.5 ${(!isCollapsed && openSections[section] === false) ? 'hidden' : ''}`}>
                {sections[section].map((it) => (
                  <SidebarItem
                    key={it.key}
                    item={it}
                    isCollapsed={isCollapsed}
                    onOpenFlyout={openFlyout}
                    isFlyoutOpenForKey={flyout.open && flyout.parentKey === it.key}
                    level={2}
                  />
                ))}
              </ul>
            </div>
          ))}
        </div>
      </nav>

      <SidebarFlyout
        anchorRect={flyout.anchorRect}
        anchorEl={flyout.anchorEl}
        anchorLabelId={flyout.anchorLabelId}
        items={flyout.items}
        visible={isCollapsed && flyout.open}
        onRequestClose={closeFlyout}
      />
    </div>
  );
}
