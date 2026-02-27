import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import { AppModalPortal } from '../shared/AppModalPortal';

const EMPTY_FORM = {
    name: '',
    code: '',
    level: '',
    description: '',
    is_active: true,
};

const parsePayload = (node) => {
    if (!node) return null;
    const raw = node.dataset.payload;
    if (!raw) return null;
    try { return JSON.parse(raw); } catch { return null; }
};

const buildUrl = (template, id) => (template ? template.replace('__DESIGNATION__', String(id)) : '');
const mapServerErrors = (errors = {}) => Object.entries(errors).reduce((acc, [field, messages]) => ({ ...acc, [field]: Array.isArray(messages) ? messages[0] : messages }), {});
const readDarkMode = () => (typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));

const mapToForm = (d) => ({
    ...EMPTY_FORM,
    name: d?.name ?? '',
    code: d?.code ?? '',
    level: d?.level ?? '',
    description: d?.description ?? '',
    is_active: Boolean(d?.is_active),
});

const buildSubmitPayload = (values) => ({
    name: values.name.trim(),
    code: values.code.trim(),
    level: values.level === '' ? '' : Number.parseInt(String(values.level), 10),
    description: values.description.trim(),
    is_active: values.is_active ? 1 : 0,
});

const clientValidate = (values) => {
    const errors = {};
    if (!values.name.trim()) errors.name = 'Designation name is required.';
    if (values.level !== '' && (Number.isNaN(Number(values.level)) || Number(values.level) < 0)) errors.level = 'Level must be a non-negative number.';
    return errors;
};

const textInputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';
const statusBadgeStyles = (isActive, isDarkMode) => (isDarkMode
    ? (isActive ? { color: '#ffffff', background: 'rgb(16 185 129 / 0.42)' } : { color: '#ffffff', background: 'rgb(245 158 11 / 0.42)' })
    : (isActive ? { color: '#166534', background: 'rgb(134 239 172 / 0.32)' } : { color: '#92400e', background: 'rgb(253 230 138 / 0.42)' })
);
const statusToggleShellStyles = (isActive, isDarkMode) => (isDarkMode
    ? { borderColor: 'rgb(100 116 139 / 0.55)', background: isActive ? 'rgb(16 185 129 / 0.18)' : 'rgb(245 158 11 / 0.2)' }
    : { borderColor: 'var(--hr-line)', background: isActive ? 'rgb(209 250 229)' : 'rgb(254 243 199)' }
);
const statusToggleTrackStyles = (isActive, isDarkMode) => ({ background: isActive ? (isDarkMode ? 'rgb(16 185 129 / 0.86)' : '#10b981') : (isDarkMode ? 'rgb(245 158 11 / 0.86)' : '#f59e0b') });
const statusToggleLabelStyles = (isDarkMode) => ({ color: isDarkMode ? '#f8fafc' : '#111827' });

function Field({ label, name, value, onChange, error, type = 'text', placeholder = '', required = false }) {
    return (
        <div className="space-y-2">
            <label htmlFor={name} className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                {label}{required ? ' *' : ''}
            </label>
            <input id={name} name={name} type={type} value={value}
                   onChange={(e) => onChange(name, e.target.value)} placeholder={placeholder}
                   className={textInputClassName} style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }} />
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );
}

function Textarea({ label, name, value, onChange, error, placeholder = '' }) {
    return (
        <div className="space-y-2">
            <label htmlFor={name} className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>{label}</label>
            <textarea id={name} name={name} rows={3} value={value} onChange={(e) => onChange(name, e.target.value)} placeholder={placeholder}
                      className="w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent resize-y" style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }} />
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );
}

function StatusToggle({ checked, onChange, isDarkMode }) {
    return (
        <div className="flex items-center gap-2 rounded-xl border px-3 py-2.5" style={statusToggleShellStyles(checked, isDarkMode)}>
            <div className="h-5 w-9 rounded-full relative cursor-pointer" role="switch" aria-checked={checked}
                 onClick={() => onChange(!checked)} style={statusToggleTrackStyles(checked, isDarkMode)}>
                <span className={`absolute top-0.5 ${checked ? 'right-0.5' : 'left-0.5'} h-4 w-4 bg-white rounded-full transition-all`} />
            </div>
            <span className="text-sm font-semibold" style={statusToggleLabelStyles(isDarkMode)}>{checked ? 'Active' : 'Inactive'}</span>
        </div>
    );
}

function DesignationsPageApp({ payload }) {
    const routes = payload.routes ?? {};
    const initialMeta = payload.designations?.meta ?? { currentPage: 1, lastPage: 1, perPage: 12, total: 0, from: null, to: null };
    const initialFilters = { q: payload.filters?.q ?? '', status: ['all', 'active', 'inactive'].includes(payload.filters?.status) ? payload.filters.status : 'all' };
    const initialEditing = payload.editingDesignation ?? null;

    const [designations, setDesignations] = useState(payload.designations?.data ?? []);
    const [meta, setMeta] = useState(initialMeta);
    const [filters, setFilters] = useState(initialFilters);
    const [searchInput, setSearchInput] = useState(initialFilters.q);
    const [loadingList, setLoadingList] = useState(false);
    const [listError, setListError] = useState('');

    const [formOpen, setFormOpen] = useState(Boolean(initialEditing));
    const [formMode, setFormMode] = useState(initialEditing ? 'edit' : 'create');
    const [editingId, setEditingId] = useState(initialEditing?.id ?? null);
    const [formValues, setFormValues] = useState(mapToForm(initialEditing));
    const [formErrors, setFormErrors] = useState({});
    const [savingForm, setSavingForm] = useState(false);
    const [statusMessage, setStatusMessage] = useState(payload.flash?.status ?? '');
    const [errorMessage, setErrorMessage] = useState(payload.flash?.error ?? '');
    const [isDarkMode, setIsDarkMode] = useState(readDarkMode);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleteBusy, setDeleteBusy] = useState(false);

    const formRef = useRef(null);
    const filterHydratedRef = useRef(false);
    const requestCounter = useRef(0);
    const perPageRef = useRef(initialMeta.perPage || 12);

    const csrfConfig = useMemo(() => (payload.csrfToken ? { headers: { 'X-CSRF-TOKEN': payload.csrfToken } } : undefined), [payload.csrfToken]);

    const fetchDesignations = useCallback(async ({ page, q, status } = {}) => {
        if (!routes.list) return;
        const reqId = ++requestCounter.current;
        setLoadingList(true); setListError('');
        try {
            const { data } = await axios.get(routes.list, { params: { q: q ?? filters.q, status: status ?? filters.status, page: page ?? meta.currentPage ?? 1, per_page: perPageRef.current } });
            if (requestCounter.current !== reqId) return;
            setDesignations(data?.data ?? []);
            setMeta(data?.meta ?? initialMeta);
        } catch (_e) {
            if (requestCounter.current !== reqId) return;
            setListError('Unable to load designations right now. Please retry.');
        } finally {
            if (requestCounter.current === reqId) setLoadingList(false);
        }
    }, [filters.q, filters.status, meta.currentPage, routes.list, initialMeta]);

    useEffect(() => {
        const t = window.setTimeout(() => setFilters((prev) => (prev.q === searchInput ? prev : { ...prev, q: searchInput })), 320);
        return () => window.clearTimeout(t);
    }, [searchInput]);

    useEffect(() => {
        if (!filterHydratedRef.current) { filterHydratedRef.current = true; return; }
        fetchDesignations({ page: 1, q: filters.q, status: filters.status });
    }, [fetchDesignations, filters.q, filters.status]);

    useEffect(() => {
        if (!formOpen) return;
        const timer = window.setTimeout(() => { formRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 120);
        return () => window.clearTimeout(timer);
    }, [editingId, formMode, formOpen]);

    useEffect(() => {
        if (typeof document === 'undefined') return undefined;
        const root = document.documentElement;
        const observer = new MutationObserver(() => setIsDarkMode(readDarkMode()));
        observer.observe(root, { attributes: true, attributeFilter: ['class'] });
        return () => observer.disconnect();
    }, []);

    const visiblePages = useMemo(() => {
        const current = meta.currentPage || 1; const last = meta.lastPage || 1; const pages = [];
        for (let page = Math.max(1, current - 2); page <= Math.min(last, current + 2); page += 1) pages.push(page);
        return pages;
    }, [meta.currentPage, meta.lastPage]);

    const clearEditQueryParam = () => {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        if (!url.searchParams.has('edit')) return;
        url.searchParams.delete('edit');
        window.history.replaceState({}, '', url.toString());
    };

    const closeForm = () => { setFormOpen(false); setFormMode('create'); setEditingId(null); setFormValues({ ...EMPTY_FORM, is_active: true }); setFormErrors({}); clearEditQueryParam(); };
    const openCreateForm = () => { setFormMode('create'); setEditingId(null); setFormValues({ ...EMPTY_FORM, is_active: true }); setFormErrors({}); setFormOpen(true); clearEditQueryParam(); };
    const openEditForm = (d) => { setFormMode('edit'); setEditingId(d.id); setFormValues(mapToForm(d)); setFormErrors({}); setFormOpen(true); };
    const onFieldChange = (field, value) => { setFormValues((prev) => ({ ...prev, [field]: value })); if (formErrors[field]) setFormErrors((prev) => { const next = { ...prev }; delete next[field]; return next; }); };
    const goToPage = (page) => { if (loadingList || page < 1 || page > (meta.lastPage || 1)) return; fetchDesignations({ page, q: filters.q, status: filters.status }); };

    const submitForm = async (e) => {
        e.preventDefault();
        const nextErrors = clientValidate(formValues);
        if (Object.keys(nextErrors).length > 0) { setFormErrors(nextErrors); return; }
        setSavingForm(true);
        try {
            const payload = buildSubmitPayload(formValues);
            let response;
            if (formMode === 'edit' && editingId) {
                response = await axios.put(buildUrl(routes.updateTemplate, editingId), payload, csrfConfig);
            } else {
                response = await axios.post(routes.create, payload, csrfConfig);
            }
            const saved = response?.data?.data ?? null;
            if (saved) {
                setStatusMessage(response?.data?.message || 'Saved.');
                setErrorMessage('');
                closeForm();
                // refresh list
                fetchDesignations({ page: meta.currentPage, q: filters.q, status: filters.status });
            }
        } catch (err) {
            const data = err?.response?.data || {};
            setErrorMessage(data?.message || 'Unable to save.');
            setStatusMessage('');
            if (data?.errors) setFormErrors(mapServerErrors(data.errors));
        } finally {
            setSavingForm(false);
        }
    };

    const askDelete = (d) => setDeleteTarget(d);
    const closeDeleteModal = () => { if (deleteBusy) return; setDeleteTarget(null); };
    const confirmDelete = async () => {
        if (!deleteTarget || !routes.deleteTemplate) return;
        setDeleteBusy(true);
        try {
            const { data } = await axios.delete(buildUrl(routes.deleteTemplate, deleteTarget.id), csrfConfig);
            setStatusMessage(data?.message || 'Deleted.');
            setErrorMessage('');
            setDeleteTarget(null);
            fetchDesignations({ page: meta.currentPage, q: filters.q, status: filters.status });
        } catch (err) {
            const data = err?.response?.data || {};
            setErrorMessage(data?.message || 'Unable to delete.');
            setStatusMessage('');
        } finally {
            setDeleteBusy(false);
        }
    };

    return (
        <div>
            <section className="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 className="text-xl font-extrabold">Designations</h2>
                    <p className="text-sm mt-2" style={{ color: 'var(--hr-text-muted)' }}>Manage organization job titles and levels.</p>
                </div>
                <button
                    type="button"
                    className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                    style={{ background: 'linear-gradient(120deg, #0ea5e9, #6366f1)' }}
                    onClick={openCreateForm}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    + Add Designation
                </button>
            </section>

            {statusMessage ? (
                <section className="hrm-modern-surface rounded-2xl p-6">
                    <p className="text-sm font-semibold text-emerald-600">{statusMessage}</p>
                </section>
            ) : null}

            {errorMessage ? (
                <section className="hrm-modern-surface rounded-2xl p-6">
                    <p className="text-sm font-semibold text-red-600">{errorMessage}</p>
                </section>
            ) : null}

            <section
                ref={formRef}
                className="overflow-hidden"
                style={{
                    maxHeight: formOpen ? '1800px' : '0px',
                    opacity: formOpen ? 1 : 0,
                    transform: formOpen ? 'translateY(0)' : 'translateY(-8px)',
                    transition: 'max-height 360ms ease, opacity 260ms ease, transform 260ms ease',
                    pointerEvents: formOpen ? 'auto' : 'none',
                }}
            >
                <article className="hrm-modern-surface rounded-2xl p-6 mt-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-extrabold">{formMode === 'edit' ? 'Update Designation' : 'Create Designation'}</h3>
                            <p className="text-sm mt-2" style={{ color: 'var(--hr-text-muted)' }}>Job title, code, level and status.</p>
                        </div>
                        <button
                            type="button"
                            className="h-9 w-9 rounded-lg border inline-flex items-center justify-center"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={closeForm}
                            aria-label="Close form"
                        >
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <form onSubmit={submitForm} className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Field label="Designation Name" name="name" value={formValues.name} onChange={onFieldChange} error={formErrors.name} required />
                        <Field label="Code" name="code" value={formValues.code} onChange={onFieldChange} error={formErrors.code} placeholder="Optional" />
                        <Field label="Level" name="level" value={formValues.level} onChange={onFieldChange} error={formErrors.level} type="number" placeholder="Optional" />
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.1em] mb-2" style={{ color: 'var(--hr-text-muted)' }}>Status</p>
                            <StatusToggle checked={formValues.is_active} onChange={(checked) => onFieldChange('is_active', checked)} isDarkMode={isDarkMode} />
                        </div>
                        <div className="md:col-span-2">
                            <Textarea label="Description" name="description" value={formValues.description} onChange={onFieldChange} error={formErrors.description} placeholder="Optional" />
                        </div>
                        <div className="md:col-span-2 flex items-center gap-2 mt-2">
                            <button type="submit" className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white" disabled={savingForm} style={{ background: 'linear-gradient(120deg, #0ea5e9, #6366f1)' }}>
                                {savingForm ? (formMode === 'edit' ? 'Updating...' : 'Creating...') : (formMode === 'edit' ? 'Update Designation' : 'Create Designation')}
                            </button>
                            <button type="button" className="rounded-xl px-4 py-2.5 text-sm font-semibold border" style={{ borderColor: 'var(--hr-line)' }} onClick={closeForm} disabled={savingForm}>Cancel</button>
                        </div>
                    </form>
                </article>
            </section>

            <section className="hrm-modern-surface rounded-2xl p-6 mt-6">
                <div className="flex flex-wrap items-center gap-4 justify-between">
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="flex items-center gap-2 rounded-xl border px-3 py-2.5" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ color: 'var(--hr-text-muted)' }}>
                                <circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" />
                            </svg>
                            <input type="search" value={searchInput} onChange={(e) => setSearchInput(e.target.value)} placeholder="Search by name, code, description..." className="bg-transparent text-sm min-w-[220px] focus:outline-none" />
                        </div>
                        <select className="rounded-xl border px-3 py-2.5 text-sm bg-transparent" style={{ borderColor: 'var(--hr-line)' }} value={filters.status} onChange={(e) => setFilters((prev) => ({ ...prev, status: e.target.value }))}>
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>{meta.total > 0 ? `Showing ${meta.from}-${meta.to} of ${meta.total}` : 'No records'}</p>
                </div>

                {listError ? (
                    <div className="mt-4 rounded-xl border px-3 py-2 text-sm text-red-600" style={{ borderColor: 'rgb(248 113 113 / 0.4)', background: 'rgb(254 242 242 / 0.72)' }}>
                        <div className="flex items-center justify-between gap-2">
                            <span>{listError}</span>
                            <button type="button" className="rounded-lg px-2.5 py-1 text-xs font-semibold border" style={{ borderColor: 'rgb(248 113 113 / 0.42)' }} onClick={() => fetchDesignations({ page: meta.currentPage, q: filters.q, status: filters.status })}>Retry</button>
                        </div>
                    </div>
                ) : null}

                <div className="mt-6 overflow-x-auto">
                    <table className="w-full min-w-[880px] text-sm">
                        <thead>
                            <tr className="border-b text-left" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                                <th className="py-4 px-6 font-semibold">Name</th>
                                <th className="py-4 px-6 font-semibold">Code</th>
                                <th className="py-4 px-6 font-semibold">Level</th>
                                <th className="py-4 px-6 font-semibold">Description</th>
                                <th className="py-4 px-6 font-semibold">Status</th>
                                <th className="py-4 px-6 font-semibold">Created Date</th>
                                <th className="py-4 px-6 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loadingList ? (
                                <tr><td colSpan={7} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>Loading designations...</td></tr>
                            ) : null}
                            {!loadingList && designations.length === 0 ? (
                                <tr><td colSpan={7} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>No designation records found.</td></tr>
                            ) : null}
                            {!loadingList && designations.map((d) => (
                                <tr key={d.id} className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
                                    <td className="py-4 px-6 font-semibold">{d.name}</td>
                                    <td className="py-4 px-6">{d.code || 'N/A'}</td>
                                    <td className="py-4 px-6">{(d.level ?? '') === '' ? 'N/A' : d.level}</td>
                                    <td className="py-4 px-6 max-w-[280px] truncate" title={d.description || 'N/A'}>{d.descriptionShort || 'N/A'}</td>
                                    <td className="py-4 px-6"><span className="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style={statusBadgeStyles(Boolean(d.is_active), isDarkMode)}>{d.statusLabel || (d.is_active ? 'Active' : 'Inactive')}</span></td>
                                    <td className="py-4 px-6">{d.createdDateLabel || 'N/A'}</td>
                                    <td className="py-4 px-6">
                                        <div className="flex items-center justify-end gap-2">
                                            <button type="button" className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style={{ borderColor: 'var(--hr-line)' }} onClick={() => openEditForm(d)}>Edit</button>
                                            <button type="button" className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600" style={{ borderColor: 'rgb(239 68 68 / 0.32)' }} onClick={() => askDelete(d)}>Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mt-6 flex flex-wrap items-center justify-between gap-4">
                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>Page {meta.currentPage} of {meta.lastPage}</p>
                    <div className="flex items-center gap-2">
                        <button type="button" className="rounded-lg border px-3 py-1.5 text-xs font-semibold" style={{ borderColor: 'var(--hr-line)' }} onClick={() => goToPage((meta.currentPage || 1) - 1)} disabled={loadingList || (meta.currentPage || 1) <= 1}>Previous</button>
                        {visiblePages.map((page) => (
                            <button key={page} type="button" className="rounded-lg border px-3 py-1.5 text-xs font-semibold" style={{ borderColor: page === meta.currentPage ? 'var(--hr-accent-border)' : 'var(--hr-line)', background: page === meta.currentPage ? 'var(--hr-accent-soft)' : 'transparent' }} onClick={() => goToPage(page)} disabled={loadingList}>{page}</button>
                        ))}
                        <button type="button" className="rounded-lg border px-3 py-1.5 text-xs font-semibold" style={{ borderColor: 'var(--hr-line)' }} onClick={() => goToPage((meta.currentPage || 1) + 1)} disabled={loadingList || (meta.currentPage || 1) >= (meta.lastPage || 1)}>Next</button>
                    </div>
                </div>
            </section>

            <AppModalPortal open={Boolean(deleteTarget)} onBackdropClick={closeDeleteModal}>
                <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                    <h3 className="text-lg font-bold">Delete Designation</h3>
                    <p className="mt-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        {deleteTarget ? `Are you sure you want to delete "${deleteTarget.name}"? This action cannot be undone.` : 'Confirm designation deletion.'}
                    </p>
                    <div className="mt-6 flex items-center justify-end gap-4">
                        <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold border" style={{ borderColor: 'var(--hr-line)' }} onClick={closeDeleteModal} disabled={deleteBusy}>Cancel</button>
                        <button type="button" className="rounded-xl px-4 py-2 text-sm font-semibold text-white" style={{ background: '#dc2626' }} onClick={confirmDelete} disabled={deleteBusy}>{deleteBusy ? 'Deleting...' : 'Delete'}</button>
                    </div>
                </div>
            </AppModalPortal>
        </div>
    );
}

export function mountDesignationsPageApp() {
    const rootElement = document.getElementById('designations-page-root');
    if (!rootElement) return;
    const payload = parsePayload(rootElement);
    if (!payload) return;
    createRoot(rootElement).render(<DesignationsPageApp payload={payload} />);
}
