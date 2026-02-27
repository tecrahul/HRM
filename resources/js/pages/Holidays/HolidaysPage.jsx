import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { AppModalPortal } from '../../components/shared/AppModalPortal';
import { HolidayApi } from '../../services/HolidayApi';
import { useHolidays } from '../../hooks/useHolidays';
import { HolidaysHeader } from '../../components/holidays/HolidaysHeader';
import { HolidaysInfoCards } from '../../components/holidays/HolidaysInfoCards';
import { HolidaysFilters } from '../../components/holidays/HolidaysFilters';
import { HolidayForm } from '../../components/holidays/HolidayForm';
import { HolidaysTable } from '../../components/holidays/HolidaysTable';
import { HolidayCalendarHeader } from '../../components/holidays/HolidayCalendarHeader';
import { HolidayCalendarView } from '../../components/holidays/HolidayCalendarView';
import { HolidayDetailModal } from '../../components/holidays/HolidayDetailModal';

const EMPTY_FORM = {
    name: '',
    holiday_date: '',
    end_date: '',
    branch_id: '',
    holiday_type: 'public',
    description: '',
    is_active: true,
};

const parsePayload = (root) => {
    if (!root) {
        return null;
    }

    const raw = root.dataset.payload;
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return null;
    }
};

const readDarkMode = () => (
    typeof document !== 'undefined'
        && document.documentElement.classList.contains('dark')
);

const mapServerErrors = (errors = {}) => Object.entries(errors).reduce((acc, [field, messages]) => ({
    ...acc,
    [field]: Array.isArray(messages) ? messages[0] : messages,
}), {});

const mapHolidayToForm = (holiday) => ({
    ...EMPTY_FORM,
    name: holiday?.name ?? '',
    holiday_date: holiday?.holiday_date ?? '',
    end_date: holiday?.end_date ?? '',
    branch_id: holiday?.branch_id ? String(holiday.branch_id) : '',
    holiday_type: holiday?.holiday_type ?? 'public',
    description: holiday?.description ?? '',
    is_active: Boolean(holiday?.is_active),
});

const buildSubmitPayload = (values) => ({
    name: values.name.trim(),
    holiday_date: values.holiday_date,
    end_date: values.end_date || null,
    branch_id: values.branch_id ? Number.parseInt(values.branch_id, 10) : null,
    holiday_type: values.holiday_type,
    description: values.description.trim(),
    is_active: values.is_active ? 1 : 0,
});

const clientValidate = (values) => {
    const errors = {};

    if (!values.name.trim()) {
        errors.name = 'Holiday name is required.';
    }

    if (!values.holiday_date) {
        errors.holiday_date = 'Holiday date is required.';
    }

    if (values.end_date && values.holiday_date && values.end_date < values.holiday_date) {
        errors.end_date = 'End date must be on or after holiday date.';
    }

    if (!values.holiday_type) {
        errors.holiday_type = 'Holiday type is required.';
    }

    return errors;
};

function ToastStack({ toasts, onDismiss }) {
    if (toasts.length === 0) {
        return null;
    }

    return (
        <div className="fixed top-4 right-4 z-[2300] flex flex-col gap-2">
            {toasts.map((toast) => (
                <button
                    key={toast.id}
                    type="button"
                    className="rounded-xl border px-4 py-3 text-sm text-left shadow-lg"
                    style={{
                        borderColor: toast.tone === 'danger' ? 'rgb(248 113 113 / 0.45)' : 'rgb(16 185 129 / 0.45)',
                        background: toast.tone === 'danger'
                            ? 'linear-gradient(120deg, rgb(254 226 226 / 0.95), rgb(254 242 242 / 0.95))'
                            : 'linear-gradient(120deg, rgb(220 252 231 / 0.95), rgb(236 253 245 / 0.95))',
                    }}
                    onClick={() => onDismiss(toast.id)}
                >
                    {toast.message}
                </button>
            ))}
        </div>
    );
}

function HolidaysPage({ payload }) {
    const formRef = useRef(null);

    const api = useMemo(() => new HolidayApi({
        routes: payload.routes ?? {},
        csrfToken: payload.csrfToken,
    }), [payload.csrfToken, payload.routes]);

    const {
        holidays,
        meta,
        stats,
        filters,
        loading,
        initialLoading,
        submitting,
        error,
        fetchHolidays,
        createHoliday,
        updateHoliday,
        deleteHoliday,
    } = useHolidays(api, payload);

    const branches = Array.isArray(payload.branches) ? payload.branches : [];
    const defaults = payload.defaults ?? {
        q: '',
        year: new Date().getFullYear(),
        branch_id: '',
        holiday_type: 'all',
        status: 'all',
        sort: 'date_asc',
    };

    const capabilities = payload.capabilities ?? {
        canCreate: false,
        canEdit: false,
        canDelete: false,
    };

    const [formOpen, setFormOpen] = useState(Boolean(payload.editingHoliday));
    const [formMode, setFormMode] = useState(payload.editingHoliday ? 'edit' : 'create');
    const [editingHoliday, setEditingHoliday] = useState(payload.editingHoliday ?? null);
    const [formValues, setFormValues] = useState(mapHolidayToForm(payload.editingHoliday));
    const [formErrors, setFormErrors] = useState({});
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDarkMode, setIsDarkMode] = useState(readDarkMode);

    // View state: 'list' | 'calendar'
    const [view, setView] = useState(() => {
        try {
            const v = localStorage.getItem('holidays.view') || 'list';
            return v === 'calendar' ? 'calendar' : 'list';
        } catch (_e) {
            return 'list';
        }
    });

    // Calendar detail modal
    const [detailOpen, setDetailOpen] = useState(false);
    const [detailHoliday, setDetailHoliday] = useState(null);

    // Calendar cursor synchronized with filters
    const initialMonth = Number.parseInt(String(filters?.month || new Date().getMonth() + 1), 10);
    const [calendarYear, setCalendarYear] = useState(filters.year || new Date().getFullYear());
    const [calendarMonth, setCalendarMonth] = useState(initialMonth);

    const [toasts, setToasts] = useState(() => {
        const initial = [];
        if (payload.flash?.status) {
            initial.push({ id: Date.now(), tone: 'success', message: payload.flash.status });
        }
        if (payload.flash?.error) {
            initial.push({ id: Date.now() + 1, tone: 'danger', message: payload.flash.error });
        }
        return initial;
    });

    // Open create form when navigated with action=create and prefill date
    useEffect(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const action = String(params.get('action') || '');
            if (Boolean(capabilities.canCreate) && action === 'create') {
                const dateParam = String(params.get('holiday_date') || params.get('start_date') || '');
                const parsed = dateParam ? new Date(dateParam) : null;
                const isValid = parsed && !Number.isNaN(parsed.getTime());

                setFormMode('create');
                setEditingHoliday(null);
                setFormValues({
                    ...EMPTY_FORM,
                    holiday_date: isValid ? dateParam : '',
                    is_active: true,
                });
                setFormErrors({});
                setFormOpen(true);
            }
        } catch (_e) {}
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [capabilities.canCreate]);

    useEffect(() => {
        if (!formOpen) {
            return;
        }

        const timer = window.setTimeout(() => {
            formRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);

        return () => window.clearTimeout(timer);
    }, [formOpen, editingHoliday?.id]);

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

    // Ensure correct data shape when opening directly in calendar view
    useEffect(() => {
        if (view !== 'calendar') return;
        const m = Number.parseInt(String(filters.month || new Date().getMonth() + 1), 10);
        const y = filters.year || new Date().getFullYear();
        setCalendarMonth(m);
        setCalendarYear(y);
        // Load calendar data without mutating filter values
        fetchHolidays({ month: String(m), year: y, per_page: 500 }, 1, true, true).catch(() => {});
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [view]);

    const pushToast = (message, tone = 'success') => {
        const id = Date.now() + Math.floor(Math.random() * 1000);
        setToasts((prev) => [...prev, { id, tone, message }]);
        window.setTimeout(() => {
            setToasts((prev) => prev.filter((toast) => toast.id !== id));
        }, 3600);
    };

    const dismissToast = (id) => {
        setToasts((prev) => prev.filter((toast) => toast.id !== id));
    };

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
        setEditingHoliday(null);
        setFormValues({ ...EMPTY_FORM, is_active: true });
        setFormErrors({});
        setFormOpen(true);
        clearEditQueryParam();
    };

    const openEditForm = (holiday) => {
        setFormMode('edit');
        setEditingHoliday(holiday);
        setFormValues(mapHolidayToForm(holiday));
        setFormErrors({});
        setFormOpen(true);
    };

    const closeForm = () => {
        if (submitting) {
            return;
        }

        setFormOpen(false);
        setFormMode('create');
        setEditingHoliday(null);
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

    const handleApplyFilters = (nextFilters) => {
        // If month changed via filter and in calendar view, sync header cursor
        if (view === 'calendar') {
            const monthNum = Number.parseInt(String(nextFilters.month || calendarMonth), 10) || calendarMonth;
            setCalendarMonth(monthNum);
            setCalendarYear(Number.parseInt(String(nextFilters.year || calendarYear), 10) || calendarYear);
        }

        const payload = view === 'calendar'
            ? { ...nextFilters, per_page: 500 }
            : nextFilters;

        fetchHolidays(payload, 1).catch((apiError) => {
            pushToast(apiError.message || 'Unable to apply filters.', 'danger');
        });
    };

    const handleClearFilters = () => {
        const payload = view === 'calendar' ? { ...defaults, month: String(new Date().getMonth() + 1), per_page: 500 } : defaults;
        if (view === 'calendar') {
            setCalendarMonth(new Date().getMonth() + 1);
            setCalendarYear(new Date().getFullYear());
        }
        fetchHolidays(payload, 1).catch((apiError) => {
            pushToast(apiError.message || 'Unable to clear filters.', 'danger');
        });
    };

    const handleToggleSort = () => {
        const nextSort = filters.sort === 'date_desc' ? 'date_asc' : 'date_desc';
        fetchHolidays({ ...filters, sort: nextSort }, 1).catch((apiError) => {
            pushToast(apiError.message || 'Unable to sort holidays.', 'danger');
        });
    };

    const handlePageChange = (page) => {
        fetchHolidays({}, page).catch((apiError) => {
            pushToast(apiError.message || 'Unable to load page.', 'danger');
        });
    };

    const handleViewChange = (next) => {
        setView(next);
        try { localStorage.setItem('holidays.view', next); } catch (_e) {}
        // When switching to calendar, ensure a month is set and load month-only data
        if (next === 'calendar') {
            const m = Number.parseInt(String(filters.month || new Date().getMonth() + 1), 10);
            const y = filters.year || new Date().getFullYear();
            setCalendarMonth(m);
            setCalendarYear(y);
            // Load calendar data without syncing into filters
            fetchHolidays({ month: String(m), year: y, per_page: 500 }, 1, false, true).catch((apiError) => {
                pushToast(apiError.message || 'Unable to load calendar.', 'danger');
            });
        }
    };

    const gotoMonth = (y, m) => {
        setCalendarYear(y);
        setCalendarMonth(m);
        // Navigate calendar months without mutating filter state
        fetchHolidays({ month: String(m), year: y, per_page: 500 }, 1, false, true).catch((apiError) => {
            pushToast(apiError.message || 'Unable to load month.', 'danger');
        });
    };

    const handlePrevMonth = () => {
        let y = calendarYear;
        let m = calendarMonth - 1;
        if (m < 1) { m = 12; y -= 1; }
        gotoMonth(y, m);
    };

    const handleNextMonth = () => {
        let y = calendarYear;
        let m = calendarMonth + 1;
        if (m > 12) { m = 1; y += 1; }
        gotoMonth(y, m);
    };

    const handleToday = () => {
        const d = new Date();
        gotoMonth(d.getFullYear(), d.getMonth() + 1);
    };

    const openDetail = (holiday) => {
        setDetailHoliday(holiday);
        setDetailOpen(true);
    };
    const closeDetail = () => {
        setDetailOpen(false);
        setDetailHoliday(null);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        const validationErrors = clientValidate(formValues);
        if (Object.keys(validationErrors).length > 0) {
            setFormErrors(validationErrors);
            return;
        }

        setFormErrors({});
        const submitPayload = buildSubmitPayload(formValues);

        try {
            let response;
            if (formMode === 'edit' && editingHoliday) {
                response = await updateHoliday(editingHoliday.id, submitPayload);
            } else {
                response = await createHoliday(submitPayload);
            }

            pushToast(response?.message || (formMode === 'edit' ? 'Holiday updated successfully.' : 'Holiday created successfully.'));
            closeForm();
        } catch (apiError) {
            if (apiError.status === 422) {
                setFormErrors(mapServerErrors(apiError.errors));
            }
            pushToast(apiError.message || 'Unable to save holiday.', 'danger');
        }
    };

    const handleDelete = async () => {
        if (!deleteTarget) {
            return;
        }

        try {
            const response = await deleteHoliday(deleteTarget.id);
            pushToast(response?.message || 'Holiday deleted successfully.');
            setDeleteTarget(null);

            if (editingHoliday?.id === deleteTarget.id) {
                closeForm();
            }
        } catch (apiError) {
            pushToast(apiError.message || 'Unable to delete holiday.', 'danger');
        }
    };

    return (
        <div className="space-y-5">
            <HolidaysHeader
                canCreate={capabilities.canCreate}
                onCreate={openCreateForm}
                disabledCreate={submitting}
                view={view}
                onViewChange={handleViewChange}
            />

            <HolidaysInfoCards stats={stats} />

            {error ? (
                <section className="hrm-modern-surface rounded-2xl p-4">
                    <p className="text-sm font-semibold text-red-600">{error}</p>
                </section>
            ) : null}

            <section
                ref={formRef}
                className="overflow-hidden"
                style={{
                    maxHeight: formOpen ? '1400px' : '0px',
                    opacity: formOpen ? 1 : 0,
                    transform: formOpen ? 'translateY(0)' : 'translateY(-8px)',
                    transition: 'max-height 360ms ease, opacity 260ms ease, transform 260ms ease',
                    pointerEvents: formOpen ? 'auto' : 'none',
                }}
            >
                <HolidayForm
                    mode={formMode}
                    values={formValues}
                    errors={formErrors}
                    branches={branches}
                    isDarkMode={isDarkMode}
                    onFieldChange={onFieldChange}
                    onSubmit={handleSubmit}
                    onCancel={closeForm}
                    onClose={closeForm}
                    saving={submitting}
                />
            </section>

            <HolidaysFilters
                filters={filters}
                defaults={defaults}
                branches={branches}
                yearOptions={payload.yearOptions}
                onApply={handleApplyFilters}
                onClear={handleClearFilters}
                loading={loading || initialLoading}
            />

            {/* Views with smooth transitions */}
            <section
                className="transition-all"
                style={{
                    maxHeight: view === 'list' ? '2000px' : '0px',
                    opacity: view === 'list' ? 1 : 0,
                    transform: view === 'list' ? 'translateY(0)' : 'translateY(-8px)',
                    overflow: 'hidden',
                }}
            >
                <HolidaysTable
                    holidays={holidays}
                    meta={meta}
                    loading={loading || initialLoading}
                    listError={error}
                    sort={filters.sort}
                    isDarkMode={isDarkMode}
                    canEdit={capabilities.canEdit}
                    canDelete={capabilities.canDelete}
                    onToggleSort={handleToggleSort}
                    onEdit={openEditForm}
                    onDelete={(holiday) => setDeleteTarget(holiday)}
                    onRetry={() => fetchHolidays({}, meta.currentPage || 1).catch(() => {})}
                    onPageChange={handlePageChange}
                />
            </section>

            <section
                className="hrm-modern-surface rounded-2xl p-6 overflow-hidden transition-all"
                style={{
                    maxHeight: view === 'calendar' ? '2000px' : '0px',
                    opacity: view === 'calendar' ? 1 : 0,
                    transform: view === 'calendar' ? 'translateY(0)' : 'translateY(-8px)',
                    pointerEvents: view === 'calendar' ? 'auto' : 'none',
                }}
            >
                <HolidayCalendarHeader
                    year={calendarYear}
                    month={calendarMonth}
                    onPrev={handlePrevMonth}
                    onNext={handleNextMonth}
                    onToday={handleToday}
                />
                <HolidayCalendarView
                    holidays={holidays}
                    year={calendarYear}
                    month={calendarMonth}
                    holidayIndexUrl={capabilities.canCreate ? payload.routes?.list : ''}
                    onSelectHoliday={openDetail}
                />
            </section>

            <HolidayDetailModal
                open={detailOpen}
                holiday={detailHoliday}
                canEdit={capabilities.canEdit}
                canDelete={capabilities.canDelete}
                onEdit={(h) => { closeDetail(); openEditForm(h); }}
                onDelete={(h) => { closeDetail(); setDeleteTarget(h); }}
                onClose={closeDetail}
            />

            {deleteTarget ? (
                <AppModalPortal>
                    <div className="fixed inset-0 z-[2200] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center px-4">
                        <div className="w-full max-w-md rounded-2xl border shadow-xl p-6 bg-[var(--hr-surface)]" style={{ borderColor: 'var(--hr-line)' }}>
                            <h4 className="text-base font-extrabold">Delete Holiday</h4>
                            <p className="text-sm mt-2" style={{ color: 'var(--hr-text-muted)' }}>
                                Are you sure you want to delete <span className="font-semibold">{deleteTarget.name}</span>? This action cannot be undone.
                            </p>
                            <div className="mt-6 flex items-center justify-end gap-4">
                                <button
                                    type="button"
                                    className="rounded-xl border px-3 py-2 text-sm font-semibold"
                                    style={{ borderColor: 'var(--hr-line)' }}
                                    onClick={() => setDeleteTarget(null)}
                                    disabled={submitting}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    className="rounded-xl px-3 py-2 text-sm font-semibold text-white"
                                    style={{ background: 'linear-gradient(120deg, #dc2626, #ef4444)' }}
                                    onClick={handleDelete}
                                    disabled={submitting}
                                >
                                    {submitting ? 'Deleting...' : 'Delete'}
                                </button>
                            </div>
                        </div>
                    </div>
                </AppModalPortal>
            ) : null}

            <ToastStack toasts={toasts} onDismiss={dismissToast} />
        </div>
    );
}

export function mountHolidaysPage() {
    const rootElement = document.getElementById('holidays-page-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        return;
    }

    const root = createRoot(rootElement);
    root.render(<HolidaysPage payload={payload} />);
}
