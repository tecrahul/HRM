import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

const useDebouncedValue = (value, delay = 400) => {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(t);
  }, [value, delay]);
  return debounced;
};

const Spinner = ({ className = 'h-4 w-4' }) => (
  <svg className={`${className} animate-spin`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
    <circle cx="12" cy="12" r="10" strokeOpacity="0.25" />
    <path d="M12 2a10 10 0 0 1 10 10" />
  </svg>
);

const Chip = ({ label, onClear }) => (
  <span className="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-xs" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
    <span>{label}</span>
    <button type="button" onClick={onClear} className="rounded p-0.5 hover:bg-black/5 dark:hover:bg-white/10" aria-label={`Remove ${label}`}>×</button>
  </span>
);

const skeletonPulse = { animation: 'pulse 1.2s ease-in-out infinite', opacity: 0.6 };

function EmployeeCard({ e }) {
  const statusClass = e.status === 'inactive' ? 'text-amber-700 bg-amber-500/20 border-amber-600/50'
    : e.status === 'suspended' ? 'text-red-700 bg-red-500/20 border-red-600/50'
    : 'text-emerald-700 bg-emerald-500/20 border-emerald-600/50';
  return (
    <article className="rounded-2xl border p-4 transition-all" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)', boxShadow: '0 18px 36px -28px rgb(2 8 23 / 0.88)' }}>
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-3">
          <img src={e.avatar} alt={`${e.full_name} avatar`} className="h-12 w-12 rounded-full object-cover border" style={{ borderColor: 'var(--hr-line)' }} />
          <div>
            <p className="text-sm font-extrabold">{e.full_name}</p>
            <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>{e.designation || 'Employee'}</p>
          </div>
        </div>
        <div>
          <button type="button" className="rounded-lg border px-2 py-1 text-xs" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }} aria-label="Quick actions">⋯</button>
        </div>
      </div>
      <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
        <div><span className="font-semibold">Emp ID:</span> <span>{e.employee_id}</span></div>
        <div><span className="font-semibold">Joined:</span> <span>{e.joined_date || '—'}</span></div>
        <div className="col-span-2"><span className="font-semibold">Dept • Branch:</span> <span>{e.department || '—'}{e.branch ? ` • ${e.branch}` : ''}</span></div>
        <div className="col-span-2 truncate"><span className="font-semibold">Email:</span> <span title={e.email}>{e.email}</span></div>
      </div>
      <div className="mt-3 flex items-center justify-between">
        <span className={`emp-status border ${statusClass}`}>{(e.status || 'active').replace('_', ' ').toUpperCase()}</span>
        <a href={`/employees/${e.id}/overview`} className="rounded-lg border px-2 py-1 text-xs font-semibold" style={{ borderColor: 'var(--hr-line)' }}>View</a>
      </div>
    </article>
  );
}

function EmployeeRow({ e }) {
  const statusClass = e.status === 'inactive' ? 'text-amber-700 bg-amber-500/20 border-amber-600/50'
    : e.status === 'suspended' ? 'text-red-700 bg-red-500/20 border-red-600/50'
    : 'text-emerald-700 bg-emerald-500/20 border-emerald-600/50';
  return (
    <tr className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
      <td className="py-3 px-3">
        <div className="flex items-center gap-3">
          <img src={e.avatar} alt="avatar" className="h-9 w-9 rounded-full object-cover border" style={{ borderColor: 'var(--hr-line)' }} />
          <div>
            <p className="text-sm font-semibold">{e.full_name}</p>
            <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{e.designation || 'Employee'}</p>
          </div>
        </div>
      </td>
      <td className="py-3 px-3"><span className={`emp-status border ${statusClass}`}>{(e.status || 'active').replace('_', ' ').toUpperCase()}</span></td>
      <td className="py-3 px-3"><p className="font-semibold">{e.department || '—'}</p><p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{e.branch || '—'}</p></td>
      <td className="py-3 px-3">{e.joined_date || '—'}</td>
      <td className="py-3 px-3">{e.email}</td>
      <td className="py-3 px-3 text-right"><a href={`/employees/${e.id}/overview`} className="rounded-lg border px-2 py-1 text-xs font-semibold" style={{ borderColor: 'var(--hr-line)' }}>View</a></td>
    </tr>
  );
}

export function EmployeeDirectory({ apiUrl = '/api/employees' }) {
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState(new URLSearchParams(window.location.search).get('search') || '');
  const [department, setDepartment] = useState(new URLSearchParams(window.location.search).get('department_id') || '');
  const [branch, setBranch] = useState(new URLSearchParams(window.location.search).get('branch_id') || '');
  const [status, setStatus] = useState(new URLSearchParams(window.location.search).get('status') || '');
  const [designation, setDesignation] = useState(new URLSearchParams(window.location.search).get('designation_id') || '');
  const [sortBy, setSortBy] = useState(new URLSearchParams(window.location.search).get('sort_by') || 'id');
  const [sortDirection, setSortDirection] = useState(new URLSearchParams(window.location.search).get('sort_direction') || 'desc');
  const [page, setPage] = useState(parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10));
  const [perPage, setPerPage] = useState(parseInt(new URLSearchParams(window.location.search).get('per_page') || '12', 10));
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [options, setOptions] = useState({ departments: [], branches: [], designations: [], statuses: ['active', 'inactive', 'suspended'] });
  const [viewMode, setViewMode] = useState(() => localStorage.getItem('emp-directory-view') || 'grid');

  const searchRef = useRef(null);
  const debouncedSearch = useDebouncedValue(search, 400);

  const updateUrl = useCallback((params) => {
    const usp = new URLSearchParams();
    if (debouncedSearch) usp.set('search', debouncedSearch);
    if (department) usp.set('department_id', department);
    if (branch) usp.set('branch_id', branch);
    if (status) usp.set('status', status);
    if (designation) usp.set('designation_id', designation);
    if (sortBy) usp.set('sort_by', sortBy);
    if (sortDirection) usp.set('sort_direction', sortDirection);
    usp.set('page', String(page));
    usp.set('per_page', String(perPage));
    window.history.replaceState(null, '', `${window.location.pathname}?${usp.toString()}`);
  }, [debouncedSearch, department, branch, status, designation, sortBy, sortDirection, page, perPage]);

  const fetchEmployees = useCallback(async (opts = {}) => {
    setLoading(true);
    setError('');
    try {
      const { data } = await axios.get(apiUrl, {
        params: {
          search: debouncedSearch,
          department_id: department || undefined,
          branch_id: branch || undefined,
          status: status || undefined,
          designation_id: designation || undefined,
          sort_by: sortBy,
          sort_direction: sortDirection,
          page,
          per_page: perPage,
          ...opts,
        },
      });
      setEmployees(data.data || []);
      setTotal(data.meta?.total || 0);
      setLastPage(data.meta?.last_page || 1);
      if (data.meta?.options) setOptions(data.meta.options);
    } catch (_e) {
      setError('Failed to load employees.');
    } finally {
      setLoading(false);
      setInitialLoading(false);
    }
  }, [apiUrl, debouncedSearch, department, branch, status, designation, sortBy, sortDirection, page, perPage]);

  // Load on filters change
  useEffect(() => {
    fetchEmployees();
  }, [fetchEmployees]);

  // Keep URL in sync
  useEffect(() => { updateUrl(); }, [updateUrl]);

  // Keyboard: focus search on '/'
  useEffect(() => {
    const handler = (e) => {
      if (e.key === '/' && !e.metaKey && !e.ctrlKey && !e.altKey) {
        e.preventDefault();
        searchRef.current?.focus();
      }
    };
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, []);

  const activeChips = [
    department && { key: 'department', label: `Department: ${options.departments.find((d) => String(d.id) === String(department))?.name || department}` },
    branch && { key: 'branch', label: `Branch: ${options.branches.find((b) => String(b.id) === String(branch))?.name || branch}` },
    status && { key: 'status', label: `Status: ${String(status).replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}` },
    designation && { key: 'designation', label: `Designation: ${options.designations.find((d) => String(d.id) === String(designation))?.name || designation}` },
  ].filter(Boolean);

  const clearChip = (key) => {
    if (key === 'department') setDepartment('');
    if (key === 'branch') setBranch('');
    if (key === 'status') setStatus('');
    if (key === 'designation') setDesignation('');
    setPage(1);
  };

  const applyManually = () => {
    fetchEmployees();
  };

  const switchView = (mode) => {
    setViewMode(mode);
    localStorage.setItem('emp-directory-view', mode);
  };

  return (
    <div className="space-y-4">
      {/* Toolbar */}
      <section className="rounded-3xl p-6" style={{ background: 'var(--hr-surface)', border: '1px solid var(--hr-line)', boxShadow: 'var(--hr-shadow-soft)' }}>
        <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
          <div className="flex items-center gap-4 text-sm font-semibold">
            <span className="emp-tab-link is-active pb-1">Employee</span>
            <a href={window.routes?.leaveIndex || '/modules/leave'} className="emp-tab-link pb-1">Leave Request</a>
          </div>
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="flex items-center rounded-xl p-1" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
              <button type="button" className={`inline-flex h-9 w-9 items-center justify-center rounded-lg ${viewMode === 'grid' ? 'emp-view-toggle is-active' : 'emp-view-toggle'}`} aria-label="Grid view" onClick={() => switchView('grid')}>▦</button>
              <button type="button" className={`inline-flex h-9 w-9 items-center justify-center rounded-lg ${viewMode === 'list' ? 'emp-view-toggle is-active' : 'emp-view-toggle'}`} aria-label="List view" onClick={() => switchView('list')}>≡</button>
            </div>
            <label className="relative w-full sm:w-[18rem]">
              <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center" style={{ color: 'var(--hr-text-muted)' }}>🔎</span>
              <input
                ref={searchRef}
                type="search"
                placeholder="Search name, email, ID, designation"
                className="w-full rounded-xl h-10 pl-10 pr-9 text-[13px] md:text-[14px]"
                style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }}
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              />
              {search && !loading && (
                <button type="button" className="absolute inset-y-0 right-2 my-auto h-6 w-6 rounded hover:bg-black/5 dark:hover:bg-white/10" aria-label="Clear search" onClick={() => { setSearch(''); setPage(1); searchRef.current?.focus(); }}>×</button>
              )}
              {loading && (
                <span className="absolute inset-y-0 right-2 my-auto"><Spinner /></span>
              )}
            </label>
          </div>
        </div>

        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <select className="rounded-xl px-3 h-10 text-[13px] md:text-[14px]" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }} value={department} onChange={(e) => { setDepartment(e.target.value); setPage(1); }}>
            <option value="">All Departments</option>
            {options.departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
          </select>
          <select className="rounded-xl px-3 h-10 text-[13px] md:text-[14px]" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }} value={branch} onChange={(e) => { setBranch(e.target.value); setPage(1); }}>
            <option value="">All Branches</option>
            {options.branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
          </select>
          <select className="rounded-xl px-3 h-10 text-[13px] md:text-[14px]" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }} value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
            <option value="">All Status</option>
            {options.statuses.map((s) => <option key={s} value={s}>{s[0].toUpperCase() + s.slice(1)}</option>)}
          </select>
          <div className="flex items-center gap-2">
            <select className="flex-1 rounded-xl px-3 h-10 text-[13px] md:text-[14px]" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }} value={designation} onChange={(e) => { setDesignation(e.target.value); setPage(1); }}>
              <option value="">All Designations</option>
              {options.designations.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
            </select>
            <button type="button" onClick={applyManually} className="rounded-xl px-3 h-10 text-[13px] md:text-[14px] font-medium border opacity-80 hover:opacity-100" style={{ border: '1px solid var(--hr-line)', color: 'var(--hr-text-muted)' }}>
              Apply
            </button>
          </div>
        </div>

        {activeChips.length > 0 && (
          <div className="mt-3 flex flex-wrap items-center gap-2">
            {activeChips.map((c) => <Chip key={c.key} label={c.label} onClear={() => clearChip(c.key)} />)}
            <button type="button" className="text-xs underline underline-offset-2" onClick={() => { setDepartment(''); setBranch(''); setStatus(''); setDesignation(''); setPage(1); }}>Clear All</button>
          </div>
        )}

        <div className="mt-3 flex items-center gap-2">
          <select className="rounded-xl px-2 h-9 text-xs" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }} value={sortBy} onChange={(e) => { setSortBy(e.target.value); setPage(1); }}>
            <option value="id">Sort: Default</option>
            <option value="full_name">Full Name</option>
            <option value="joined_date">Joined Date</option>
            <option value="designation">Designation</option>
            <option value="department">Department</option>
            <option value="branch">Branch</option>
            <option value="status">Status</option>
          </select>
          <select className="rounded-xl px-2 h-9 text-xs" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }} value={sortDirection} onChange={(e) => { setSortDirection(e.target.value); setPage(1); }}>
            <option value="asc">Asc</option>
            <option value="desc">Desc</option>
          </select>
          <select className="rounded-xl px-2 h-9 text-xs" style={{ border: '1px solid var(--hr-line)', background: 'var(--hr-surface-strong)' }} value={String(perPage)} onChange={(e) => { setPerPage(parseInt(e.target.value, 10)); setPage(1); }}>
            {[10, 12, 15, 20, 24, 30].map((n) => <option key={n} value={n}>{n}/page</option>)}
          </select>
        </div>
      </section>

      {/* Directory */}
      <section className="rounded-3xl p-6" style={{ background: 'var(--hr-surface)', border: '1px solid var(--hr-line)', boxShadow: 'var(--hr-shadow-soft)' }}>
        <div className="flex items-center justify-between gap-4">
          <div>
            <h4 className="text-lg font-extrabold">Employee Directory</h4>
            <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>Showing {employees.length} of {new Intl.NumberFormat().format(total)} employee records.</p>
          </div>
        </div>

        {/* Content */}
        {initialLoading ? (
          <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="rounded-2xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                <div className="h-5 w-1/3 rounded" style={{ ...skeletonPulse, background: 'var(--hr-line)' }} />
                <div className="mt-3 h-3 w-full rounded" style={{ ...skeletonPulse, background: 'var(--hr-line)' }} />
                <div className="mt-2 h-3 w-2/3 rounded" style={{ ...skeletonPulse, background: 'var(--hr-line)' }} />
                <div className="mt-6 h-8 w-24 rounded" style={{ ...skeletonPulse, background: 'var(--hr-line)' }} />
              </div>
            ))}
          </div>
        ) : error ? (
          <div className="mt-6 rounded-xl border p-4 text-sm" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)', color: 'var(--hr-text-muted)' }}>{error}</div>
        ) : employees.length === 0 ? (
          <div className="mt-6 flex flex-col items-center justify-center rounded-2xl border p-10 text-center" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
            <div className="h-20 w-20 rounded-full" style={{ background: 'var(--hr-line)' }} />
            <p className="mt-3 text-sm" style={{ color: 'var(--hr-text-muted)' }}>No employees match your filters.</p>
          </div>
        ) : viewMode === 'grid' ? (
          <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            {employees.map((e) => <EmployeeCard key={e.id} e={e} />)}
          </div>
        ) : (
          <div className="mt-6 overflow-x-auto">
            <table className="w-full min-w-[860px] text-sm">
              <thead>
                <tr className="border-b text-left" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                  <th className="py-2.5 px-3 font-semibold">Employee</th>
                  <th className="py-2.5 px-3 font-semibold">Status</th>
                  <th className="py-2.5 px-3 font-semibold">Department</th>
                  <th className="py-2.5 px-3 font-semibold">Joined</th>
                  <th className="py-2.5 px-3 font-semibold">Email</th>
                  <th className="py-2.5 px-3 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                {employees.map((e) => <EmployeeRow key={e.id} e={e} />)}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        <div className="mt-4 flex items-center justify-between gap-3">
          <div className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>Page {page} of {lastPage}</div>
          <div className="flex items-center gap-2">
            <button type="button" className="rounded-lg border px-2 py-1 text-xs" disabled={loading || page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))} style={{ borderColor: 'var(--hr-line)', opacity: page <= 1 ? 0.5 : 1 }}>Previous</button>
            <button type="button" className="rounded-lg border px-2 py-1 text-xs" disabled={loading || page >= lastPage} onClick={() => setPage((p) => Math.min(lastPage, p + 1))} style={{ borderColor: 'var(--hr-line)', opacity: page >= lastPage ? 0.5 : 1 }}>Next</button>
          </div>
        </div>
      </section>
    </div>
  );
}

export function mountEmployeeDirectory() {
  const root = document.getElementById('employee-directory-root');
  if (!root) return;
  const apiUrl = root.dataset.apiUrl || '/api/employees';
  const r = createRoot(root);
  r.render(<EmployeeDirectory apiUrl={apiUrl} />);
}

