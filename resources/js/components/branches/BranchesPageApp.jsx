import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import { AppModalPortal } from '../shared/AppModalPortal';

const EMPTY_FORM = {
    name: '',
    code: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
    location: '',
    description: '',
    is_active: true,
};

const parsePayload = (node) => {
    if (!node) {
        return null;
    }

    const raw = node.dataset.payload;
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return null;
    }
};

const buildBranchUrl = (template, id) => {
    if (!template) {
        return '';
    }

    return template.replace('__BRANCH__', String(id));
};

const mapServerErrors = (errors = {}) => Object.entries(errors).reduce((acc, [field, messages]) => ({
    ...acc,
    [field]: Array.isArray(messages) ? messages[0] : messages,
}), {});

const readDarkMode = () => (
    typeof document !== 'undefined'
        && document.documentElement.classList.contains('dark')
);

const mapBranchToForm = (branch) => ({
    ...EMPTY_FORM,
    name: branch?.name ?? '',
    code: branch?.code ?? '',
    address_line_1: branch?.address_line_1 ?? '',
    address_line_2: branch?.address_line_2 ?? '',
    city: branch?.city ?? '',
    state: branch?.state ?? '',
    country: branch?.country ?? '',
    postal_code: branch?.postal_code ?? '',
    location: branch?.location ?? '',
    description: branch?.description ?? '',
    is_active: Boolean(branch?.is_active),
});

const buildSubmitPayload = (values) => ({
    name: values.name.trim(),
    code: values.code.trim(),
    address_line_1: values.address_line_1.trim(),
    address_line_2: values.address_line_2.trim(),
    city: values.city.trim(),
    state: values.state.trim(),
    country: values.country.trim(),
    postal_code: values.postal_code.trim(),
    location: values.location.trim(),
    description: values.description.trim(),
    is_active: values.is_active ? 1 : 0,
});

const clientValidate = (values) => {
    const errors = {};

    if (!values.name.trim()) {
        errors.name = 'Branch name is required.';
    }

    if (!values.code.trim()) {
        errors.code = 'Branch code is required.';
    }

    return errors;
};

const statusBadgeStyles = (isActive, isDarkMode) => {
    if (isDarkMode) {
        return isActive
            ? { color: '#ffffff', background: 'rgb(16 185 129 / 0.42)' }
            : { color: '#ffffff', background: 'rgb(245 158 11 / 0.42)' };
    }

    return isActive
        ? { color: '#166534', background: 'rgb(134 239 172 / 0.32)' }
        : { color: '#92400e', background: 'rgb(253 230 138 / 0.42)' };
};

const statusToggleShellStyles = (isActive, isDarkMode) => {
    if (isDarkMode) {
        return {
            borderColor: 'rgb(100 116 139 / 0.55)',
            background: isActive ? 'rgb(16 185 129 / 0.18)' : 'rgb(245 158 11 / 0.2)',
        };
    }

    return {
        borderColor: 'var(--hr-line)',
        background: isActive ? 'rgb(209 250 229)' : 'rgb(254 243 199)',
    };
};

const statusToggleTrackStyles = (isActive, isDarkMode) => ({
    background: isActive
        ? (isDarkMode ? 'rgb(16 185 129 / 0.86)' : '#10b981')
        : (isDarkMode ? 'rgb(245 158 11 / 0.86)' : '#f59e0b'),
});

const statusToggleLabelStyles = (isDarkMode) => ({
    color: isDarkMode ? '#f8fafc' : '#111827',
});

const textInputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';

function BranchField({
    label,
    name,
    value,
    onChange,
    error,
    required = false,
    placeholder,
    helper,
    type = 'text',
}) {
    return (
        <div className="flex flex-col gap-2">
            <label htmlFor={name} className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                {label}
                {required ? ' *' : ''}
            </label>
            <input
                id={name}
                name={name}
                type={type}
                value={value}
                onChange={(event) => onChange(name, event.target.value)}
                placeholder={placeholder}
                className={textInputClassName}
                style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
            />
            {helper ? <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{helper}</p> : null}
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );
}

function BranchTextarea({
    label,
    name,
    value,
    onChange,
    error,
    placeholder,
}) {
    return (
        <div className="flex flex-col gap-2 md:col-span-2">
            <label htmlFor={name} className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                {label}
            </label>
            <textarea
                id={name}
                name={name}
                value={value}
                rows={4}
                onChange={(event) => onChange(name, event.target.value)}
                placeholder={placeholder}
                className={`${textInputClassName} resize-y`}
                style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
            />
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );
}

function BranchesPageApp({ payload }) {
    const routes = payload.routes ?? {};
    const initialMeta = payload.branches?.meta ?? {
        currentPage: 1,
        lastPage: 1,
        perPage: 12,
        total: 0,
        from: null,
        to: null,
    };
    const initialFilters = {
        q: payload.filters?.q ?? '',
        status: ['all', 'active', 'inactive'].includes(payload.filters?.status) ? payload.filters.status : 'all',
    };
    const initialEditingBranch = payload.editingBranch ?? null;

    const [branches, setBranches] = useState(payload.branches?.data ?? []);
    const [meta, setMeta] = useState(initialMeta);
    const [filters, setFilters] = useState(initialFilters);
    const [searchInput, setSearchInput] = useState(initialFilters.q);
    const [loadingList, setLoadingList] = useState(false);
    const [listError, setListError] = useState('');

    const [formOpen, setFormOpen] = useState(Boolean(initialEditingBranch));
    const [formMode, setFormMode] = useState(initialEditingBranch ? 'edit' : 'create');
    const [editingId, setEditingId] = useState(initialEditingBranch?.id ?? null);
    const [formValues, setFormValues] = useState(mapBranchToForm(initialEditingBranch));
    const [formErrors, setFormErrors] = useState({});
    const [savingForm, setSavingForm] = useState(false);

    const [statusMessage, setStatusMessage] = useState(payload.flash?.status ?? '');
    const [errorMessage, setErrorMessage] = useState(payload.flash?.error ?? '');
    const [isDarkMode, setIsDarkMode] = useState(readDarkMode);

    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleteBusy, setDeleteBusy] = useState(false);

    const formRef = useRef(null);
    const perPageRef = useRef(initialMeta.perPage || 12);
    const filterHydratedRef = useRef(false);
    const requestCounter = useRef(0);

    const csrfConfig = useMemo(() => (
        payload.csrfToken
            ? { headers: { 'X-CSRF-TOKEN': payload.csrfToken } }
            : undefined
    ), [payload.csrfToken]);

    const fetchBranches = useCallback(async ({ page, q, status } = {}) => {
        if (!routes.list) {
            return;
        }

        const requestId = requestCounter.current + 1;
        requestCounter.current = requestId;

        setLoadingList(true);
        setListError('');

        try {
            const { data } = await axios.get(routes.list, {
                params: {
                    q: q ?? filters.q,
                    status: status ?? filters.status,
                    page: page ?? meta.currentPage ?? 1,
                    per_page: perPageRef.current,
                },
            });

            if (requestCounter.current !== requestId) {
                return;
            }

            setBranches(data?.data ?? []);
            setMeta(data?.meta ?? initialMeta);
        } catch (_error) {
            if (requestCounter.current !== requestId) {
                return;
            }
            setListError('Unable to load branches right now. Please retry.');
        } finally {
            if (requestCounter.current === requestId) {
                setLoadingList(false);
            }
        }
    }, [filters.q, filters.status, initialMeta, meta.currentPage, routes.list]);

    useEffect(() => {
        const debounce = window.setTimeout(() => {
            setFilters((prev) => {
                if (prev.q === searchInput) {
                    return prev;
                }

                return {
                    ...prev,
                    q: searchInput,
                };
            });
        }, 320);

        return () => window.clearTimeout(debounce);
    }, [searchInput]);

    useEffect(() => {
        if (!filterHydratedRef.current) {
            filterHydratedRef.current = true;
            return;
        }

        fetchBranches({
            page: 1,
            q: filters.q,
            status: filters.status,
        });
    }, [fetchBranches, filters.q, filters.status]);

    useEffect(() => {
        if (!formOpen) {
            return;
        }

        const timer = window.setTimeout(() => {
            formRef.current?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }, 120);

        return () => window.clearTimeout(timer);
    }, [formOpen, formMode, editingId]);

    useEffect(() => {
        if (typeof document === 'undefined') {
            return undefined;
        }

        const root = document.documentElement;
        const observer = new MutationObserver(() => {
            setIsDarkMode(readDarkMode());
        });

        observer.observe(root, {
            attributes: true,
            attributeFilter: ['class'],
        });

        return () => observer.disconnect();
    }, []);

    const visiblePages = useMemo(() => {
        const current = meta.currentPage || 1;
        const last = meta.lastPage || 1;
        const pages = [];

        for (let page = Math.max(1, current - 2); page <= Math.min(last, current + 2); page += 1) {
            pages.push(page);
        }

        return pages;
    }, [meta.currentPage, meta.lastPage]);

    const clearEditQueryParam = () => {
        if (typeof window === 'undefined') {
            return;
        }

        const url = new URL(window.location.href);
        if (!url.searchParams.has('edit')) {
            return;
        }

        url.searchParams.delete('edit');
        window.history.replaceState({}, '', url.toString());
    };

    const openCreateForm = () => {
        setFormMode('create');
        setEditingId(null);
        setFormValues({ ...EMPTY_FORM, is_active: true });
        setFormErrors({});
        setFormOpen(true);
        clearEditQueryParam();
    };

    const openEditForm = (branch) => {
        setFormMode('edit');
        setEditingId(branch.id);
        setFormValues(mapBranchToForm(branch));
        setFormErrors({});
        setFormOpen(true);
    };

    const closeForm = () => {
        setFormOpen(false);
        setFormMode('create');
        setEditingId(null);
        setFormValues({ ...EMPTY_FORM, is_active: true });
        setFormErrors({});
        clearEditQueryParam();
    };

    const onFieldChange = (field, value) => {
        setFormValues((prev) => ({
            ...prev,
            [field]: value,
        }));

        setFormErrors((prev) => {
            if (!prev[field]) {
                return prev;
            }

            const next = { ...prev };
            delete next[field];
            return next;
        });
    };

    const goToPage = (page) => {
        if (loadingList || page < 1 || page > (meta.lastPage || 1)) {
            return;
        }

        fetchBranches({
            page,
            q: filters.q,
            status: filters.status,
        });
    };

    const submitForm = async (event) => {
        event.preventDefault();

        const validationErrors = clientValidate(formValues);
        if (Object.keys(validationErrors).length > 0) {
            setFormErrors(validationErrors);
            return;
        }

        setSavingForm(true);
        setErrorMessage('');
        setStatusMessage('');
        setFormErrors({});

        const submitPayload = buildSubmitPayload(formValues);

        try {
            let response;
            if (formMode === 'edit' && editingId) {
                response = await axios.put(buildBranchUrl(routes.updateTemplate, editingId), submitPayload, csrfConfig);
            } else {
                response = await axios.post(routes.create, submitPayload, csrfConfig);
            }

            setStatusMessage(response?.data?.message ?? (formMode === 'edit'
                ? 'Branch updated successfully.'
                : 'Branch created successfully.'));
            closeForm();
            fetchBranches({
                page: formMode === 'edit' ? meta.currentPage : 1,
                q: filters.q,
                status: filters.status,
            });
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setFormErrors(mapServerErrors(error.response.data.errors));
            } else {
                setErrorMessage(error.response?.data?.message ?? 'Unable to save branch right now.');
            }
        } finally {
            setSavingForm(false);
        }
    };

    const askDelete = (branch) => {
        setDeleteTarget(branch);
    };

    const closeDeleteModal = () => {
        if (deleteBusy) {
            return;
        }

        setDeleteTarget(null);
    };

    const confirmDelete = async () => {
        if (!deleteTarget || !routes.deleteTemplate || deleteBusy) {
            return;
        }

        setDeleteBusy(true);
        setErrorMessage('');
        setStatusMessage('');

        try {
            const { data } = await axios.delete(buildBranchUrl(routes.deleteTemplate, deleteTarget.id), csrfConfig);
            setStatusMessage(data?.message ?? 'Branch deleted successfully.');

            if (editingId === deleteTarget.id) {
                closeForm();
            }

            const nextPage = branches.length === 1 && meta.currentPage > 1
                ? meta.currentPage - 1
                : meta.currentPage;

            await fetchBranches({
                page: nextPage,
                q: filters.q,
                status: filters.status,
            });
            setDeleteTarget(null);
        } catch (error) {
            setErrorMessage(error.response?.data?.message ?? 'Unable to delete this branch.');
        } finally {
            setDeleteBusy(false);
        }
    };

    return (
        <div className="space-y-5">
            <section className="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 className="text-xl font-extrabold">Branches</h2>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Manage company branch records and branch location details.
                    </p>
                </div>
                <button
                    type="button"
                    className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                    style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                    onClick={openCreateForm}
                >
                    <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    + Create Branch
                </button>
            </section>

            {statusMessage ? (
                <section className="hrm-modern-surface rounded-2xl p-4">
                    <p className="text-sm font-semibold text-emerald-600">{statusMessage}</p>
                </section>
            ) : null}

            {errorMessage ? (
                <section className="hrm-modern-surface rounded-2xl p-4">
                    <p className="text-sm font-semibold text-red-600">{errorMessage}</p>
                </section>
            ) : null}

            <section
                ref={formRef}
                className="overflow-hidden"
                style={{
                    maxHeight: formOpen ? '2200px' : '0px',
                    opacity: formOpen ? 1 : 0,
                    transform: formOpen ? 'translateY(0)' : 'translateY(-8px)',
                    transition: 'max-height 360ms ease, opacity 260ms ease, transform 260ms ease',
                    pointerEvents: formOpen ? 'auto' : 'none',
                }}
            >
                <article className="hrm-modern-surface rounded-2xl p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-extrabold">{formMode === 'edit' ? 'Update Branch' : 'Create Branch'}</h3>
                            <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                                Fill in branch identity, address, and active state details.
                            </p>
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

                    <form className="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4" onSubmit={submitForm}>
                        <BranchField
                            label="Branch Name"
                            name="name"
                            value={formValues.name}
                            onChange={onFieldChange}
                            error={formErrors.name}
                            placeholder="New York HQ"
                            required
                        />
                        <BranchField
                            label="Branch Code"
                            name="code"
                            value={formValues.code}
                            onChange={onFieldChange}
                            error={formErrors.code}
                            placeholder="NY-HQ"
                            required
                            helper="Must be unique."
                        />
                        <BranchField
                            label="Address Line 1"
                            name="address_line_1"
                            value={formValues.address_line_1}
                            onChange={onFieldChange}
                            error={formErrors.address_line_1}
                            placeholder="123 Madison Ave"
                        />
                        <BranchField
                            label="Address Line 2"
                            name="address_line_2"
                            value={formValues.address_line_2}
                            onChange={onFieldChange}
                            error={formErrors.address_line_2}
                            placeholder="Suite 500"
                        />
                        <BranchField
                            label="City"
                            name="city"
                            value={formValues.city}
                            onChange={onFieldChange}
                            error={formErrors.city}
                            placeholder="New York"
                        />
                        <BranchField
                            label="State"
                            name="state"
                            value={formValues.state}
                            onChange={onFieldChange}
                            error={formErrors.state}
                            placeholder="NY"
                        />
                        <BranchField
                            label="Country"
                            name="country"
                            value={formValues.country}
                            onChange={onFieldChange}
                            error={formErrors.country}
                            placeholder="United States"
                        />
                        <BranchField
                            label="Postal Code"
                            name="postal_code"
                            value={formValues.postal_code}
                            onChange={onFieldChange}
                            error={formErrors.postal_code}
                            placeholder="10016"
                        />
                        <BranchField
                            label="Location (Optional)"
                            name="location"
                            value={formValues.location}
                            onChange={onFieldChange}
                            error={formErrors.location}
                            placeholder="Midtown Manhattan"
                        />
                        <div className="flex flex-col gap-2">
                            <p className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Status
                            </p>
                            <button
                                type="button"
                                className="w-fit rounded-full px-1.5 py-1 inline-flex items-center gap-2 border"
                                style={statusToggleShellStyles(formValues.is_active, isDarkMode)}
                                onClick={() => onFieldChange('is_active', !formValues.is_active)}
                                aria-pressed={formValues.is_active}
                            >
                                <span
                                    className={`h-5 w-9 rounded-full inline-flex items-center px-0.5 transition-all ${formValues.is_active ? 'justify-end' : 'justify-start'}`}
                                    style={statusToggleTrackStyles(formValues.is_active, isDarkMode)}
                                >
                                    <span className="h-4 w-4 rounded-full bg-white shadow" />
                                </span>
                                <span className="text-xs font-semibold" style={statusToggleLabelStyles(isDarkMode)}>
                                    {formValues.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </button>
                            {formErrors.is_active ? <p className="text-xs text-red-500">{formErrors.is_active}</p> : null}
                        </div>

                        <BranchTextarea
                            label="Description"
                            name="description"
                            value={formValues.description}
                            onChange={onFieldChange}
                            error={formErrors.description}
                            placeholder="Branch operational notes, coverage, or purpose."
                        />

                        <div className="md:col-span-2 flex items-center gap-3 pt-1">
                            <button
                                type="submit"
                                className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                                style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                                disabled={savingForm}
                            >
                                {savingForm
                                    ? (formMode === 'edit' ? 'Updating...' : 'Creating...')
                                    : (formMode === 'edit' ? 'Update Branch' : 'Create Branch')}
                            </button>
                            <button
                                type="button"
                                className="rounded-xl px-4 py-2.5 text-sm font-semibold border"
                                style={{ borderColor: 'var(--hr-line)' }}
                                onClick={closeForm}
                                disabled={savingForm}
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </article>
            </section>

            <section className="hrm-modern-surface rounded-2xl p-5">
                <div className="flex flex-wrap items-center gap-3 justify-between">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="flex items-center gap-2 rounded-xl border px-3 py-2.5" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ color: 'var(--hr-text-muted)' }}>
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                            <input
                                type="search"
                                value={searchInput}
                                onChange={(event) => setSearchInput(event.target.value)}
                                placeholder="Search by name, code, location, address..."
                                className="bg-transparent text-sm min-w-[220px] focus:outline-none"
                            />
                        </div>
                        <select
                            className="rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={filters.status}
                            onChange={(event) => {
                                const nextStatus = event.target.value;
                                setFilters((prev) => ({
                                    ...prev,
                                    status: nextStatus,
                                }));
                            }}
                        >
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                        {meta.total > 0
                            ? `Showing ${meta.from}-${meta.to} of ${meta.total}`
                            : 'No records'}
                    </p>
                </div>

                {listError ? (
                    <div className="mt-4 rounded-xl border px-3 py-2 text-sm text-red-600" style={{ borderColor: 'rgb(248 113 113 / 0.4)', background: 'rgb(254 242 242 / 0.72)' }}>
                        <div className="flex items-center justify-between gap-2">
                            <span>{listError}</span>
                            <button
                                type="button"
                                className="rounded-lg px-2.5 py-1 text-xs font-semibold border"
                                style={{ borderColor: 'rgb(248 113 113 / 0.42)' }}
                                onClick={() => fetchBranches({
                                    page: meta.currentPage,
                                    q: filters.q,
                                    status: filters.status,
                                })}
                            >
                                Retry
                            </button>
                        </div>
                    </div>
                ) : null}

                <div className="mt-4 overflow-x-auto">
                    <table className="w-full min-w-[920px] text-sm">
                        <thead>
                            <tr className="border-b text-left" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                                <th className="py-2.5 px-2 font-semibold">Name</th>
                                <th className="py-2.5 px-2 font-semibold">Code</th>
                                <th className="py-2.5 px-2 font-semibold">Location</th>
                                <th className="py-2.5 px-2 font-semibold">Address</th>
                                <th className="py-2.5 px-2 font-semibold">Status</th>
                                <th className="py-2.5 px-2 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loadingList ? (
                                <tr>
                                    <td colSpan={6} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                        Loading branches...
                                    </td>
                                </tr>
                            ) : null}

                            {!loadingList && branches.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                        No branch records found.
                                    </td>
                                </tr>
                            ) : null}

                            {!loadingList && branches.map((branch) => (
                                <tr key={branch.id} className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
                                    <td className="py-3 px-2 font-semibold">{branch.name}</td>
                                    <td className="py-3 px-2">{branch.code || 'N/A'}</td>
                                    <td className="py-3 px-2">{branch.locationLabel || 'N/A'}</td>
                                    <td className="py-3 px-2 max-w-[260px] truncate" title={branch.addressSummary || 'N/A'}>
                                        {branch.addressSummary || 'N/A'}
                                    </td>
                                    <td className="py-3 px-2">
                                        <span
                                            className="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1"
                                            style={statusBadgeStyles(Boolean(branch.is_active), isDarkMode)}
                                        >
                                            {branch.statusLabel || (branch.is_active ? 'Active' : 'Inactive')}
                                        </span>
                                    </td>
                                    <td className="py-3 px-2">
                                        <div className="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border"
                                                style={{ borderColor: 'var(--hr-line)' }}
                                                onClick={() => openEditForm(branch)}
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600"
                                                style={{ borderColor: 'rgb(239 68 68 / 0.32)' }}
                                                onClick={() => askDelete(branch)}
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                        Page {meta.currentPage} of {meta.lastPage}
                    </p>
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            className="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={() => goToPage((meta.currentPage || 1) - 1)}
                            disabled={loadingList || (meta.currentPage || 1) <= 1}
                        >
                            Previous
                        </button>
                        {visiblePages.map((page) => (
                            <button
                                key={page}
                                type="button"
                                className="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                                style={{
                                    borderColor: page === meta.currentPage ? 'var(--hr-accent-border)' : 'var(--hr-line)',
                                    background: page === meta.currentPage ? 'var(--hr-accent-soft)' : 'transparent',
                                }}
                                onClick={() => goToPage(page)}
                                disabled={loadingList}
                            >
                                {page}
                            </button>
                        ))}
                        <button
                            type="button"
                            className="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={() => goToPage((meta.currentPage || 1) + 1)}
                            disabled={loadingList || (meta.currentPage || 1) >= (meta.lastPage || 1)}
                        >
                            Next
                        </button>
                    </div>
                </div>
            </section>

            <AppModalPortal open={Boolean(deleteTarget)} onBackdropClick={closeDeleteModal}>
                <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                    <h3 className="text-lg font-bold">Delete Branch</h3>
                    <p className="mt-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        {deleteTarget
                            ? `Are you sure you want to delete "${deleteTarget.name}"? This action cannot be undone.`
                            : 'Confirm branch deletion.'}
                    </p>
                    <div className="mt-5 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            className="rounded-xl px-4 py-2 text-sm font-semibold border"
                            style={{ borderColor: 'var(--hr-line)' }}
                            onClick={closeDeleteModal}
                            disabled={deleteBusy}
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            className="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                            style={{ background: '#dc2626' }}
                            onClick={confirmDelete}
                            disabled={deleteBusy}
                        >
                            {deleteBusy ? 'Deleting...' : 'Delete'}
                        </button>
                    </div>
                </div>
            </AppModalPortal>
        </div>
    );
}

export function mountBranchesPageApp() {
    const rootElement = document.getElementById('branches-page-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        return;
    }

    createRoot(rootElement).render(<BranchesPageApp payload={payload} />);
}
