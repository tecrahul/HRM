import React, { useCallback, useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import { getGlobalFilters, onFiltersChange } from '../../utils/globalFilters';

// ─── Inject component-scoped CSS once ────────────────────────────────────────
if (typeof document !== 'undefined' && !document.getElementById('emp-dir-styles')) {
  const s = document.createElement('style');
  s.id = 'emp-dir-styles';
  s.textContent = `
    .emp-dir-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 16px;
    }
    @media (min-width: 768px)  { .emp-dir-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1280px) { .emp-dir-grid { grid-template-columns: repeat(4, 1fr); } }

    .emp-dir-card {
      border-radius: 14px;
      border: 1px solid var(--hr-line);
      background: var(--hr-surface-strong);
      padding: 20px;
      cursor: pointer;
      transform: translateY(0);
      box-shadow: 0 16px 32px -22px rgb(2 8 23 / 0.88);
      transition: transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
      position: relative;
    }
    .emp-dir-card:hover {
      transform: translateY(-3px);
      border-color: rgb(96 165 250 / 0.6);
      box-shadow: 0 24px 48px -18px rgb(37 99 235 / 0.28), 0 8px 24px -8px rgb(2 8 23 / 0.3);
    }
    .emp-dir-card:hover .emp-dir-card-arrow {
      color: rgb(59 130 246);
    }

    .emp-dir-row:hover {
      background: var(--hr-surface-strong) !important;
    }

    .emp-dir-menu {
      position: absolute;
      top: calc(100% + 4px);
      right: 0;
      z-index: 99;
      min-width: 190px;
      border-radius: 12px;
      border: 1px solid var(--hr-line);
      background: var(--hr-surface);
      box-shadow: 0 20px 48px -12px rgb(2 8 23 / 0.55);
      overflow: hidden;
    }
    .emp-dir-menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 500;
      color: var(--hr-text-main);
      text-decoration: none;
      transition: background 120ms;
    }
    .emp-dir-menu-item:hover {
      background: var(--hr-surface-strong);
    }
    .emp-dir-menu-item.is-danger {
      color: rgb(239 68 68);
    }

    .emp-dir-filter-select {
      height: 38px;
      padding: 0 12px;
      border-radius: 10px;
      border: 1px solid var(--hr-line);
      background: var(--hr-surface-strong);
      color: var(--hr-text-main);
      font-size: 13px;
      cursor: pointer;
      min-width: 130px;
      outline: none;
      transition: border-color 140ms;
    }
    .emp-dir-filter-select:focus {
      border-color: rgb(96 165 250 / 0.7);
    }

    .emp-dir-view-btn {
      width: 38px;
      height: 38px;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 140ms, color 140ms;
    }
    .emp-dir-view-btn.is-active {
      background: linear-gradient(145deg, rgb(37 99 235 / 0.75), rgb(56 189 248 / 0.52));
      color: #ecfeff;
    }
    .emp-dir-view-btn:not(.is-active) {
      background: var(--hr-surface-strong);
      color: var(--hr-text-muted);
    }

    .emp-dir-sort-th {
      padding: 11px 14px;
      font-weight: 700;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      white-space: nowrap;
      cursor: pointer;
      user-select: none;
      border-bottom: 2px solid var(--hr-line);
      background: transparent;
      color: var(--hr-text-muted);
      transition: color 120ms;
      text-align: left;
    }
    .emp-dir-sort-th:hover,
    .emp-dir-sort-th.is-active {
      color: var(--hr-accent, #2563eb);
    }

    .emp-dir-skeleton {
      background: var(--hr-line);
      border-radius: 6px;
      animation: emp-dir-pulse 1.4s ease-in-out infinite;
    }
    @keyframes emp-dir-pulse {
      0%, 100% { opacity: 0.65; }
      50%       { opacity: 0.35; }
    }
    @keyframes emp-dir-spin {
      to { transform: rotate(360deg); }
    }
  `;
  document.head.appendChild(s);
}

// ─── Utilities ───────────────────────────────────────────────────────────────

function useDebouncedValue(value, delay = 300) {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(t);
  }, [value, delay]);
  return debounced;
}

/**
 * Deterministic presence derived from employee ID.
 * Stable across renders: same employee always gets same status.
 * ~28% online · ~20% away · ~52% offline
 */
function getPresence(id) {
  const h = ((id * 2654435761) >>> 0) % 100;
  if (h < 28) return 'online';
  if (h < 48) return 'away';
  return 'offline';
}

function fmtDate(str) {
  if (!str) return '—';
  try {
    return new Date(str).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  } catch {
    return str;
  }
}

// ─── Shared atoms ────────────────────────────────────────────────────────────

function PresenceDot({ status }) {
  const color = status === 'online' ? '#22c55e' : status === 'away' ? '#eab308' : '#94a3b8';
  return (
    <span
      title={status}
      style={{
        position: 'absolute',
        bottom: 1,
        right: 1,
        width: 11,
        height: 11,
        borderRadius: '50%',
        background: color,
        border: '2px solid var(--hr-surface-strong)',
        boxSizing: 'border-box',
        display: 'block',
      }}
    />
  );
}

function Avatar({ src, name, size = 44, presence }) {
  return (
    <div style={{ position: 'relative', width: size, height: size, flexShrink: 0 }}>
      <img
        src={src}
        alt={`${name} avatar`}
        loading="lazy"
        width={size}
        height={size}
        style={{ width: size, height: size, borderRadius: '50%', objectFit: 'cover', border: '2px solid var(--hr-line)', display: 'block' }}
        onError={(e) => { e.currentTarget.src = '/images/user-avatar.svg'; }}
      />
      <PresenceDot status={presence} />
    </div>
  );
}

function StatusBadge({ status }) {
  const s = (status || 'active').toLowerCase();
  let cls = 'emp-status border ';
  if (s === 'inactive') cls += 'text-amber-600 border-amber-500/50 bg-amber-500/15';
  else if (s === 'suspended') cls += 'text-red-600 border-red-500/50 bg-red-500/15';
  else cls += 'text-emerald-600 border-emerald-500/50 bg-emerald-500/15';
  return <span className={cls}>{s.replace(/_/g, ' ').toUpperCase()}</span>;
}

function Spinner() {
  return (
    <svg
      width="16" height="16" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" strokeWidth="2"
      style={{ animation: 'emp-dir-spin 1s linear infinite', display: 'block' }}
    >
      <circle cx="12" cy="12" r="10" strokeOpacity="0.2" />
      <path d="M12 2a10 10 0 0 1 10 10" />
    </svg>
  );
}

// ─── Action menu ─────────────────────────────────────────────────────────────

const ALL_MENU_ACTIONS = [
  { label: 'View Profile',    href: (id) => `/employees/${id}/overview`,   icon: <UserIcon />,  permission: null },
  { label: 'Edit Employee',   href: (id) => `/admin/users/${id}/edit`,     icon: <EditIcon />,  permission: 'canEditEmployee' },
  { label: 'View Attendance', href: (id) => `/employees/${id}/attendance`, icon: <CalIcon />,   permission: 'canViewAttendance' },
  { label: 'View Payroll',    href: (id) => `/employees/${id}/payroll`,    icon: <MoneyIcon />, permission: 'canViewPayroll' },
];

function ActionMenu({ employeeId, permissions, onClose }) {
  const ref = useRef(null);
  useEffect(() => {
    const fn = (e) => { if (ref.current && !ref.current.contains(e.target)) onClose(); };
    document.addEventListener('mousedown', fn);
    return () => document.removeEventListener('mousedown', fn);
  }, [onClose]);

  const visibleActions = ALL_MENU_ACTIONS.filter(
    (a) => a.permission === null || permissions[a.permission],
  );

  return (
    <div ref={ref} className="emp-dir-menu">
      {visibleActions.map((a) => (
        <a
          key={a.label}
          href={a.href(employeeId)}
          className="emp-dir-menu-item"
          onClick={(e) => e.stopPropagation()}
        >
          <span style={{ opacity: 0.65, display: 'flex' }}>{a.icon}</span>
          {a.label}
        </a>
      ))}
    </div>
  );
}

// ─── Icon components (inline SVG, no deps) ───────────────────────────────────

function UserIcon() {
  return <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a8.5 8.5 0 0 1 13 0"/></svg>;
}
function EditIcon() {
  return <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>;
}
function CalIcon() {
  return <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>;
}
function MoneyIcon() {
  return <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2M12 12v4M10 14h4"/></svg>;
}
function SearchIcon() {
  return <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>;
}
function CloseIcon() {
  return <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>;
}
function GridIcon() {
  return <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>;
}
function ListIcon() {
  return <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>;
}
function ArrowRightIcon() {
  return <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>;
}
function BuildingIcon() {
  return <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>;
}
function MailIcon() {
  return <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>;
}

// ─── Employee Card (Grid view) ────────────────────────────────────────────────

function EmployeeCard({ e, permissions }) {
  const [menuOpen, setMenuOpen] = useState(false);
  const presence = getPresence(e.id);

  return (
    <article
      className="emp-dir-card"
      onClick={() => { window.location.href = `/employees/${e.id}/overview`; }}
      role="button"
      tabIndex={0}
      onKeyDown={(ev) => { if (ev.key === 'Enter') window.location.href = `/employees/${e.id}/overview`; }}
    >
      {/* ── Top ── */}
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 10 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: 0, flex: 1 }}>
          <Avatar src={e.avatar} name={e.full_name} size={48} presence={presence} />
          <div style={{ minWidth: 0 }}>
            <p style={{ fontSize: 14, fontWeight: 800, lineHeight: 1.3, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
              {e.full_name}
            </p>
            <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--hr-text-muted)', marginTop: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
              {e.designation || 'Employee'}
            </p>
            <p style={{ fontSize: 11, color: 'var(--hr-text-muted)', marginTop: 2, fontFamily: 'ui-monospace, monospace', letterSpacing: '0.04em', opacity: 0.8 }}>
              {e.employee_id}
            </p>
          </div>
        </div>

        {/* Three-dot menu */}
        <div style={{ position: 'relative', flexShrink: 0 }} onClick={(ev) => ev.stopPropagation()}>
          <button
            type="button"
            onClick={() => setMenuOpen((o) => !o)}
            aria-label="Employee actions"
            aria-expanded={menuOpen}
            style={{
              width: 30, height: 30,
              borderRadius: 8,
              border: '1px solid var(--hr-line)',
              background: menuOpen ? 'var(--hr-surface-strong)' : 'transparent',
              color: 'var(--hr-text-muted)',
              cursor: 'pointer',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              fontSize: 18, lineHeight: 1,
            }}
          >
            ⋯
          </button>
          {menuOpen && <ActionMenu employeeId={e.id} permissions={permissions} onClose={() => setMenuOpen(false)} />}
        </div>
      </div>

      {/* ── Middle info ── */}
      <div style={{ marginTop: 14, display: 'flex', flexDirection: 'column', gap: 6 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 7, fontSize: 12, color: 'var(--hr-text-muted)' }}>
          <span style={{ display: 'flex', flexShrink: 0 }}><BuildingIcon /></span>
          <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
            {e.department || '—'}{e.branch ? ` • ${e.branch}` : ''}
          </span>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 7, fontSize: 12, color: 'var(--hr-text-muted)' }}>
          <span style={{ display: 'flex', flexShrink: 0 }}><CalIcon /></span>
          <span>Joined {fmtDate(e.joined_date)}</span>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 7, fontSize: 12, color: 'var(--hr-text-muted)' }}>
          <span style={{ display: 'flex', flexShrink: 0 }}><MailIcon /></span>
          <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={e.email}>{e.email}</span>
        </div>
      </div>

      {/* ── Bottom ── */}
      <div style={{ marginTop: 16, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <StatusBadge status={e.status} />
        <span
          className="emp-dir-card-arrow"
          style={{ fontSize: 12, fontWeight: 700, color: 'var(--hr-text-muted)', display: 'flex', alignItems: 'center', gap: 4, transition: 'color 160ms' }}
        >
          View Profile <ArrowRightIcon />
        </span>
      </div>
    </article>
  );
}

// ─── Skeleton card ────────────────────────────────────────────────────────────

function SkeletonCard() {
  const sk = { className: 'emp-dir-skeleton' };
  return (
    <div style={{ borderRadius: 14, border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)', padding: 20 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
        <div {...sk} style={{ width: 48, height: 48, borderRadius: '50%', flexShrink: 0 }} />
        <div style={{ flex: 1 }}>
          <div {...sk} style={{ height: 12, width: '58%', marginBottom: 8, borderRadius: 4 }} />
          <div {...sk} style={{ height: 10, width: '40%', borderRadius: 4 }} />
        </div>
      </div>
      <div style={{ marginTop: 14 }}>
        <div {...sk} style={{ height: 10, width: '80%', marginBottom: 7, borderRadius: 4 }} />
        <div {...sk} style={{ height: 10, width: '62%', marginBottom: 7, borderRadius: 4 }} />
        <div {...sk} style={{ height: 10, width: '72%', borderRadius: 4 }} />
      </div>
      <div style={{ marginTop: 16, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div {...sk} style={{ height: 20, width: 64, borderRadius: 999 }} />
        <div {...sk} style={{ height: 10, width: 80, borderRadius: 4 }} />
      </div>
    </div>
  );
}

// ─── Employee row (List view) ─────────────────────────────────────────────────

function EmployeeRow({ e, permissions }) {
  const [menuOpen, setMenuOpen] = useState(false);
  const presence = getPresence(e.id);
  const tdStyle = { padding: '12px 14px', fontSize: 13, verticalAlign: 'middle' };

  return (
    <tr
      className="emp-dir-row"
      style={{ borderBottom: '1px solid var(--hr-line)', cursor: 'pointer', transition: 'background 120ms' }}
      onClick={() => { window.location.href = `/employees/${e.id}/overview`; }}
    >
      <td style={tdStyle}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <Avatar src={e.avatar} name={e.full_name} size={36} presence={presence} />
          <div>
            <p style={{ fontSize: 13, fontWeight: 700, lineHeight: 1.3, whiteSpace: 'nowrap' }}>{e.full_name}</p>
            <p style={{ fontSize: 11, color: 'var(--hr-text-muted)', marginTop: 1 }}>{e.designation || 'Employee'}</p>
          </div>
        </div>
      </td>
      <td style={{ ...tdStyle, fontFamily: 'ui-monospace, monospace', fontSize: 12, color: 'var(--hr-text-muted)', whiteSpace: 'nowrap' }}>{e.employee_id}</td>
      <td style={{ ...tdStyle, whiteSpace: 'nowrap' }}>{e.designation || '—'}</td>
      <td style={tdStyle}>{e.department || '—'}</td>
      <td style={tdStyle}>{e.branch || '—'}</td>
      <td style={{ ...tdStyle, whiteSpace: 'nowrap' }}>{fmtDate(e.joined_date)}</td>
      <td style={tdStyle}><StatusBadge status={e.status} /></td>
      <td style={{ ...tdStyle, textAlign: 'right' }} onClick={(ev) => ev.stopPropagation()}>
        <div style={{ position: 'relative', display: 'inline-block' }}>
          <button
            type="button"
            onClick={() => setMenuOpen((o) => !o)}
            aria-label="Employee actions"
            style={{ padding: '4px 10px', borderRadius: 8, border: '1px solid var(--hr-line)', background: 'transparent', cursor: 'pointer', fontSize: 17, color: 'var(--hr-text-muted)', lineHeight: 1 }}
          >⋯</button>
          {menuOpen && <ActionMenu employeeId={e.id} permissions={permissions} onClose={() => setMenuOpen(false)} />}
        </div>
      </td>
    </tr>
  );
}

// ─── Skeleton row ─────────────────────────────────────────────────────────────

function SkeletonRow() {
  const sk = { className: 'emp-dir-skeleton' };
  return (
    <tr style={{ borderBottom: '1px solid var(--hr-line)' }}>
      <td style={{ padding: '14px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <div {...sk} style={{ width: 36, height: 36, borderRadius: '50%', flexShrink: 0 }} />
          <div>
            <div {...sk} style={{ height: 11, width: 120, marginBottom: 6, borderRadius: 4 }} />
            <div {...sk} style={{ height: 9, width: 80, borderRadius: 4 }} />
          </div>
        </div>
      </td>
      {[90, 100, 100, 80, 80, 56].map((w, i) => (
        <td key={i} style={{ padding: '14px' }}>
          <div {...sk} style={{ height: 10, width: w, borderRadius: 4 }} />
        </td>
      ))}
      <td style={{ padding: '14px', textAlign: 'right' }}>
        <div {...sk} style={{ height: 28, width: 40, borderRadius: 8, marginLeft: 'auto' }} />
      </td>
    </tr>
  );
}

// ─── Sort-able table header ───────────────────────────────────────────────────

function SortTh({ col, label, sortBy, sortDir, onSort, align = 'left' }) {
  const active = sortBy === col;
  return (
    <th
      className={`emp-dir-sort-th${active ? ' is-active' : ''}`}
      onClick={() => onSort(col)}
      style={{ textAlign: align }}
    >
      {label}{active ? (sortDir === 'asc' ? ' ↑' : ' ↓') : ''}
    </th>
  );
}

// ─── Filter select ────────────────────────────────────────────────────────────

function FilterSelect({ value, onChange, options, placeholder }) {
  return (
    <select
      className="emp-dir-filter-select"
      value={value}
      onChange={(e) => onChange(e.target.value)}
    >
      <option value="">{placeholder}</option>
      {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
    </select>
  );
}

// ─── Main component ───────────────────────────────────────────────────────────

export function EmployeeDirectory({ apiUrl = '/api/employees', permissions = {} }) {
  const qs = new URLSearchParams(window.location.search);

  const [employees, setEmployees]       = useState([]);
  const [loading, setLoading]           = useState(false);
  const [initialLoading, setInitial]    = useState(true);
  const [error, setError]               = useState('');
  const [search, setSearch]             = useState(qs.get('search') || '');
  const [department, setDepartment]     = useState(qs.get('department') || getGlobalFilters().department || '');
  const [branch, setBranch]             = useState(qs.get('branch') || getGlobalFilters().branch || '');
  const [status, setStatus]             = useState(qs.get('status') || '');
  const [designation, setDesignation]   = useState(qs.get('designation_id') || '');
  const [sortBy, setSortBy]             = useState(qs.get('sort_by') || 'id');
  const [sortDir, setSortDir]           = useState(qs.get('sort_direction') || 'desc');
  const [page, setPage]                 = useState(parseInt(qs.get('page') || '1', 10));
  const [perPage]                       = useState(20);
  const [total, setTotal]               = useState(0);
  const [lastPage, setLastPage]         = useState(1);
  const [options, setOptions]           = useState({
    departments: [], branches: [], designations: [],
    statuses: ['active', 'inactive', 'suspended'],
  });
  const [viewMode, setViewMode]         = useState(() => localStorage.getItem('emp-directory-view') || 'grid');

  const searchRef      = useRef(null);
  const debouncedSearch = useDebouncedValue(search, 300);

  // Sync global header filters (branch / department)
  useEffect(() => {
    return onFiltersChange((f) => {
      if (f.branch !== undefined)     { setBranch(f.branch || ''); }
      if (f.department !== undefined) { setDepartment(f.department || ''); }
      setPage(1);
    });
  }, []);

  // Fetch employees whenever any filter/sort/page changes
  const fetchEmployees = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await axios.get(apiUrl, {
        params: {
          search:        debouncedSearch  || undefined,
          department:    department       || undefined,
          branch:        branch           || undefined,
          status:        status           || undefined,
          designation_id: designation     || undefined,
          sort_by:       sortBy,
          sort_direction: sortDir,
          page,
          per_page:      perPage,
        },
      });
      setEmployees(data.data || []);
      setTotal(data.meta?.total || 0);
      setLastPage(data.meta?.last_page || 1);
      if (data.meta?.options) setOptions(data.meta.options);
    } catch {
      setError('Failed to load employees. Please try again.');
    } finally {
      setLoading(false);
      setInitial(false);
    }
  }, [apiUrl, debouncedSearch, department, branch, status, designation, sortBy, sortDir, page, perPage]);

  useEffect(() => { fetchEmployees(); }, [fetchEmployees]);

  // Keep URL in sync (no page reload)
  useEffect(() => {
    const usp = new URLSearchParams();
    if (debouncedSearch) usp.set('search', debouncedSearch);
    if (department)      usp.set('department', department);
    if (branch)          usp.set('branch', branch);
    if (status)          usp.set('status', status);
    if (designation)     usp.set('designation_id', designation);
    if (sortBy !== 'id') usp.set('sort_by', sortBy);
    if (sortDir !== 'desc') usp.set('sort_direction', sortDir);
    if (page > 1)        usp.set('page', String(page));
    const str = usp.toString();
    window.history.replaceState(null, '', `${window.location.pathname}${str ? `?${str}` : ''}`);
  }, [debouncedSearch, department, branch, status, designation, sortBy, sortDir, page]);

  // Keyboard shortcut: press '/' to focus search
  useEffect(() => {
    const fn = (e) => {
      const tag = document.activeElement?.tagName;
      if (e.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
        e.preventDefault();
        searchRef.current?.focus();
      }
    };
    window.addEventListener('keydown', fn);
    return () => window.removeEventListener('keydown', fn);
  }, []);

  const handleSort = (col) => {
    if (sortBy === col) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    else { setSortBy(col); setSortDir('asc'); }
    setPage(1);
  };

  const switchView = (mode) => {
    setViewMode(mode);
    localStorage.setItem('emp-directory-view', mode);
  };

  const clearFilters = () => {
    setSearch(''); setDepartment('');
    setStatus(''); setDesignation(''); setPage(1);
  };

  const hasFilters = !!(search || department || status || designation);

  const deptOptions  = options.departments.map((d) => ({ value: d.name, label: d.name }));
  const branchOpts   = options.branches.map((b) => ({ value: b.name, label: b.name }));
  const statusOpts   = options.statuses.map((s) => ({ value: s, label: s.charAt(0).toUpperCase() + s.slice(1) }));
  const desigOpts    = options.designations.map((d) => ({ value: String(d.id), label: d.name }));

  // ── Render ──────────────────────────────────────────────────────────────────
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>

      {/* ━━━ Header / Filters ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */}
      <section style={{
        borderRadius: 16, border: '1px solid var(--hr-line)',
        background: 'var(--hr-surface)', boxShadow: 'var(--hr-shadow-soft)',
        padding: '20px 24px',
      }}>

        {/* Search */}
        <div style={{ position: 'relative' }}>
          <span style={{ position: 'absolute', left: 14, top: '50%', transform: 'translateY(-50%)', color: 'var(--hr-text-muted)', display: 'flex', pointerEvents: 'none' }}>
            <SearchIcon />
          </span>
          <input
            ref={searchRef}
            type="search"
            placeholder="Search employees by name, email, employee ID, department or branch"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            style={{
              width: '100%',
              height: 44,
              borderRadius: 12,
              border: '1px solid var(--hr-line)',
              background: 'var(--hr-surface-strong)',
              paddingLeft: 44,
              paddingRight: 44,
              fontSize: 14,
              color: 'var(--hr-text-main)',
              boxSizing: 'border-box',
              outline: 'none',
            }}
          />
          <span style={{ position: 'absolute', right: 14, top: '50%', transform: 'translateY(-50%)', display: 'flex', color: 'var(--hr-text-muted)' }}>
            {loading ? (
              <Spinner />
            ) : search ? (
              <button
                type="button"
                onClick={() => { setSearch(''); setPage(1); searchRef.current?.focus(); }}
                style={{ background: 'none', border: 'none', cursor: 'pointer', padding: 0, display: 'flex', color: 'var(--hr-text-muted)' }}
                aria-label="Clear search"
              >
                <CloseIcon />
              </button>
            ) : null}
          </span>
        </div>

        {/* Filters row + View toggle */}
        <div style={{ marginTop: 14, display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 10, justifyContent: 'space-between' }}>

          {/* Dropdowns */}
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, alignItems: 'center' }}>
            <FilterSelect value={department} onChange={(v) => { setDepartment(v); setPage(1); }} options={deptOptions}  placeholder="Department ▾" />
            <FilterSelect value={status}     onChange={(v) => { setStatus(v);     setPage(1); }} options={statusOpts}   placeholder="Status ▾" />
            <FilterSelect value={designation} onChange={(v) => { setDesignation(v); setPage(1); }} options={desigOpts}  placeholder="Designation ▾" />
            {hasFilters && (
              <button
                type="button"
                onClick={clearFilters}
                style={{ height: 38, padding: '0 14px', borderRadius: 10, border: '1px solid var(--hr-line)', background: 'transparent', cursor: 'pointer', fontSize: 13, color: 'var(--hr-text-muted)', whiteSpace: 'nowrap' }}
              >
                × Clear filters
              </button>
            )}
          </div>

          {/* Grid / List toggle */}
          <div style={{ display: 'flex', borderRadius: 10, border: '1px solid var(--hr-line)', overflow: 'hidden', flexShrink: 0 }}>
            <button
              type="button"
              className={`emp-dir-view-btn${viewMode === 'grid' ? ' is-active' : ''}`}
              onClick={() => switchView('grid')}
              aria-label="Grid view"
              style={{ borderRight: '1px solid var(--hr-line)' }}
            >
              <GridIcon />
            </button>
            <button
              type="button"
              className={`emp-dir-view-btn${viewMode === 'list' ? ' is-active' : ''}`}
              onClick={() => switchView('list')}
              aria-label="List view"
            >
              <ListIcon />
            </button>
          </div>
        </div>
      </section>

      {/* ━━━ Directory ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */}
      <section style={{
        borderRadius: 16, border: '1px solid var(--hr-line)',
        background: 'var(--hr-surface)', boxShadow: 'var(--hr-shadow-soft)',
        padding: '24px',
      }}>

        {/* Section heading */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap', marginBottom: 24 }}>
          <div>
            <h2 style={{ fontSize: 18, fontWeight: 800, margin: 0 }}>Employee Directory</h2>
            <p style={{ fontSize: 13, color: 'var(--hr-text-muted)', marginTop: 4 }}>
              {initialLoading
                ? 'Loading employees…'
                : `Showing ${employees.length} of ${new Intl.NumberFormat().format(total)} employee${total !== 1 ? 's' : ''}`}
            </p>
          </div>

          {/* Sort controls (list view) */}
          {viewMode === 'list' && !initialLoading && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <select
                value={sortBy}
                onChange={(e) => { setSortBy(e.target.value); setPage(1); }}
                className="emp-dir-filter-select"
                style={{ minWidth: 140 }}
              >
                <option value="id">Sort: Default</option>
                <option value="full_name">Name</option>
                <option value="joined_date">Join Date</option>
                <option value="department">Department</option>
                <option value="branch">Branch</option>
                <option value="status">Status</option>
              </select>
              <button
                type="button"
                onClick={() => { setSortDir((d) => (d === 'asc' ? 'desc' : 'asc')); setPage(1); }}
                style={{ height: 38, padding: '0 12px', borderRadius: 10, border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)', cursor: 'pointer', fontSize: 13, color: 'var(--hr-text-muted)' }}
                title={sortDir === 'asc' ? 'Ascending' : 'Descending'}
              >
                {sortDir === 'asc' ? '↑ Asc' : '↓ Desc'}
              </button>
            </div>
          )}
        </div>

        {/* ── Loading skeletons ── */}
        {initialLoading ? (
          viewMode === 'grid' ? (
            <div className="emp-dir-grid">
              {Array.from({ length: 8 }).map((_, i) => <SkeletonCard key={i} />)}
            </div>
          ) : (
            <div style={{ overflowX: 'auto' }}>
              <table style={{ width: '100%', minWidth: 900, borderCollapse: 'collapse' }}>
                <tbody>
                  {Array.from({ length: 6 }).map((_, i) => <SkeletonRow key={i} />)}
                </tbody>
              </table>
            </div>
          )

        /* ── Error state ── */
        ) : error ? (
          <div style={{ borderRadius: 12, border: '1px solid var(--hr-line)', padding: '18px 20px', fontSize: 13, color: 'var(--hr-text-muted)', background: 'var(--hr-surface-strong)', display: 'flex', alignItems: 'center', gap: 10 }}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ flexShrink: 0, color: 'rgb(239 68 68)' }}>
              <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
            </svg>
            {error}
            <button type="button" onClick={fetchEmployees} style={{ marginLeft: 'auto', padding: '4px 12px', borderRadius: 8, border: '1px solid var(--hr-line)', background: 'transparent', cursor: 'pointer', fontSize: 12 }}>
              Retry
            </button>
          </div>

        /* ── Empty state ── */
        ) : employees.length === 0 ? (
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '64px 20px', textAlign: 'center' }}>
            <div style={{ width: 80, height: 80, borderRadius: '50%', background: 'var(--hr-surface-strong)', border: '1px solid var(--hr-line)', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: 20 }}>
              <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" style={{ color: 'var(--hr-text-muted)', opacity: 0.5 }}>
                <circle cx="12" cy="7" r="4"/><path d="M5.5 21a8.5 8.5 0 0 1 13 0"/>
              </svg>
            </div>
            <p style={{ fontSize: 17, fontWeight: 800, margin: '0 0 8px' }}>No employees found</p>
            <p style={{ fontSize: 13, color: 'var(--hr-text-muted)', margin: '0 0 24px', maxWidth: 320 }}>
              No results match your current search or filter criteria.
            </p>
            {hasFilters && (
              <button
                type="button"
                onClick={clearFilters}
                style={{ padding: '10px 24px', borderRadius: 10, border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)', cursor: 'pointer', fontSize: 13, fontWeight: 700 }}
              >
                Clear Filters
              </button>
            )}
          </div>

        /* ── Grid view ── */
        ) : viewMode === 'grid' ? (
          <div className="emp-dir-grid">
            {employees.map((e) => <EmployeeCard key={e.id} e={e} permissions={permissions} />)}
          </div>

        /* ── List view ── */
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', minWidth: 920, borderCollapse: 'collapse', fontSize: 13 }}>
              <thead>
                <tr>
                  <SortTh col="full_name"   label="Employee"    sortBy={sortBy} sortDir={sortDir} onSort={handleSort} />
                  <th className="emp-dir-sort-th" style={{ cursor: 'default' }}>Emp ID</th>
                  <SortTh col="designation" label="Designation" sortBy={sortBy} sortDir={sortDir} onSort={handleSort} />
                  <SortTh col="department"  label="Department"  sortBy={sortBy} sortDir={sortDir} onSort={handleSort} />
                  <SortTh col="branch"      label="Branch"      sortBy={sortBy} sortDir={sortDir} onSort={handleSort} />
                  <SortTh col="joined_date" label="Joined"      sortBy={sortBy} sortDir={sortDir} onSort={handleSort} />
                  <SortTh col="status"      label="Status"      sortBy={sortBy} sortDir={sortDir} onSort={handleSort} />
                  <th className="emp-dir-sort-th" style={{ cursor: 'default', textAlign: 'right' }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {employees.map((e) => <EmployeeRow key={e.id} e={e} permissions={permissions} />)}
              </tbody>
            </table>
          </div>
        )}

        {/* ── Pagination ── */}
        {!initialLoading && employees.length > 0 && (
          <div style={{ marginTop: 24, display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 10, paddingTop: 20, borderTop: '1px solid var(--hr-line)' }}>
            <p style={{ fontSize: 12, color: 'var(--hr-text-muted)', margin: 0 }}>
              Page {page} of {lastPage} &nbsp;·&nbsp; {new Intl.NumberFormat().format(total)} total employee{total !== 1 ? 's' : ''}
            </p>
            <div style={{ display: 'flex', gap: 6 }}>
              {[
                { label: '«', action: () => setPage(1),                          disabled: page <= 1 },
                { label: 'Previous', action: () => setPage((p) => Math.max(1, p - 1)), disabled: page <= 1 },
                { label: 'Next',     action: () => setPage((p) => Math.min(lastPage, p + 1)), disabled: page >= lastPage },
                { label: '»', action: () => setPage(lastPage),                   disabled: page >= lastPage },
              ].map(({ label, action, disabled }) => (
                <button
                  key={label}
                  type="button"
                  disabled={loading || disabled}
                  onClick={action}
                  style={{
                    padding: '6px 12px',
                    borderRadius: 8,
                    border: '1px solid var(--hr-line)',
                    background: 'var(--hr-surface-strong)',
                    cursor: disabled ? 'not-allowed' : 'pointer',
                    fontSize: 12,
                    fontWeight: 600,
                    opacity: disabled ? 0.4 : 1,
                    color: 'var(--hr-text-main)',
                    transition: 'opacity 120ms',
                  }}
                >
                  {label}
                </button>
              ))}
            </div>
          </div>
        )}
      </section>
    </div>
  );
}

// ─── Mount ───────────────────────────────────────────────────────────────────

export function mountEmployeeDirectory() {
  const root = document.getElementById('employee-directory-root');
  if (!root) return;
  const apiUrl = root.dataset.apiUrl || '/api/employees';
  let permissions = {};
  try {
    permissions = root.dataset.permissions ? JSON.parse(root.dataset.permissions) : {};
  } catch (_e) {}
  createRoot(root).render(<EmployeeDirectory apiUrl={apiUrl} permissions={permissions} />);
}
