import React, { useEffect, useMemo, useState } from 'react';
import { payrollApi } from '../api';
import {
    ConfirmModal,
    GlobalFilterBar,
    HorizontalStepper,
    InfoCard,
    SectionHeader,
    StatusBadge,
    TableEmptyState,
    formatCount,
    formatDateTime,
    formatMoney,
    useDebouncedValue,
} from '../shared/ui';

const STEPS = [
    { id: 1, title: 'Select Month' },
    { id: 2, title: 'Preview & Generate' },
    { id: 3, title: 'Approve Payroll' },
    { id: 4, title: 'Pay & Lock' },
];

const APPROVED_STATUSES = ['processed', 'paid'];
const MONTH_OPTIONS = [
    { value: '01', label: 'January' },
    { value: '02', label: 'February' },
    { value: '03', label: 'March' },
    { value: '04', label: 'April' },
    { value: '05', label: 'May' },
    { value: '06', label: 'June' },
    { value: '07', label: 'July' },
    { value: '08', label: 'August' },
    { value: '09', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const normalizeMonth = (value) => {
    const raw = String(value || '').trim();
    return /^\d{4}-(0[1-9]|1[0-2])$/.test(raw) ? raw : '';
};

const currentMonthValue = () => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
};

const toWorkflowPayload = (filters, payrollMonth) => ({
    payroll_month: payrollMonth,
    branch_id: filters.branchId || '',
    department_id: filters.departmentId || '',
    employee_id: filters.employeeId || '',
});

const canApproveStatus = (status) => ['draft', 'failed'].includes(String(status));

export function ProcessingPage({
    urls,
    csrfToken,
    filters,
    onFilterChange = () => {},
    onClearFilters = () => {},
    permissions,
    initialAlert = '',
}) {
    const [activeStep, setActiveStep] = useState(1);
    const [completedStep, setCompletedStep] = useState(0);
    const [payrollMonth, setPayrollMonth] = useState(normalizeMonth(filters.payrollMonth || '') || currentMonthValue());
    const [monthConfirmed, setMonthConfirmed] = useState(false);

    const [overview, setOverview] = useState(null);
    const [preview, setPreview] = useState(null);

    const [loadingOverview, setLoadingOverview] = useState(false);
    const [loadingPreview, setLoadingPreview] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState(() => {
        const mapped = {
            failed: 'failed',
            pending_approvals: 'generated',
        };

        return mapped[String(initialAlert)] || '';
    });
    const [selectedIds, setSelectedIds] = useState([]);

    const [paymentMethod, setPaymentMethod] = useState('bank_transfer');
    const [paymentReference, setPaymentReference] = useState('');
    const [notes, setNotes] = useState('');
    const [confirmLock, setConfirmLock] = useState(false);

    const [approveConfirmOpen, setApproveConfirmOpen] = useState(false);
    const [payConfirmOpen, setPayConfirmOpen] = useState(false);
    const [unlockReason, setUnlockReason] = useState('');

    const [submitting, setSubmitting] = useState(false);

    const debouncedSearch = useDebouncedValue(search, 300);
    const fallbackMonth = useMemo(() => currentMonthValue(), []);

    const selectedYear = Number.parseInt((payrollMonth || fallbackMonth).slice(0, 4), 10);
    const selectedMonthNumber = (payrollMonth || fallbackMonth).slice(5, 7);
    const currentYear = new Date().getFullYear();

    const yearOptions = useMemo(() => {
        const safeSelectedYear = Number.isNaN(selectedYear) ? currentYear : selectedYear;
        const startYear = Math.min(currentYear - 4, safeSelectedYear - 1);
        const endYear = Math.max(currentYear + 4, safeSelectedYear + 1);

        const years = [];
        for (let year = startYear; year <= endYear; year += 1) {
            years.push(String(year));
        }

        return years;
    }, [currentYear, selectedYear]);

    const payrollMonthLabel = useMemo(() => {
        const normalized = normalizeMonth(payrollMonth || '');
        if (!normalized) {
            return 'Payroll Month';
        }

        const [year, month] = normalized.split('-');
        const monthLabel = MONTH_OPTIONS.find((item) => item.value === month)?.label;

        return monthLabel ? `${monthLabel} ${year}` : normalized;
    }, [payrollMonth]);

    const workflowFilters = useMemo(() => toWorkflowPayload(filters, payrollMonth), [filters, payrollMonth]);
    const workflowStatus = String(overview?.header?.status || 'draft').toLowerCase();
    const stepInstructions = {
        1: 'Select payroll month to start the workflow.',
        2: 'Preview calculation and generate payroll records.',
        3: 'Review generated records and approve payroll.',
        4: 'Finalize payout and lock payroll for the month.',
    };
    const hasGeneratedRecords = Array.isArray(overview?.records) && overview.records.length > 0;
    const hasGeneratedStatus = ['generated', 'approved', 'processed', 'paid', 'failed'].includes(workflowStatus);
    const showPayrollHeader = monthConfirmed && (hasGeneratedRecords || hasGeneratedStatus);
    const maxAccessibleStep = Math.min(4, Math.max(1, completedStep + 1));

    const loadOverview = ({ syncWorkflow = false } = {}) => {
        if (!payrollMonth) {
            return Promise.resolve();
        }

        setLoadingOverview(true);
        setError('');

        return payrollApi.getWorkflowOverview(urls.workflowOverview, workflowFilters)
            .then((data) => {
                setOverview(data ?? null);

                if (!syncWorkflow) {
                    return data;
                }

                const records = Array.isArray(data?.records) ? data.records : [];
                const allApprovedRecords = records.length > 0 && records.every((item) => APPROVED_STATUSES.includes(String(item.status)));
                const anyGenerated = records.length > 0;

                if (data?.header?.locked) {
                    setCompletedStep(4);
                    setActiveStep(4);
                    return data;
                }

                if (allApprovedRecords) {
                    setCompletedStep(3);
                    setActiveStep(3);
                    return data;
                }

                if (anyGenerated) {
                    setCompletedStep(2);
                    setActiveStep(3);
                    return data;
                }

                setCompletedStep(1);
                setActiveStep(2);
                return data;
            })
            .catch((apiError) => {
                const message = apiError?.response?.data?.message || 'Unable to load payroll processing overview.';
                setError(String(message));
                setOverview(null);
                return null;
            })
            .finally(() => {
                setLoadingOverview(false);
            });
    };

    useEffect(() => {
        loadOverview({ syncWorkflow: false });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [workflowFilters.branch_id, workflowFilters.department_id, workflowFilters.employee_id, workflowFilters.payroll_month]);

    useEffect(() => {
        setPreview(null);
    }, [workflowFilters.branch_id, workflowFilters.department_id, workflowFilters.employee_id]);

    useEffect(() => {
        if (monthConfirmed && String(initialAlert) === 'not_generated') {
            setCompletedStep((prev) => Math.max(prev, 1));
            setActiveStep(2);
        }
    }, [initialAlert, monthConfirmed]);

    useEffect(() => {
        setActiveStep(1);
        setCompletedStep(0);
        setMonthConfirmed(false);
        setPreview(null);
        setSelectedIds([]);
        setConfirmLock(false);
        setSearch('');
    }, [payrollMonth]);

    const onContinueStep1 = async () => {
        if (!payrollMonth) {
            setError('Please select payroll month.');
            return;
        }

        setError('');
        setSuccess('');
        const nextOverview = await loadOverview({ syncWorkflow: true });

        if (nextOverview?.header?.locked) {
            setError('Selected payroll month is already closed and locked.');
            setMonthConfirmed(false);
            setActiveStep(1);
            setCompletedStep(0);
            return;
        }

        setMonthConfirmed(true);

        if ((Array.isArray(nextOverview?.records) ? nextOverview.records : []).length > 0) {
            setSuccess('Payroll already generated for this month. Continue from approval.');
        }
    };

    const onPreview = () => {
        setLoadingPreview(true);
        setError('');

        payrollApi.previewWorkflow(urls.workflowPreviewBatch, workflowFilters, csrfToken)
            .then((data) => {
                setPreview(data ?? null);
                setSuccess(data?.warning ? String(data.warning) : 'Preview generated successfully.');
            })
            .catch((apiError) => {
                const message = apiError?.response?.data?.message || 'Unable to preview payroll calculation.';
                setError(String(message));
                setPreview(null);
            })
            .finally(() => {
                setLoadingPreview(false);
            });
    };

    const onGenerate = () => {
        if (!permissions.canGenerate) {
            setError('You are not allowed to generate payroll.');
            return;
        }

        setSubmitting(true);
        setError('');
        setSuccess('');

        payrollApi.generateWorkflow(urls.workflowGenerateBatch, { ...workflowFilters, notes }, csrfToken)
            .then((data) => {
                setSuccess(String(data?.message || 'Payroll generated successfully.'));
                setCompletedStep((prev) => Math.max(prev, 2));
                setActiveStep(3);
                return loadOverview({ syncWorkflow: true });
            })
            .catch((apiError) => {
                const message = apiError?.response?.data?.message || 'Unable to generate payroll.';
                setError(String(message));
            })
            .finally(() => {
                setSubmitting(false);
            });
    };

    const filteredRecords = useMemo(() => {
        const records = Array.isArray(overview?.records) ? overview.records : [];

        return records.filter((item) => {
            if (statusFilter && String(item.uiStatus || item.status) !== statusFilter) {
                return false;
            }

            if (debouncedSearch) {
                const haystack = `${item.employeeName || ''} ${item.department || ''}`.toLowerCase();
                if (!haystack.includes(debouncedSearch.toLowerCase())) {
                    return false;
                }
            }

            return true;
        });
    }, [overview?.records, statusFilter, debouncedSearch]);

    const tableTotals = useMemo(() => ({
        gross: filteredRecords.reduce((acc, row) => acc + Number(row.gross || 0), 0),
        deductions: filteredRecords.reduce((acc, row) => acc + Number(row.deductions || 0), 0),
        net: filteredRecords.reduce((acc, row) => acc + Number(row.net || 0), 0),
    }), [filteredRecords]);

    const approveCandidates = useMemo(
        () => filteredRecords.filter((item) => canApproveStatus(item.status)),
        [filteredRecords],
    );

    const allApproved = useMemo(() => {
        const records = Array.isArray(overview?.records) ? overview.records : [];

        return records.length > 0 && records.every((item) => APPROVED_STATUSES.includes(String(item.status)));
    }, [overview?.records]);

    const isLocked = Boolean(overview?.header?.locked);

    useEffect(() => {
        if (monthConfirmed && allApproved && !isLocked) {
            setCompletedStep((prev) => Math.max(prev, 3));
        }
    }, [allApproved, isLocked, monthConfirmed]);

    const toggleSelect = (id) => {
        setSelectedIds((prev) => (prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]));
    };

    const toggleSelectAll = () => {
        const candidateIds = approveCandidates.map((item) => item.id);
        const allSelected = candidateIds.length > 0 && candidateIds.every((id) => selectedIds.includes(id));

        if (allSelected) {
            setSelectedIds((prev) => prev.filter((id) => !candidateIds.includes(id)));
            return;
        }

        setSelectedIds((prev) => Array.from(new Set([...prev, ...candidateIds])));
    };

    const onApproveSelected = () => {
        if (!permissions.canApprove) {
            setError('You are not allowed to approve payroll.');
            return;
        }

        setSubmitting(true);
        setError('');
        setSuccess('');

        payrollApi.approveWorkflow(urls.workflowApproveBatch, {
            ...workflowFilters,
            payroll_ids: selectedIds,
        }, csrfToken)
            .then((data) => {
                setSuccess(String(data?.message || 'Selected payroll records approved.'));
                setSelectedIds([]);
                return loadOverview({ syncWorkflow: true });
            })
            .catch((apiError) => {
                const message = apiError?.response?.data?.message || 'Unable to approve selected payroll records.';
                setError(String(message));
            })
            .finally(() => {
                setSubmitting(false);
                setApproveConfirmOpen(false);
            });
    };

    const onPayAndClose = () => {
        if (!permissions.canMarkPaid) {
            setError('You are not allowed to mark payroll as paid.');
            return;
        }

        if (!allApproved) {
            setError('All payroll records must be approved before payment.');
            return;
        }

        if (!confirmLock) {
            setError('Please confirm payroll lock before marking paid.');
            return;
        }

        setSubmitting(true);
        setError('');
        setSuccess('');

        payrollApi.payWorkflow(urls.workflowPayClose, {
            ...workflowFilters,
            payment_method: paymentMethod,
            payment_reference: paymentReference,
            notes,
            confirm_lock: true,
        }, csrfToken)
            .then((data) => {
                setSuccess(String(data?.message || 'Payroll marked as paid and locked.'));
                setCompletedStep(4);
                setActiveStep(4);
                return loadOverview({ syncWorkflow: true });
            })
            .catch((apiError) => {
                const message = apiError?.response?.data?.message || 'Unable to mark payroll as paid.';
                setError(String(message));
            })
            .finally(() => {
                setSubmitting(false);
                setPayConfirmOpen(false);
            });
    };

    const onUnlock = () => {
        if (!permissions.canUnlock) {
            setError('Only super admin can unlock payroll month.');
            return;
        }

        setSubmitting(true);
        setError('');
        setSuccess('');

        payrollApi.unlockWorkflow(urls.workflowUnlock, {
            payroll_month: payrollMonth,
            unlock_reason: unlockReason,
        }, csrfToken)
            .then((data) => {
                setSuccess(String(data?.message || 'Payroll month unlocked successfully.'));
                setMonthConfirmed(false);
                setActiveStep(1);
                setCompletedStep(0);
                return loadOverview({ syncWorkflow: false });
            })
            .catch((apiError) => {
                const message = apiError?.response?.data?.message || 'Unable to unlock payroll month.';
                setError(String(message));
            })
            .finally(() => {
                setSubmitting(false);
            });
    };

    const exportCsvUrl = useMemo(() => {
        const params = new URLSearchParams();
        params.set('payroll_month', payrollMonth || '');
        if (filters.employeeId) {
            params.set('employee_id', filters.employeeId);
        }
        if (statusFilter) {
            params.set('status', statusFilter);
        }
        if (debouncedSearch) {
            params.set('q', debouncedSearch);
        }

        return `${urls.directoryExportCsv}?${params.toString()}`;
    }, [urls.directoryExportCsv, payrollMonth, filters.employeeId, statusFilter, debouncedSearch]);

    return (
        <div className="space-y-6">
            <section className="ui-section">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.1em]" style={{ color: 'var(--hr-text-muted)' }}>Payroll Processing</p>
                        <h3 className="mt-1 text-xl font-extrabold">{payrollMonthLabel}</h3>
                        <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>{stepInstructions[activeStep]}</p>
                    </div>
                    <StatusBadge status={overview?.header?.status || 'draft'} />
                </div>

                {error ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}
                {success ? <p className="mt-3 text-sm text-green-700">{success}</p> : null}

                <HorizontalStepper
                    steps={STEPS}
                    activeStep={activeStep}
                    completedStep={completedStep}
                    maxAccessibleStep={maxAccessibleStep}
                    onStepChange={setActiveStep}
                />
            </section>

            {showPayrollHeader ? (
                <section className="ui-section">
                    <SectionHeader title="Payroll Header" subtitle="Workflow summary after payroll generation." />
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                        <InfoCard label="Payroll Month" value={overview?.header?.payrollMonth || payrollMonth} icon="calendar" />
                        <InfoCard label="Status" value={<StatusBadge status={overview?.header?.status || 'generated'} />} icon="status" />
                        <InfoCard label="Total Employees" value={formatCount(overview?.header?.totalEmployees || 0)} icon="users" />
                        <InfoCard label="Total Net Pay" value={formatMoney(overview?.header?.totalNetPay || 0)} tone="success" icon="money" />
                        <InfoCard label="Last Updated" value={formatDateTime(overview?.header?.lastUpdatedAt)} icon="clock" />
                        <InfoCard label="Locked" value={isLocked ? 'Yes' : 'No'} tone={isLocked ? 'danger' : 'default'} icon="shield" />
                    </div>
                </section>
            ) : null}

            {activeStep === 1 ? (
                <section className="ui-section hrm-step-content">
                    <SectionHeader title="Step 1: Select Month" subtitle="Choose payroll month to continue workflow." />
                    <div className="mt-6 grid gap-4 md:grid-cols-2">
                        <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Payroll Month
                            <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <select
                                    className="ui-select"
                                    value={String(selectedYear)}
                                    onChange={(event) => {
                                        const year = event.target.value;
                                        const month = selectedMonthNumber || fallbackMonth.slice(5, 7);
                                        setPayrollMonth(normalizeMonth(`${year}-${month}`));
                                    }}
                                    disabled={loadingOverview}
                                >
                                    {yearOptions.map((year) => (
                                        <option key={`payroll-year-${year}`} value={year}>{year}</option>
                                    ))}
                                </select>
                                <select
                                    className="ui-select"
                                    value={selectedMonthNumber}
                                    onChange={(event) => {
                                        const month = event.target.value;
                                        const year = Number.isNaN(selectedYear)
                                            ? fallbackMonth.slice(0, 4)
                                            : String(selectedYear);
                                        setPayrollMonth(normalizeMonth(`${year}-${month}`));
                                    }}
                                    disabled={loadingOverview}
                                >
                                    {MONTH_OPTIONS.map((month) => (
                                        <option key={`payroll-month-${month.value}`} value={month.value}>{month.label}</option>
                                    ))}
                                </select>
                            </div>
                        </label>

                        <div className="rounded-xl border px-4 py-3" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Current Status</p>
                            <div className="mt-2"><StatusBadge status={overview?.header?.status || 'draft'} /></div>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-wrap items-center gap-2">
                        <button type="button" className="ui-btn ui-btn-primary" onClick={onContinueStep1} disabled={isLocked || loadingOverview}>
                            {loadingOverview ? 'Checking...' : 'Continue'}
                        </button>
                        {isLocked ? (
                            <p className="text-sm font-semibold text-red-700">Payroll month is closed. Continue is disabled.</p>
                        ) : null}
                    </div>

                    {isLocked && permissions.canUnlock ? (
                        <div className="mt-4 flex flex-wrap gap-2">
                            <input
                                type="text"
                                className="ui-input"
                                placeholder="Unlock reason"
                                value={unlockReason}
                                onChange={(event) => setUnlockReason(event.target.value)}
                            />
                            <button type="button" className="ui-btn ui-btn-ghost" onClick={onUnlock} disabled={submitting}>
                                Unlock Month
                            </button>
                        </div>
                    ) : null}
                </section>
            ) : null}

            {activeStep === 2 ? (
                <>
                    <GlobalFilterBar
                        urls={urls}
                        filters={filters}
                        employee={filters.employee}
                        onChange={onFilterChange}
                        onClear={onClearFilters}
                    />
                    <section className="ui-section hrm-step-content">
                        <SectionHeader title="Step 2: Preview & Generate" subtitle="Review payroll calculation before generation." />

                        <div className="mt-4 flex flex-wrap gap-2">
                            <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setActiveStep(1)}>Back</button>
                            <button type="button" className="ui-btn ui-btn-ghost" onClick={onPreview} disabled={loadingPreview || isLocked}>
                                {loadingPreview ? 'Previewing...' : 'Refresh Preview'}
                            </button>
                            <button type="button" className="ui-btn ui-btn-primary" onClick={onGenerate} disabled={submitting || isLocked || !permissions.canGenerate}>
                                {submitting ? 'Generating...' : 'Generate Payroll'}
                            </button>
                        </div>

                        {preview ? (
                            <>
                                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    <InfoCard label="Total Employees" value={formatCount(preview?.summary?.totalEmployees ?? 0)} icon="users" />
                                    <InfoCard label="Gross Total" value={formatMoney(preview?.summary?.grossTotal ?? 0)} icon="money" />
                                    <InfoCard label="Deduction Total" value={formatMoney(preview?.summary?.deductionTotal ?? 0)} icon="bank" />
                                    <InfoCard label="Net Total" value={formatMoney(preview?.summary?.netTotal ?? 0)} tone="success" icon="chart" />
                                    <InfoCard label="Employees with Errors" value={formatCount(preview?.summary?.employeesWithErrors ?? 0)} tone="danger" icon="warning" />
                                    <InfoCard label="Missing Salary Structure" value={formatCount(preview?.summary?.missingSalaryStructure ?? 0)} tone="warning" icon="shield" />
                                </div>

                                <div className="ui-table-wrap mt-4">
                                    <table className="ui-table">
                                        <thead>
                                            <tr>
                                                <th>Employee Name</th>
                                                <th>Department</th>
                                                <th>Gross</th>
                                                <th>Deductions</th>
                                                <th>Net</th>
                                                <th>Error</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(Array.isArray(preview?.rows) ? preview.rows : []).length === 0 ? (
                                                <TableEmptyState loading={loadingPreview} error={error} colSpan={6} emptyMessage="No preview rows available." />
                                            ) : (preview.rows || []).map((row) => (
                                                <tr key={`preview-row-${row.employeeId}`}>
                                                    <td>{row.employeeName}</td>
                                                    <td>{row.department || 'N/A'}</td>
                                                    <td>{formatMoney(row.gross)}</td>
                                                    <td>{formatMoney(row.deductions)}</td>
                                                    <td>{formatMoney(row.net)}</td>
                                                    <td>{row.error ? <span className="ui-status-chip ui-status-red">{row.error}</span> : <span className="ui-status-chip ui-status-green">OK</span>}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </>
                        ) : (
                            <div className="mt-4 rounded-xl border p-4 text-sm font-semibold" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)', color: 'var(--hr-text-muted)' }}>
                                Run preview to view payroll totals and employee-level calculation output.
                            </div>
                        )}
                    </section>
                </>
            ) : null}

            {activeStep === 3 ? (
                <section className="ui-section hrm-step-content">
                    <SectionHeader title="Step 3: Approve Payroll" subtitle="Approve generated payroll in bulk with status filters." />
                    <div className="mt-4 flex flex-wrap gap-2">
                        <input
                            type="search"
                            className="ui-input"
                            placeholder="Search employee or department"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                        <select className="ui-select" value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                            <option value="">All Status</option>
                            <option value="generated">Generated</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                        </select>
                        <a className="ui-btn ui-btn-ghost" href={exportCsvUrl}>Export CSV</a>
                    </div>

                    <div className="ui-table-wrap mt-4">
                        <table className="ui-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input
                                            type="checkbox"
                                            onChange={toggleSelectAll}
                                            checked={approveCandidates.length > 0 && approveCandidates.every((item) => selectedIds.includes(item.id))}
                                        />
                                    </th>
                                    <th>Employee Name</th>
                                    <th>Department</th>
                                    <th>Gross</th>
                                    <th>Deductions</th>
                                    <th>Net</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredRecords.length === 0 ? (
                                    <TableEmptyState loading={loadingOverview} error={error} colSpan={7} emptyMessage="No payroll records available for approval." />
                                ) : filteredRecords.map((row) => (
                                    <tr key={`approve-row-${row.id}`}>
                                        <td>
                                            <input
                                                type="checkbox"
                                                checked={selectedIds.includes(row.id)}
                                                disabled={!canApproveStatus(row.status)}
                                                onChange={() => toggleSelect(row.id)}
                                            />
                                        </td>
                                        <td>{row.employeeName}</td>
                                        <td>{row.department || 'N/A'}</td>
                                        <td>{formatMoney(row.gross)}</td>
                                        <td>{formatMoney(row.deductions)}</td>
                                        <td>{formatMoney(row.net)}</td>
                                        <td><StatusBadge status={row.uiStatus || row.status} /></td>
                                    </tr>
                                ))}
                            </tbody>
                            {filteredRecords.length > 0 ? (
                                <tfoot>
                                    <tr>
                                        <td></td>
                                        <td className="font-bold" colSpan={2}>Page Totals</td>
                                        <td className="font-bold">{formatMoney(tableTotals.gross)}</td>
                                        <td className="font-bold">{formatMoney(tableTotals.deductions)}</td>
                                        <td className="font-bold">{formatMoney(tableTotals.net)}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            ) : null}
                        </table>
                    </div>

                    <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setActiveStep(2)}>Back</button>
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                className="ui-btn ui-btn-primary"
                                disabled={selectedIds.length === 0 || submitting || !permissions.canApprove}
                                onClick={() => setApproveConfirmOpen(true)}
                            >
                                Approve Selected ({formatCount(selectedIds.length)})
                            </button>
                            <button
                                type="button"
                                className="ui-btn ui-btn-ghost"
                                disabled={!allApproved}
                                onClick={() => setActiveStep(4)}
                            >
                                Continue to Pay
                            </button>
                        </div>
                    </div>
                </section>
            ) : null}

            {activeStep === 4 ? (
                <section className="ui-section hrm-step-content">
                    <SectionHeader title="Step 4: Pay & Lock" subtitle="Final payout confirmation with permanent workflow lock." />
                    <div className="mt-4 grid gap-3 md:grid-cols-3">
                        <InfoCard label="Total Net Amount" value={formatMoney(overview?.header?.totalNetPay || 0)} tone="success" icon="money" />
                        <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Payment Method
                            <select className="ui-select mt-1" value={paymentMethod} onChange={(event) => setPaymentMethod(event.target.value)} disabled={isLocked}>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </label>
                        <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Payment Reference
                            <input className="ui-input mt-1" value={paymentReference} onChange={(event) => setPaymentReference(event.target.value)} disabled={isLocked} />
                        </label>
                    </div>
                    <label className="mt-3 inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={confirmLock} onChange={(event) => setConfirmLock(event.target.checked)} disabled={isLocked} />
                        I confirm payroll payment and accept that this month will be locked.
                    </label>
                    <div className="mt-4 flex gap-2">
                        <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setActiveStep(3)} disabled={isLocked}>Back</button>
                        <button
                            type="button"
                            className="ui-btn ui-btn-primary"
                            disabled={!allApproved || isLocked || submitting || !permissions.canMarkPaid}
                            onClick={() => setPayConfirmOpen(true)}
                        >
                            Mark as Paid
                        </button>
                    </div>
                    {isLocked ? (
                        <p className="mt-3 text-sm font-semibold text-red-700">Payroll is locked after payment. Editing is disabled.</p>
                    ) : null}
                </section>
            ) : null}

            <ConfirmModal
                open={approveConfirmOpen}
                title="Confirm Payroll Approval"
                body="Approve selected payroll records now? You cannot skip required workflow sequence."
                confirmLabel="Approve Selected"
                busy={submitting}
                onCancel={() => setApproveConfirmOpen(false)}
                onConfirm={onApproveSelected}
            />

            <ConfirmModal
                open={payConfirmOpen}
                title="Confirm Mark as Paid"
                body="This will mark payroll as paid and lock the selected payroll month from edits. Continue?"
                confirmLabel="Mark Paid & Lock"
                tone="danger"
                busy={submitting}
                onCancel={() => setPayConfirmOpen(false)}
                onConfirm={onPayAndClose}
            />
        </div>
    );
}
