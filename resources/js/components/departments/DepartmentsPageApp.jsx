import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import { AppModalPortal } from '../shared/AppModalPortal';

const EMPTY_FORM = {
    name: '',
    code: '',
    branch_id: '',
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

const buildDepartmentUrl = (template, id) => {
    if (!template) {
        return '';
    }

    return template.replace('__DEPARTMENT__', String(id));
};

const mapServerErrors = (errors = {}) => Object.entries(errors).reduce((acc, [field, messages]) => ({
    ...acc,
    [field]: Array.isArray(messages) ? messages[0] : messages,
}), {});

const readDarkMode = () => (
    typeof document !== 'undefined'
        && document.documentElement.classList.contains('dark')
);

const mapDepartmentToForm = (department, defaultBranchId) => ({
    ...EMPTY_FORM,
    name: department?.name ?? '',
    code: department?.code ?? '',
    branch_id: department?.branch_id ? String(department.branch_id) : defaultBranchId,
    description: department?.description ?? '',
    is_active: Boolean(department?.is_active),
});

const buildSubmitPayload = (values) => ({
    name: values.name.trim(),
    code: values.code.trim(),
    branch_id: Number.parseInt(String(values.branch_id), 10),
    description: values.description.trim(),
    is_active: values.is_active ? 1 : 0,
});

const clientValidate = (values) => {
    const errors = {};

    if (!values.name.trim()) {
        errors.name = 'Department name is required.';
    }

    if (!values.code.trim()) {
        errors.code = 'Department code is required.';
    }

    if (!values.branch_id) {
        errors.branch_id = 'Please select a branch.';
    }

    return errors;
};

const textInputClassName = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-transparent';

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

function DepartmentField({
    label,
    name,
    value,
    onChange,
    error,
    required = false,
    placeholder,
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
                type="text"
                value={value}
                onChange={(event) => onChange(name, event.target.value)}
                placeholder={placeholder}
                className={textInputClassName}
                style={{ borderColor: error ? '#f87171' : 'var(--hr-line)' }}
            />
            {error ? <p className="text-xs text-red-500">{error}</p> : null}
        </div>
    );
}

function DepartmentsPageApp({ payload }) {
    const routes = payload.routes ?? {};
    const branches = Array.isArray(payload.branches) ? payload.branches : [];
    const defaultBranchId = branches[0]?.id ? String(branches[0].id) : '';
    const initialMeta = payload.departments?.meta ?? {
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
        branch_id: payload.filters?.branch_id ? String(payload.filters.branch_id) : '',
    };
    const initialEditingDepartment = payload.editingDepartment ?? null;

    const [departments, setDepartments] = useState(payload.departments?.data ?? []);
    const [meta, setMeta] = useState(initialMeta);
    const [filters, setFilters] = useState(initialFilters);
    const [searchInput, setSearchInput] = useState(initialFilters.q);
    const [loadingList, setLoadingList] = useState(false);
    const [listError, setListError] = useState('');

    const [formOpen, setFormOpen] = useState(Boolean(initialEditingDepartment));
    const [formMode, setFormMode] = useState(initialEditingDepartment ? 'edit' : 'create');
    const [editingId, setEditingId] = useState(initialEditingDepartment?.id ?? null);
    const [formValues, setFormValues] = useState(mapDepartmentToForm(initialEditingDepartment, defaultBranchId));
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

    const csrfConfig = useMemo(() => (
        payload.csrfToken
            ? { headers: { 'X-CSRF-TOKEN': payload.csrfToken } }
            : undefined
    ), [payload.csrfToken]);

    const fetchDepartments = useCallback(async ({ page, q, status, branchId } = {}) => {
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
                    branch_id: branchId ?? filters.branch_id,
                    page: page ?? meta.currentPage ?? 1,
                    per_page: perPageRef.current,
                },
            });

            if (requestCounter.current !== requestId) {
                return;
            }

            setDepartments(data?.data ?? []);
            setMeta(data?.meta ?? initialMeta);
        } catch (_error) {
            if (requestCounter.current !== requestId) {
                return;
            }
            setListError('Unable to load departments right now. Please retry.');
        } finally {
            if (requestCounter.current === requestId) {
                setLoadingList(false);
            }
        }
    }, [filters.branch_id, filters.q, filters.status, initialMeta, meta.currentPage, routes.list]);

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

        fetchDepartments({
            page: 1,
            q: filters.q,
            status: filters.status,
            branchId: filters.branch_id,
        });
    }, [fetchDepartments, filters.branch_id, filters.q, filters.status]);

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
    }, [editingId, formMode, formOpen]);

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

    const closeForm = () => {
        setFormOpen(false);
        setFormMode('create');
        setEditingId(null);
        setFormValues({
            ...EMPTY_FORM,
            branch_id: defaultBranchId,
            is_active: true,
        });
        setFormErrors({});
        clearEditQueryParam();
    };

    const openCreateForm = () => {
        setFormMode('create');
        setEditingId(null);
        setFormValues({
            ...EMPTY_FORM,
            branch_id: defaultBranchId,
            is_active: true,
        });
        setFormErrors({});
        setFormOpen(true);
        clearEditQueryParam();
    };

    const openEditForm = (department) => {
        setFormMode('edit');
        setEditingId(department.id);
        setFormValues(mapDepartmentToForm(department, defaultBranchId));
        setFormErrors({});
        setFormOpen(true);
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

        fetchDepartments({
            page,
            q: filters.q,
            status: filters.status,
            branchId: filters.branch_id,
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
                response = await axios.put(buildDepartmentUrl(routes.updateTemplate, editingId), submitPayload, csrfConfig);
            } else {
                response = await axios.post(routes.create, submitPayload, csrfConfig);
            }

            setStatusMessage(response?.data?.message ?? (formMode === 'edit'
                ? 'Department updated successfully.'
                : 'Department created successfully.'));
            closeForm();
            fetchDepartments({
                page: formMode === 'edit' ? meta.currentPage : 1,
                q: filters.q,
                status: filters.status,
                branchId: filters.branch_id,
            });
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setFormErrors(mapServerErrors(error.response.data.errors));
            } else {
                setErrorMessage(error.response?.data?.message ?? 'Unable to save department right now.');
            }
        } finally {
            setSavingForm(false);
        }
    };

    const askDelete = (department) => {
        setDeleteTarget(department);
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
            const { data } = await axios.delete(buildDepartmentUrl(routes.deleteTemplate, deleteTarget.id), csrfConfig);
            setStatusMessage(data?.message ?? 'Department deleted successfully.');

            if (editingId === deleteTarget.id) {
                closeForm();
            }

            const nextPage = departments.length === 1 && meta.currentPage > 1
                ? meta.currentPage - 1
                : meta.currentPage;

            await fetchDepartments({
                page: nextPage,
                q: filters.q,
                status: filters.status,
                branchId: filters.branch_id,
            });
            setDeleteTarget(null);
        } catch (error) {
            setErrorMessage(error.response?.data?.message ?? 'Unable to delete this department.');
        } finally {
            setDeleteBusy(false);
        }
    };

    return (
        <div className="space-y-5">
            <section className="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 className="text-xl font-extrabold">Departments</h2>
                    <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                        Configure branch-linked departments for employees and operations.
                    </p>
                </div>
                <button
                    type="button"
                    className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60 disabled:cursor-not-allowed"
                    style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                    onClick={openCreateForm}
                    disabled={branches.length === 0}
                >
                    + Create Department
                </button>
            </section>

            {branches.length === 0 ? (
                <section className="hrm-modern-surface rounded-2xl p-4">
                    <p className="text-sm font-semibold text-amber-700">
                        Create at least one branch before adding departments.
                    </p>
                </section>
            ) : null}

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
                    maxHeight: formOpen ? '1200px' : '0px',
                    opacity: formOpen ? 1 : 0,
                    transform: formOpen ? 'translateY(0)' : 'translateY(-8px)',
                    transition: 'max-height 360ms ease, opacity 260ms ease, transform 260ms ease',
                    pointerEvents: formOpen ? 'auto' : 'none',
                }}
            >
                <article className="hrm-modern-surface rounded-2xl p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-extrabold">{formMode === 'edit' ? 'Update Department' : 'Create Department'}</h3>
                            <p className="text-sm mt-1" style={{ color: 'var(--hr-text-muted)' }}>
                                Assign department identity, branch mapping, and active status.
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
                        <DepartmentField
                            label="Department Name"
                            name="name"
                            value={formValues.name}
                            onChange={onFieldChange}
                            error={formErrors.name}
                            placeholder="Engineering"
                            required
                        />
                        <DepartmentField
                            label="Department Code"
                            name="code"
                            value={formValues.code}
                            onChange={onFieldChange}
                            error={formErrors.code}
                            placeholder="ENG"
                            required
                        />

                        <div className="flex flex-col gap-2">
                            <label htmlFor="branch_id" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Branch *
                            </label>
                            <select
                                id="branch_id"
                                name="branch_id"
                                className={textInputClassName}
                                style={{ borderColor: formErrors.branch_id ? '#f87171' : 'var(--hr-line)' }}
                                value={formValues.branch_id}
                                onChange={(event) => onFieldChange('branch_id', event.target.value)}
                            >
                                <option value="">Select branch</option>
                                {branches.map((branch) => (
                                    <option key={branch.id} value={String(branch.id)}>
                                        {branch.name}
                                    </option>
                                ))}
                            </select>
                            {formErrors.branch_id ? <p className="text-xs text-red-500">{formErrors.branch_id}</p> : null}
                        </div>

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
                        </div>

                        <div className="flex flex-col gap-2 md:col-span-2">
                            <label htmlFor="description" className="text-xs font-semibold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Description
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows={4}
                                className={`${textInputClassName} resize-y`}
                                style={{ borderColor: formErrors.description ? '#f87171' : 'var(--hr-line)' }}
                                placeholder="Department purpose and responsibilities"
                                value={formValues.description}
                                onChange={(event) => onFieldChange('description', event.target.value)}
                            />
                            {formErrors.description ? <p className="text-xs text-red-500">{formErrors.description}</p> : null}
                        </div>

                        <div className="md:col-span-2 flex items-center gap-3 pt-1">
                            <button
                                type="submit"
                                className="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                                style={{ background: 'linear-gradient(120deg, #0f766e, #0ea5a4)' }}
                                disabled={savingForm}
                            >
                                {savingForm
                                    ? (formMode === 'edit' ? 'Updating...' : 'Creating...')
                                    : (formMode === 'edit' ? 'Update Department' : 'Create Department')}
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
                                placeholder="Search by name, code, branch, description..."
                                className="bg-transparent text-sm min-w-[220px] focus:outline-none"
                            />
                        </div>
                        <select
                            className="rounded-xl border px-3 py-2.5 text-sm bg-transparent"
                            style={{ borderColor: 'var(--hr-line)' }}
                            value={filters.branch_id}
                            onChange={(event) => {
                                const nextBranchId = event.target.value;
                                setFilters((prev) => ({
                                    ...prev,
                                    branch_id: nextBranchId,
                                }));
                            }}
                        >
                            <option value="">All Branches</option>
                            {branches.map((branch) => (
                                <option key={branch.id} value={String(branch.id)}>
                                    {branch.name}
                                </option>
                            ))}
                        </select>
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
                                onClick={() => fetchDepartments({
                                    page: meta.currentPage,
                                    q: filters.q,
                                    status: filters.status,
                                    branchId: filters.branch_id,
                                })}
                            >
                                Retry
                            </button>
                        </div>
                    </div>
                ) : null}

                <div className="mt-4 overflow-x-auto">
                    <table className="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr className="border-b text-left" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                                <th className="py-2.5 px-2 font-semibold">Name</th>
                                <th className="py-2.5 px-2 font-semibold">Code</th>
                                <th className="py-2.5 px-2 font-semibold">Branch</th>
                                <th className="py-2.5 px-2 font-semibold">Description</th>
                                <th className="py-2.5 px-2 font-semibold">Status</th>
                                <th className="py-2.5 px-2 font-semibold">Created Date</th>
                                <th className="py-2.5 px-2 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loadingList ? (
                                <tr>
                                    <td colSpan={7} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                        Loading departments...
                                    </td>
                                </tr>
                            ) : null}

                            {!loadingList && departments.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="py-8 px-2 text-center" style={{ color: 'var(--hr-text-muted)' }}>
                                        No department records found.
                                    </td>
                                </tr>
                            ) : null}

                            {!loadingList && departments.map((department) => (
                                <tr key={department.id} className="border-b" style={{ borderColor: 'var(--hr-line)' }}>
                                    <td className="py-3 px-2 font-semibold">{department.name}</td>
                                    <td className="py-3 px-2">{department.code || 'N/A'}</td>
                                    <td className="py-3 px-2">{department.branchName || 'N/A'}</td>
                                    <td className="py-3 px-2 max-w-[280px] truncate" title={department.description || 'N/A'}>
                                        {department.descriptionShort || 'N/A'}
                                    </td>
                                    <td className="py-3 px-2">
                                        <span
                                            className="text-[11px] font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1"
                                            style={statusBadgeStyles(Boolean(department.is_active), isDarkMode)}
                                        >
                                            {department.statusLabel || (department.is_active ? 'Active' : 'Inactive')}
                                        </span>
                                    </td>
                                    <td className="py-3 px-2">{department.createdDateLabel || 'N/A'}</td>
                                    <td className="py-3 px-2">
                                        <div className="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border"
                                                style={{ borderColor: 'var(--hr-line)' }}
                                                onClick={() => openEditForm(department)}
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                className="rounded-lg px-2.5 py-1.5 text-xs font-semibold border text-red-600"
                                                style={{ borderColor: 'rgb(239 68 68 / 0.32)' }}
                                                onClick={() => askDelete(department)}
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
                    <h3 className="text-lg font-bold">Delete Department</h3>
                    <p className="mt-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        {deleteTarget
                            ? `Are you sure you want to delete "${deleteTarget.name}"? This action cannot be undone.`
                            : 'Confirm department deletion.'}
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

export function mountDepartmentsPageApp() {
    const rootElement = document.getElementById('departments-page-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        return;
    }

    createRoot(rootElement).render(<DepartmentsPageApp payload={payload} />);
}
