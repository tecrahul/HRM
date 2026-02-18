import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import { EmployeeAutocomplete } from './EmployeeAutocomplete';
import { AppModalPortal } from './shared/AppModalPortal';

const countFormatter = new Intl.NumberFormat();
const moneyFormatter = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const toCount = (value) => countFormatter.format(Number(value ?? 0));
const toMoney = (value) => moneyFormatter.format(Number(value ?? 0));
const toMonthValue = (value) => {
    if (typeof value !== 'string' || value.length < 7) {
        return '';
    }

    return value.slice(0, 7);
};

const statusLabel = (value) => String(value ?? '').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const statusStyle = (status) => {
    if (status === 'paid') {
        return { color: '#166534', background: 'rgb(34 197 94 / 0.16)' };
    }

    if (status === 'approved') {
        return { color: '#1d4ed8', background: 'rgb(59 130 246 / 0.16)' };
    }

    if (status === 'failed') {
        return { color: '#991b1b', background: 'rgb(239 68 68 / 0.16)' };
    }

    return { color: '#475569', background: 'rgb(148 163 184 / 0.2)' };
};

const STATUS_FILTER_OPTIONS = ['generated', 'approved', 'paid', 'failed'];

const statusSortRank = (status) => {
    const ranks = {
        generated: 1,
        approved: 2,
        paid: 3,
        failed: 4,
    };

    return ranks[String(status)] ?? 99;
};

const WORKFLOW_STEPS = [
    { id: 1, label: 'Select Month' },
    { id: 2, label: 'Preview Calculation' },
    { id: 3, label: 'Approve Payroll' },
    { id: 4, label: 'Mark as Paid' },
];

const toPercent = (numerator, denominator) => {
    const safeDenominator = Number(denominator ?? 0);
    if (safeDenominator <= 0) {
        return 0;
    }

    const value = (Number(numerator ?? 0) / safeDenominator) * 100;
    return Math.max(0, Math.min(100, Number(value.toFixed(1))));
};

const parsePayload = (rootElement) => {
    const raw = rootElement.dataset.payload;
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return null;
    }
};

function SectionTitle({ eyebrow, title, subtitle }) {
    return (
        <div>
            {eyebrow ? (
                <p className="text-[11px] font-bold uppercase tracking-[0.12em]" style={{ color: 'var(--hr-text-muted)' }}>
                    {eyebrow}
                </p>
            ) : null}
            <h3 className="mt-1 text-lg font-extrabold" style={{ color: 'var(--hr-text-main)' }}>{title}</h3>
            {subtitle ? (
                <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>{subtitle}</p>
            ) : null}
        </div>
    );
}

function MetricProgressCard({ label, valueLabel, helperText, percent, barColor }) {
    return (
        <article className="rounded-2xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
            <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>{label}</p>
            <p className="mt-2 text-2xl font-extrabold" style={{ color: 'var(--hr-text-main)' }}>{valueLabel}</p>
            <p className="mt-1 text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>{helperText}</p>
            <div className="mt-3 h-2.5 w-full overflow-hidden rounded-full" style={{ background: 'rgb(148 163 184 / 0.2)' }}>
                <span
                    className="block h-full rounded-full"
                    style={{ width: `${percent}%`, background: barColor }}
                />
            </div>
        </article>
    );
}

function AlertCard({ title, body, tone = 'warning', actionHref, actionLabel }) {
    const toneMap = {
        warning: {
            border: 'rgb(245 158 11 / 0.38)',
            background: 'linear-gradient(130deg, rgb(245 158 11 / 0.14), rgb(245 158 11 / 0.04))',
            color: '#92400e',
        },
        success: {
            border: 'rgb(34 197 94 / 0.38)',
            background: 'linear-gradient(130deg, rgb(34 197 94 / 0.14), rgb(34 197 94 / 0.04))',
            color: '#166534',
        },
        danger: {
            border: 'rgb(239 68 68 / 0.38)',
            background: 'linear-gradient(130deg, rgb(239 68 68 / 0.14), rgb(239 68 68 / 0.04))',
            color: '#991b1b',
        },
    };

    const style = toneMap[tone] ?? toneMap.warning;

    return (
        <article className="rounded-2xl border p-4" style={{ borderColor: style.border, background: style.background }}>
            <p className="text-sm font-bold" style={{ color: style.color }}>{title}</p>
            <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-main)' }}>{body}</p>
            {actionHref && actionLabel ? (
                <a href={actionHref} className="mt-3 inline-flex text-xs font-bold" style={{ color: 'var(--hr-accent)' }}>
                    {actionLabel}
                </a>
            ) : null}
        </article>
    );
}

function StatPill({ label, value }) {
    return (
        <div className="rounded-xl border px-3 py-2" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
            <p className="text-[11px] font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>{label}</p>
            <p className="mt-1 text-base font-extrabold" style={{ color: 'var(--hr-text-main)' }}>{value}</p>
        </div>
    );
}

function WorkflowStepIndicator({ activeStep, completedStep }) {
    return (
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-4">
            {WORKFLOW_STEPS.map((step) => {
                const isCompleted = completedStep >= step.id;
                const isActive = activeStep === step.id;

                return (
                    <div
                        key={step.id}
                        className="rounded-xl border px-3 py-2"
                        style={{
                            borderColor: isActive ? 'var(--hr-accent-border)' : 'var(--hr-line)',
                            background: isActive ? 'var(--hr-accent-soft)' : 'var(--hr-surface-strong)',
                        }}
                    >
                        <p className="text-[11px] font-bold uppercase tracking-[0.08em]" style={{ color: isActive ? 'var(--hr-accent)' : 'var(--hr-text-muted)' }}>
                            Step {step.id}
                        </p>
                        <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>{step.label}</p>
                        <p className="mt-1 text-[11px] font-semibold" style={{ color: isCompleted ? '#15803d' : 'var(--hr-text-muted)' }}>
                            {isCompleted ? 'Completed' : isActive ? 'In Progress' : 'Locked'}
                        </p>
                    </div>
                );
            })}
        </div>
    );
}

function ConfirmModal({
    open,
    title,
    description,
    onCancel,
    onConfirm,
    disabled,
    confirmLabel = 'Confirm',
}) {
    return (
        <AppModalPortal open={open} onBackdropClick={disabled ? null : onCancel}>
            <div className="app-modal-panel w-full max-w-md p-5" role="dialog" aria-modal="true">
                <h4 className="text-lg font-extrabold">{title}</h4>
                <p className="mt-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>{description}</p>
                <div className="mt-4 flex items-center justify-end gap-2">
                    <button type="button" onClick={onCancel} className="ui-btn ui-btn-ghost" disabled={disabled}>Cancel</button>
                    <button type="button" onClick={onConfirm} className="ui-btn ui-btn-primary" disabled={disabled}>
                        {disabled ? 'Processing...' : confirmLabel}
                    </button>
                </div>
            </div>
        </AppModalPortal>
    );
}

function AlertTile({ label, count, active, onClick, tone = 'warning' }) {
    const styles = {
        warning: { border: 'rgb(245 158 11 / 0.35)', bg: 'rgb(245 158 11 / 0.08)', color: '#92400e' },
        danger: { border: 'rgb(239 68 68 / 0.35)', bg: 'rgb(239 68 68 / 0.08)', color: '#991b1b' },
        info: { border: 'rgb(59 130 246 / 0.35)', bg: 'rgb(59 130 246 / 0.08)', color: '#1d4ed8' },
    };
    const toneStyle = styles[tone] ?? styles.warning;

    return (
        <button
            type="button"
            onClick={onClick}
            className="w-full rounded-xl border px-3 py-3 text-left transition"
            style={{
                borderColor: active ? 'var(--hr-accent-border)' : toneStyle.border,
                background: active ? 'var(--hr-accent-soft)' : toneStyle.bg,
            }}
        >
            <p className="text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                {label}
            </p>
            <p className="mt-1 text-2xl font-extrabold" style={{ color: toneStyle.color }}>{toCount(count)}</p>
        </button>
    );
}

function AdminPayrollManagement({ payload }) {
    const stats = payload?.stats ?? {};
    const filters = payload?.filters ?? {};
    const initialRecords = Array.isArray(payload?.records) ? payload.records : [];
    const employees = Array.isArray(payload?.employees) ? payload.employees : [];
    const initialStructures = Array.isArray(payload?.structures) ? payload.structures : [];
    const statusOptions = Array.isArray(payload?.statusOptions) ? payload.statusOptions : [];
    const paymentMethodOptions = Array.isArray(payload?.paymentMethodOptions) ? payload.paymentMethodOptions : [];
    const pagination = payload?.pagination ?? {};
    const urls = payload?.urls ?? {};
    const flash = payload?.flash ?? {};
    const validation = payload?.validation ?? {};
    const alerts = payload?.alerts ?? {};
    const permissions = payload?.permissions ?? {};
    const oldInput = payload?.oldInput ?? {};
    const csrfToken = String(payload?.csrfToken ?? '');

    const structureCoverage = toPercent(stats.employeesWithStructure, stats.totalEmployees);
    const payrollGenerationProgress = toPercent(stats.generatedThisMonth, stats.totalEmployees);
    const approvalProgress = toPercent(
        Number(stats.processedThisMonth ?? 0) + Number(stats.paidThisMonth ?? 0),
        stats.generatedThisMonth,
    );
    const payoutProgress = toPercent(stats.paidThisMonth, stats.generatedThisMonth);

    const missingStructureCount = Math.max(0, Number(stats.totalEmployees ?? 0) - Number(stats.employeesWithStructure ?? 0));
    const [records, setRecords] = useState(initialRecords);
    const [structures, setStructures] = useState(initialStructures);
    const [activeAlertFilter, setActiveAlertFilter] = useState('');
    const [tableSearch, setTableSearch] = useState(String(filters.q ?? ''));
    const [tableStatusFilter, setTableStatusFilter] = useState('');
    const [tableSortBy, setTableSortBy] = useState('net');
    const [tableSortDir, setTableSortDir] = useState('desc');
    const [selectedPayrollIds, setSelectedPayrollIds] = useState([]);
    const [tableActionError, setTableActionError] = useState('');
    const [tableActionSuccess, setTableActionSuccess] = useState('');
    const [bulkBusy, setBulkBusy] = useState(false);

    const [selectedStructureUserId, setSelectedStructureUserId] = useState(() => {
        if (structures.length > 0) {
            return String(structures[0].userId);
        }
        if (employees.length > 0) {
            return String(employees[0].id);
        }

        return '';
    });
    const [showStructureEditor, setShowStructureEditor] = useState(false);
    const [structureHistory, setStructureHistory] = useState([]);
    const [historyLoading, setHistoryLoading] = useState(false);
    const [structureSaveBusy, setStructureSaveBusy] = useState(false);
    const [structureError, setStructureError] = useState('');
    const [structureSuccess, setStructureSuccess] = useState('');
    const [structureForm, setStructureForm] = useState({
        basic_salary: '',
        hra: '',
        special_allowance: '',
        bonus: '',
        other_allowance: '',
        pf_deduction: '',
        tax_deduction: '',
        other_deduction: '',
        effective_from: '',
        notes: '',
    });

    const selectedStructure = structures.find((item) => String(item.userId) === String(selectedStructureUserId)) ?? null;
    const selectedStructureEmployee = employees.find((item) => String(item.id) === String(selectedStructureUserId)) ?? null;
    const selectedStructureNet = selectedStructure
        ? Number(selectedStructure.basicSalary || 0)
            + Number(selectedStructure.hra || 0)
            + Number(selectedStructure.specialAllowance || 0)
            + Number(selectedStructure.bonus || 0)
            + Number(selectedStructure.otherAllowance || 0)
            - Number(selectedStructure.pfDeduction || 0)
            - Number(selectedStructure.taxDeduction || 0)
            - Number(selectedStructure.otherDeduction || 0)
        : 0;

    useEffect(() => {
        if (!selectedStructureUserId || !urls.structureHistory) {
            setStructureHistory([]);
            return;
        }

        const endpoint = String(urls.structureHistory).replace('__USER_ID__', String(selectedStructureUserId));
        setHistoryLoading(true);
        axios.get(endpoint)
            .then(({ data }) => {
                setStructureHistory(Array.isArray(data?.history) ? data.history : []);
            })
            .catch(() => {
                setStructureHistory([]);
            })
            .finally(() => {
                setHistoryLoading(false);
            });
    }, [selectedStructureUserId, urls.structureHistory]);

    useEffect(() => {
        if (!selectedStructure) {
            setStructureForm({
                basic_salary: '',
                hra: '',
                special_allowance: '',
                bonus: '',
                other_allowance: '',
                pf_deduction: '',
                tax_deduction: '',
                other_deduction: '',
                effective_from: '',
                notes: '',
            });
            return;
        }

        setStructureForm({
            basic_salary: String(selectedStructure.basicSalary ?? ''),
            hra: String(selectedStructure.hra ?? 0),
            special_allowance: String(selectedStructure.specialAllowance ?? 0),
            bonus: String(selectedStructure.bonus ?? 0),
            other_allowance: String(selectedStructure.otherAllowance ?? 0),
            pf_deduction: String(selectedStructure.pfDeduction ?? 0),
            tax_deduction: String(selectedStructure.taxDeduction ?? 0),
            other_deduction: String(selectedStructure.otherDeduction ?? 0),
            effective_from: selectedStructure.effectiveFrom || '',
            notes: selectedStructure.notes || '',
        });
    }, [selectedStructure]);

    const [workflowState, setWorkflowState] = useState({
        month: toMonthValue(oldInput.payroll_month || filters.payroll_month),
        employeeId: String(oldInput.user_id ?? ''),
        payableDays: oldInput.payable_days ?? '',
        preview: null,
        payroll: null,
        paymentMethod: String(oldInput.payment_method ?? ''),
        paymentReference: String(oldInput.payment_reference ?? ''),
        paymentNotes: String(oldInput.notes ?? ''),
        busy: '',
        error: '',
        success: '',
        showPayConfirm: false,
        activeStep: 1,
    });

    const payrollStatus = String(workflowState.payroll?.status ?? '');
    const isPaidLocked = payrollStatus === 'paid';
    const completedStep = isPaidLocked
        ? 4
        : payrollStatus === 'processed'
            ? 3
            : workflowState.payroll
                ? 2
                : workflowState.preview
                    ? 1
                    : 0;

    const canPreview = workflowState.month !== ''
        && workflowState.employeeId !== ''
        && workflowState.busy === ''
        && Boolean(permissions.canGenerate);
    const canGenerate = workflowState.preview !== null && !isPaidLocked && workflowState.busy === '' && Boolean(permissions.canGenerate);
    const canApprove = workflowState.payroll !== null
        && payrollStatus === 'draft'
        && workflowState.busy === ''
        && Boolean(permissions.canApprove);
    const canOpenPayConfirm = workflowState.payroll !== null
        && payrollStatus === 'processed'
        && workflowState.paymentMethod !== ''
        && workflowState.busy === ''
        && Boolean(permissions.canMarkPaid);

    const previewWorkflow = async () => {
        if (!canPreview) {
            return;
        }

        setWorkflowState((prev) => ({
            ...prev,
            busy: 'preview',
            error: '',
            success: '',
            activeStep: 1,
        }));

        try {
            const { data } = await axios.post(urls.workflowPreview, {
                _token: csrfToken,
                user_id: Number(workflowState.employeeId),
                payroll_month: workflowState.month,
                payable_days: workflowState.payableDays === '' ? null : Number(workflowState.payableDays),
            });

            const existingPayroll = data?.existingPayroll ?? null;
            const nextPayroll = existingPayroll || null;
            const nextStep = existingPayroll
                ? (existingPayroll.status === 'paid' ? 4 : existingPayroll.status === 'processed' ? 4 : 3)
                : 2;

            setWorkflowState((prev) => ({
                ...prev,
                preview: data?.preview ?? null,
                payroll: nextPayroll,
                error: '',
                success: existingPayroll
                    ? `Existing payroll found with status ${statusLabel(existingPayroll.status)}.`
                    : 'Preview loaded successfully.',
                activeStep: nextStep,
                busy: '',
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to preview payroll calculation.';
            setWorkflowState((prev) => ({
                ...prev,
                busy: '',
                error: message,
                success: '',
            }));
        }
    };

    const generateWorkflow = async () => {
        if (!canGenerate) {
            return;
        }

        setWorkflowState((prev) => ({
            ...prev,
            busy: 'generate',
            error: '',
            success: '',
        }));

        try {
            const { data } = await axios.post(urls.workflowGenerate, {
                _token: csrfToken,
                user_id: Number(workflowState.employeeId),
                payroll_month: workflowState.month,
                payable_days: workflowState.payableDays === '' ? null : Number(workflowState.payableDays),
                notes: workflowState.paymentNotes || null,
            });

            setWorkflowState((prev) => ({
                ...prev,
                payroll: data?.payroll ?? null,
                activeStep: 3,
                busy: '',
                error: '',
                success: data?.message || 'Payroll generated.',
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to generate payroll.';
            setWorkflowState((prev) => ({
                ...prev,
                busy: '',
                error: message,
                success: '',
            }));
        }
    };

    const approveWorkflow = async () => {
        const approveUrl = workflowState.payroll?.approveUrl;
        if (!canApprove || !approveUrl) {
            return;
        }

        setWorkflowState((prev) => ({
            ...prev,
            busy: 'approve',
            error: '',
            success: '',
        }));

        try {
            const { data } = await axios.put(approveUrl, {
                _token: csrfToken,
            });

            setWorkflowState((prev) => ({
                ...prev,
                payroll: data?.payroll ?? prev.payroll,
                activeStep: 4,
                busy: '',
                error: '',
                success: data?.message || 'Payroll approved successfully.',
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to approve payroll.';
            setWorkflowState((prev) => ({
                ...prev,
                busy: '',
                error: message,
                success: '',
            }));
        }
    };

    const confirmPayWorkflow = async () => {
        const payUrl = workflowState.payroll?.payUrl;
        if (!payUrl || workflowState.paymentMethod === '' || workflowState.busy !== '') {
            return;
        }

        setWorkflowState((prev) => ({
            ...prev,
            busy: 'pay',
            error: '',
            success: '',
        }));

        try {
            const { data } = await axios.put(payUrl, {
                _token: csrfToken,
                payment_method: workflowState.paymentMethod,
                payment_reference: workflowState.paymentReference || null,
                notes: workflowState.paymentNotes || null,
            });

            setWorkflowState((prev) => ({
                ...prev,
                payroll: data?.payroll ?? prev.payroll,
                busy: '',
                error: '',
                success: data?.message || 'Payroll marked as paid.',
                showPayConfirm: false,
                activeStep: 4,
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to mark payroll as paid.';
            setWorkflowState((prev) => ({
                ...prev,
                busy: '',
                error: message,
                success: '',
                showPayConfirm: false,
            }));
        }
    };

    const [enterpriseState, setEnterpriseState] = useState({
        month: toMonthValue(oldInput.payroll_month || filters.payroll_month),
        employeeScope: String(filters.employee_id ?? '') !== '' ? 'specific' : 'all',
        department: '',
        employeeId: String(filters.employee_id ?? ''),
        statusFilter: 'all',
        selectedIds: [],
        paymentMethod: String(oldInput.payment_method ?? ''),
        paymentReference: String(oldInput.payment_reference ?? ''),
        notes: String(oldInput.notes ?? ''),
        confirmLock: false,
        activeStep: 1,
        busy: '',
        error: '',
        success: '',
        warning: '',
        overview: null,
        preview: null,
        showApproveConfirm: false,
        showPayConfirm: false,
    });

    const departmentOptions = Array.from(new Set(
        employees
            .map((employee) => String(employee.department || '').trim())
            .filter((department) => department !== ''),
    )).sort((a, b) => a.localeCompare(b));
    const enterpriseSelectedEmployee = employees.find((item) => String(item.id) === String(enterpriseState.employeeId || '')) ?? null;

    const enterpriseStatus = String(enterpriseState.overview?.header?.status || 'draft');
    const enterpriseLocked = Boolean(enterpriseState.overview?.header?.locked);
    const enterpriseCompletedStep = enterpriseStatus === 'paid'
        ? 4
        : enterpriseStatus === 'approved'
            ? 3
            : enterpriseStatus === 'generated'
                ? 2
                : 0;

    const enterpriseRows = Array.isArray(enterpriseState.overview?.records) ? enterpriseState.overview.records : [];
    const step3Rows = enterpriseRows.filter((row) => {
        if (enterpriseState.statusFilter === 'all') {
            return true;
        }

        return String(row.uiStatus || row.status) === enterpriseState.statusFilter;
    });
    const approvableRows = step3Rows.filter((row) => ['generated', 'failed'].includes(String(row.uiStatus || row.status)));
    const allStep3Selected = approvableRows.length > 0
        && approvableRows.every((row) => enterpriseState.selectedIds.includes(Number(row.id)));
    const allApprovedForStep4 = enterpriseRows.length > 0
        && enterpriseRows.every((row) => ['approved', 'paid'].includes(String(row.uiStatus || row.status)));
    const enterpriseHeader = enterpriseState.overview?.header ?? {};
    const enterpriseSummary = enterpriseState.overview?.summary ?? {};
    const enterprisePermissions = enterpriseState.overview?.permissions ?? {
        canGenerate: permissions.canGenerate,
        canApprove: permissions.canApprove,
        canMarkPaid: permissions.canMarkPaid,
        canUnlock: permissions.canUnlock,
    };

    const loadEnterpriseOverview = async (overrides = {}) => {
        if (!urls.workflowOverview) {
            return null;
        }

        const resolvedScope = overrides.employeeScope ?? enterpriseState.employeeScope;
        const resolvedEmployeeId = resolvedScope === 'specific'
            ? (overrides.employeeId ?? enterpriseState.employeeId)
            : '';
        const requestPayload = {
            payroll_month: overrides.month ?? enterpriseState.month,
            department: overrides.department ?? enterpriseState.department,
            employee_id: resolvedEmployeeId || null,
        };

        try {
            const { data } = await axios.get(urls.workflowOverview, { params: requestPayload });
            const nextStatus = String(data?.header?.status || 'draft');
            const nextStep = nextStatus === 'paid'
                ? 4
                : nextStatus === 'approved'
                    ? 4
                    : nextStatus === 'generated'
                        ? 3
                        : 1;
            setEnterpriseState((prev) => ({
                ...prev,
                overview: data,
                error: '',
                warning: '',
                month: String(data?.month || prev.month),
                activeStep: nextStep,
            }));
            return data;
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to load workflow overview.';
            setEnterpriseState((prev) => ({
                ...prev,
                error: message,
            }));
            return null;
        }
    };

    useEffect(() => {
        loadEnterpriseOverview();
    }, []);

    const handleWorkflowContinue = async () => {
        if (!enterpriseState.month) {
            setEnterpriseState((prev) => ({ ...prev, error: 'Please select payroll month.' }));
            return;
        }

        if (enterpriseState.employeeScope === 'specific' && !enterpriseState.employeeId) {
            setEnterpriseState((prev) => ({ ...prev, error: 'Please select an employee.' }));
            return;
        }

        setEnterpriseState((prev) => ({ ...prev, busy: 'overview', error: '', success: '', warning: '' }));
        const overview = await loadEnterpriseOverview();

        if (!overview) {
            setEnterpriseState((prev) => ({ ...prev, busy: '' }));
            return;
        }

        if (overview?.header?.locked) {
            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                error: 'This payroll month is already closed and locked.',
            }));
            return;
        }

        setEnterpriseState((prev) => ({
            ...prev,
            activeStep: 2,
            busy: 'preview',
            warning: Number(overview?.summary?.generatedCount || 0) > 0
                ? 'Payroll already generated for one or more employees in this month.'
                : '',
        }));

        try {
            const { data } = await axios.post(urls.workflowPreviewBatch, {
                _token: csrfToken,
                payroll_month: enterpriseState.month,
                department: enterpriseState.department || null,
                employee_id: enterpriseState.employeeScope === 'specific' && enterpriseState.employeeId
                    ? Number(enterpriseState.employeeId)
                    : null,
            });

            setEnterpriseState((prev) => ({
                ...prev,
                preview: data,
                busy: '',
                error: '',
                warning: data?.warning || prev.warning,
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to load preview.';
            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                error: message,
            }));
        }
    };

    const handleGenerateBatch = async () => {
        if (!urls.workflowGenerateBatch || enterpriseLocked) {
            return;
        }

        setEnterpriseState((prev) => ({ ...prev, busy: 'generate', error: '', success: '' }));

        try {
            const { data } = await axios.post(urls.workflowGenerateBatch, {
                _token: csrfToken,
                payroll_month: enterpriseState.month,
                department: enterpriseState.department || null,
                employee_id: enterpriseState.employeeScope === 'specific' && enterpriseState.employeeId
                    ? Number(enterpriseState.employeeId)
                    : null,
                notes: enterpriseState.notes || null,
            });

            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                activeStep: 3,
                overview: data?.overview ?? prev.overview,
                success: data?.message || 'Payroll generated.',
                error: '',
                selectedIds: [],
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to generate payroll.';
            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                error: message,
                success: '',
            }));
        }
    };

    const confirmApproveBatch = async () => {
        if (!urls.workflowApproveBatch) {
            return;
        }

        setEnterpriseState((prev) => ({
            ...prev,
            busy: 'approve',
            showApproveConfirm: false,
            error: '',
            success: '',
        }));

        try {
            const { data } = await axios.post(urls.workflowApproveBatch, {
                _token: csrfToken,
                payroll_month: enterpriseState.month,
                department: enterpriseState.department || null,
                employee_id: enterpriseState.employeeScope === 'specific' && enterpriseState.employeeId
                    ? Number(enterpriseState.employeeId)
                    : null,
                payroll_ids: enterpriseState.selectedIds,
            });

            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                overview: data?.overview ?? prev.overview,
                success: data?.message || 'Payroll approved.',
                selectedIds: [],
                activeStep: 3,
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to approve payroll.';
            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                error: message,
            }));
        }
    };

    const confirmPayClose = async () => {
        if (!urls.workflowPayClose) {
            return;
        }

        setEnterpriseState((prev) => ({
            ...prev,
            busy: 'pay',
            showPayConfirm: false,
            error: '',
            success: '',
        }));

        try {
            const { data } = await axios.post(urls.workflowPayClose, {
                _token: csrfToken,
                payroll_month: enterpriseState.month,
                department: enterpriseState.department || null,
                employee_id: enterpriseState.employeeScope === 'specific' && enterpriseState.employeeId
                    ? Number(enterpriseState.employeeId)
                    : null,
                payment_method: enterpriseState.paymentMethod,
                payment_reference: enterpriseState.paymentReference || null,
                notes: enterpriseState.notes || null,
                confirm_lock: true,
            });

            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                overview: data?.overview ?? prev.overview,
                success: data?.message || 'Payroll paid and locked.',
                activeStep: 4,
                confirmLock: true,
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to mark payroll as paid.';
            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                error: message,
            }));
        }
    };

    const unlockPayrollMonth = async () => {
        if (!urls.workflowUnlock) {
            return;
        }

        setEnterpriseState((prev) => ({ ...prev, busy: 'unlock', error: '', success: '' }));

        try {
            const { data } = await axios.post(urls.workflowUnlock, {
                _token: csrfToken,
                payroll_month: enterpriseState.month,
                unlock_reason: 'Manual unlock by super admin',
            });

            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                overview: data?.overview ?? prev.overview,
                success: data?.message || 'Payroll month unlocked.',
                activeStep: 1,
            }));
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to unlock payroll month.';
            setEnterpriseState((prev) => ({
                ...prev,
                busy: '',
                error: message,
            }));
        }
    };

    const openStructureEditor = () => {
        if (!selectedStructureUserId) {
            return;
        }

        setShowStructureEditor(true);
        setStructureError('');
        setStructureSuccess('');
    };

    const validateStructureForm = () => {
        if (!selectedStructureUserId) {
            return 'Please select an employee.';
        }

        if (structureForm.basic_salary === '') {
            return 'Basic salary is required.';
        }

        const numericFields = [
            'basic_salary',
            'hra',
            'special_allowance',
            'bonus',
            'other_allowance',
            'pf_deduction',
            'tax_deduction',
            'other_deduction',
        ];

        for (const key of numericFields) {
            const value = Number(structureForm[key] === '' ? 0 : structureForm[key]);
            if (Number.isNaN(value) || value < 0) {
                return 'Salary values cannot be negative.';
            }
        }

        return '';
    };

    const saveStructure = async () => {
        if (!permissions.canGenerate) {
            setStructureError('You do not have permission to update salary structure.');
            return;
        }

        const validationMessage = validateStructureForm();
        if (validationMessage !== '') {
            setStructureError(validationMessage);
            return;
        }

        const endpoint = String(urls.structureUpsert || '').replace('__USER_ID__', String(selectedStructureUserId));
        if (endpoint.trim() === '') {
            setStructureError('Structure API endpoint is missing.');
            return;
        }

        setStructureSaveBusy(true);
        setStructureError('');
        setStructureSuccess('');

        try {
            const payloadData = {
                _token: csrfToken,
                user_id: Number(selectedStructureUserId),
                basic_salary: Number(structureForm.basic_salary || 0),
                hra: Number(structureForm.hra || 0),
                special_allowance: Number(structureForm.special_allowance || 0),
                bonus: Number(structureForm.bonus || 0),
                other_allowance: Number(structureForm.other_allowance || 0),
                pf_deduction: Number(structureForm.pf_deduction || 0),
                tax_deduction: Number(structureForm.tax_deduction || 0),
                other_deduction: Number(structureForm.other_deduction || 0),
                effective_from: structureForm.effective_from || null,
                notes: structureForm.notes || null,
            };

            const { data } = await axios.put(endpoint, payloadData);
            const nextStructure = data?.structure;
            if (nextStructure) {
                setStructures((prev) => {
                    const found = prev.some((entry) => String(entry.userId) === String(nextStructure.userId));
                    if (!found) {
                        return [nextStructure, ...prev];
                    }

                    return prev.map((entry) => (
                        String(entry.userId) === String(nextStructure.userId)
                            ? { ...entry, ...nextStructure }
                            : entry
                    ));
                });
            }

            setStructureHistory(Array.isArray(data?.history) ? data.history : []);
            setStructureSuccess(data?.message || 'Structure saved successfully.');
            setShowStructureEditor(false);
        } catch (error) {
            const message = error?.response?.data?.message || 'Unable to save structure.';
            setStructureError(message);
        } finally {
            setStructureSaveBusy(false);
        }
    };

    const missingBankEmployeeIds = employees
        .filter((employee) => !employee.hasBankDetails)
        .map((employee) => Number(employee.id));

    const issueRows = (() => {
        if (activeAlertFilter === 'missing_structure') {
            return employees
                .filter((employee) => !employee.hasStructure)
                .map((employee) => ({
                    id: `issue-structure-${employee.id}`,
                    issueType: 'Missing salary structure',
                    user: employee,
                }));
        }

        if (activeAlertFilter === 'not_generated') {
            const generatedUserIds = new Set(records.map((record) => Number(record.user?.id)));
            return employees
                .filter((employee) => employee.hasStructure && !generatedUserIds.has(Number(employee.id)))
                .map((employee) => ({
                    id: `issue-not-generated-${employee.id}`,
                    issueType: 'Payroll not generated',
                    user: employee,
                }));
        }

        if (activeAlertFilter === 'missing_bank_details') {
            return employees
                .filter((employee) => !employee.hasBankDetails)
                .map((employee) => ({
                    id: `issue-bank-${employee.id}`,
                    issueType: 'Missing bank details',
                    user: employee,
                }));
        }

        return [];
    })();

    const searchedRows = records.filter((record) => {
        const search = tableSearch.trim().toLowerCase();
        if (search === '') {
            return true;
        }

        return [
            record.user?.name,
            record.user?.email,
            record.paymentReference,
            record.notes,
            record.statusLabel,
        ].some((value) => String(value || '').toLowerCase().includes(search));
    });

    const alertFilteredRows = searchedRows.filter((record) => {
        if (activeAlertFilter === 'pending_approvals') {
            return record.uiStatus === 'generated';
        }

        if (activeAlertFilter === 'calculation_errors') {
            return record.uiStatus === 'failed';
        }

        if (activeAlertFilter === 'missing_bank_details') {
            return missingBankEmployeeIds.includes(Number(record.user?.id));
        }

        return true;
    });

    const statusFilteredRows = alertFilteredRows.filter((record) => {
        if (!tableStatusFilter) {
            return true;
        }

        return String(record.uiStatus) === tableStatusFilter;
    });

    const sortedRows = [...statusFilteredRows].sort((a, b) => {
        const direction = tableSortDir === 'asc' ? 1 : -1;

        if (tableSortBy === 'gross') {
            return (Number(a.grossEarnings) - Number(b.grossEarnings)) * direction;
        }

        if (tableSortBy === 'status') {
            return (statusSortRank(a.uiStatus) - statusSortRank(b.uiStatus)) * direction;
        }

        return (Number(a.netSalary) - Number(b.netSalary)) * direction;
    });

    const allRowIds = sortedRows.map((row) => Number(row.id));
    const allSelected = allRowIds.length > 0 && allRowIds.every((id) => selectedPayrollIds.includes(id));

    const summaryTotals = sortedRows.reduce((acc, row) => ({
        gross: acc.gross + Number(row.grossEarnings || 0),
        deductions: acc.deductions + Number(row.totalDeductions || 0),
        net: acc.net + Number(row.netSalary || 0),
    }), { gross: 0, deductions: 0, net: 0 });

    const toggleSelectAll = () => {
        if (allSelected) {
            setSelectedPayrollIds([]);
            return;
        }

        setSelectedPayrollIds(allRowIds);
    };

    const toggleSelectOne = (recordId) => {
        const id = Number(recordId);
        setSelectedPayrollIds((prev) => (
            prev.includes(id)
                ? prev.filter((existingId) => existingId !== id)
                : [...prev, id]
        ));
    };

    const performBulkAction = async (action) => {
        if (selectedPayrollIds.length === 0) {
            setTableActionError('Please select at least one payroll row.');
            return;
        }

        setBulkBusy(true);
        setTableActionError('');
        setTableActionSuccess('');

        try {
            const payloadData = {
                _token: csrfToken,
                action,
                payroll_ids: selectedPayrollIds,
            };

            if (action === 'mark_paid') {
                if (workflowState.paymentMethod === '') {
                    setTableActionError('Select payment method in workflow step 4 before bulk mark paid.');
                    setBulkBusy(false);
                    return;
                }
                payloadData.payment_method = workflowState.paymentMethod;
                payloadData.payment_reference = workflowState.paymentReference || null;
                payloadData.notes = workflowState.paymentNotes || null;
            }

            const { data } = await axios.post(urls.directoryBulkAction, payloadData);
            const summary = data?.summary ?? {};
            setTableActionSuccess(
                `Bulk ${summary.action || action} completed. Updated: ${toCount(summary.updated || 0)}, Deleted: ${toCount(summary.deleted || 0)}, Skipped: ${toCount(summary.skipped || 0)}.`,
            );
            window.location.reload();
        } catch (error) {
            const message = error?.response?.data?.message || 'Bulk action failed.';
            setTableActionError(message);
        } finally {
            setBulkBusy(false);
        }
    };

    return (
        <div className="space-y-5">
            <section className="rounded-2xl border p-5" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <SectionTitle
                        eyebrow="Section 1"
                        title="Monthly Payroll Overview"
                        subtitle={`Current cycle performance for ${payload?.monthLabel ?? 'selected month'}.`}
                    />
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <StatPill label="Generated" value={toCount(stats.generatedThisMonth)} />
                        <StatPill label="Draft" value={toCount(stats.draftThisMonth)} />
                        <StatPill label="Processed" value={toCount(stats.processedThisMonth)} />
                        <StatPill label="Paid" value={toCount(stats.paidThisMonth)} />
                    </div>
                </div>

                <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricProgressCard
                        label="Structure Readiness"
                        valueLabel={`${toCount(stats.employeesWithStructure)} / ${toCount(stats.totalEmployees)}`}
                        helperText={`${structureCoverage.toFixed(1)}% employees have salary structure`}
                        percent={structureCoverage}
                        barColor="linear-gradient(120deg, #4f46e5, #22d3ee)"
                    />
                    <MetricProgressCard
                        label="Payroll Generated"
                        valueLabel={`${toCount(stats.generatedThisMonth)} / ${toCount(stats.totalEmployees)}`}
                        helperText={`${payrollGenerationProgress.toFixed(1)}% employees generated`}
                        percent={payrollGenerationProgress}
                        barColor="linear-gradient(120deg, #0ea5e9, #22c55e)"
                    />
                    <MetricProgressCard
                        label="Approval Progress"
                        valueLabel={`${toCount(Number(stats.processedThisMonth ?? 0) + Number(stats.paidThisMonth ?? 0))} / ${toCount(stats.generatedThisMonth)}`}
                        helperText={`${approvalProgress.toFixed(1)}% approved or paid`}
                        percent={approvalProgress}
                        barColor="linear-gradient(120deg, #d97706, #f59e0b)"
                    />
                    <MetricProgressCard
                        label="Net Payout"
                        valueLabel={toMoney(stats.netThisMonth)}
                        helperText={`${payoutProgress.toFixed(1)}% already paid`}
                        percent={payoutProgress}
                        barColor="linear-gradient(120deg, #16a34a, #22c55e)"
                    />
                </div>
            </section>

            <section className="rounded-2xl border p-5" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                <SectionTitle
                    eyebrow="Section 2"
                    title="Alerts & Pending Actions"
                    subtitle="Operational issues detected for the selected month."
                />

                <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <AlertTile
                        label="Missing Salary Structure"
                        count={Number(alerts.missing_structure ?? missingStructureCount)}
                        active={activeAlertFilter === 'missing_structure'}
                        onClick={() => setActiveAlertFilter((prev) => prev === 'missing_structure' ? '' : 'missing_structure')}
                        tone="warning"
                    />
                    <AlertTile
                        label="Not Generated"
                        count={Number(alerts.not_generated ?? 0)}
                        active={activeAlertFilter === 'not_generated'}
                        onClick={() => setActiveAlertFilter((prev) => prev === 'not_generated' ? '' : 'not_generated')}
                        tone="info"
                    />
                    <AlertTile
                        label="Pending Approvals"
                        count={Number(alerts.pending_approvals ?? stats.draftThisMonth ?? 0)}
                        active={activeAlertFilter === 'pending_approvals'}
                        onClick={() => setActiveAlertFilter((prev) => prev === 'pending_approvals' ? '' : 'pending_approvals')}
                        tone="info"
                    />
                    <AlertTile
                        label="Calculation Errors"
                        count={Number(alerts.calculation_errors ?? stats.failedThisMonth ?? 0)}
                        active={activeAlertFilter === 'calculation_errors'}
                        onClick={() => setActiveAlertFilter((prev) => prev === 'calculation_errors' ? '' : 'calculation_errors')}
                        tone="danger"
                    />
                    <AlertTile
                        label="Missing Bank Details"
                        count={Number(alerts.missing_bank_details ?? 0)}
                        active={activeAlertFilter === 'missing_bank_details'}
                        onClick={() => setActiveAlertFilter((prev) => prev === 'missing_bank_details' ? '' : 'missing_bank_details')}
                        tone="warning"
                    />
                </div>

                {Number(alerts.missing_structure ?? 0) === 0
                    && Number(alerts.not_generated ?? 0) === 0
                    && Number(alerts.pending_approvals ?? 0) === 0
                    && Number(alerts.calculation_errors ?? 0) === 0
                    && Number(alerts.missing_bank_details ?? 0) === 0 ? (
                    <p className="mt-3 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-semibold text-green-700">
                        No Issues This Month
                    </p>
                ) : null}

                {flash?.status ? (
                    <p className="mt-3 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-semibold text-green-700">{flash.status}</p>
                ) : null}
                {flash?.error ? (
                    <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">{flash.error}</p>
                ) : null}
                {validation?.hasErrors ? (
                    <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">
                        {(Array.isArray(validation?.messages) ? validation.messages[0] : null) || 'Validation error detected.'}
                    </p>
                ) : null}
            </section>

            <section id="payroll-processing-steps" className="rounded-2xl border p-5" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                <SectionTitle
                    eyebrow="Section 3"
                    title="Enterprise Payroll Stepper"
                    subtitle="Strict month-level flow: Select Month, Preview & Generate, Approve, Pay & Close."
                />

                <div className="mt-4 grid grid-cols-2 gap-2 md:grid-cols-6">
                    <StatPill label="Payroll Month" value={enterpriseHeader.payrollMonth || '-'} />
                    <div className="rounded-xl border px-3 py-2" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                        <p className="text-[11px] font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Status</p>
                        <p className="mt-1 text-base font-extrabold">
                            <span className="ui-status-chip" style={statusStyle(enterpriseStatus)}>
                                {enterpriseHeader.statusLabel || statusLabel(enterpriseStatus)}
                            </span>
                        </p>
                    </div>
                    <StatPill label="Total Employees" value={toCount(enterpriseHeader.totalEmployees)} />
                    <StatPill label="Total Net Pay" value={toMoney(enterpriseHeader.totalNetPay)} />
                    <StatPill label="Last Updated" value={enterpriseHeader.lastUpdatedAt ? new Date(enterpriseHeader.lastUpdatedAt).toLocaleString() : '-'} />
                    <StatPill label="Lock" value={enterpriseLocked ? 'Locked' : 'Open'} />
                </div>

                {enterpriseLocked ? (
                    <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">
                        Payroll is locked for this month.
                        {enterpriseHeader.lockedBy ? ` Locked by ${enterpriseHeader.lockedBy}.` : ''}
                    </p>
                ) : null}

                {(enterpriseState.error || enterpriseState.success || enterpriseState.warning) ? (
                    <div className="mt-4 space-y-2">
                        {enterpriseState.error ? (
                            <div className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">{enterpriseState.error}</div>
                        ) : null}
                        {enterpriseState.warning ? (
                            <div className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">{enterpriseState.warning}</div>
                        ) : null}
                        {enterpriseState.success ? (
                            <div className="rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-semibold text-green-700">{enterpriseState.success}</div>
                        ) : null}
                    </div>
                ) : null}

                <div className="mt-4 grid grid-cols-1 gap-2 md:grid-cols-4">
                    {WORKFLOW_STEPS.map((step) => {
                        const isCompleted = enterpriseCompletedStep >= step.id;
                        const isActive = enterpriseState.activeStep === step.id;
                        const isFuture = step.id > enterpriseState.activeStep && !isCompleted;

                        return (
                            <div
                                key={`enterprise-step-${step.id}`}
                                className="rounded-xl border px-3 py-2"
                                style={{
                                    borderColor: isActive ? 'var(--hr-accent-border)' : 'var(--hr-line)',
                                    background: isActive ? 'var(--hr-accent-soft)' : 'var(--hr-surface)',
                                    opacity: isFuture ? 0.65 : 1,
                                }}
                            >
                                <p className="text-[11px] font-bold uppercase tracking-[0.08em]" style={{ color: isActive ? 'var(--hr-accent)' : 'var(--hr-text-muted)' }}>
                                    Step {step.id}
                                </p>
                                <p className="mt-1 text-sm font-bold" style={{ color: 'var(--hr-text-main)' }}>
                                    {isCompleted ? '✓ ' : ''}{step.label}
                                </p>
                                <p className="mt-1 text-[11px] font-semibold" style={{ color: isCompleted ? '#15803d' : 'var(--hr-text-muted)' }}>
                                    {isCompleted ? 'Completed' : isActive ? 'Active' : 'Disabled'}
                                </p>
                            </div>
                        );
                    })}
                </div>

                <div className="mt-4 rounded-2xl border p-4" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                    {enterpriseState.activeStep === 1 ? (
                        <div className="space-y-4">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Step 1</p>
                                <h4 className="mt-1 text-base font-extrabold">Select Month</h4>
                            </div>

                            <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div>
                                    <label className="ui-kpi-label mb-1 block" htmlFor="enterprise_month">Payroll Month</label>
                                    <input
                                        id="enterprise_month"
                                        type="month"
                                        value={enterpriseState.month}
                                        onChange={(event) => setEnterpriseState((prev) => ({ ...prev, month: event.target.value }))}
                                        className="ui-input"
                                        disabled={enterpriseLocked}
                                    />
                                </div>
                                <div>
                                    <label className="ui-kpi-label mb-1 block">Employee Scope</label>
                                    <div className="flex items-center gap-3 rounded-xl border px-3 py-2" style={{ borderColor: 'var(--hr-line)' }}>
                                        <label className="inline-flex items-center gap-2 text-sm">
                                            <input
                                                type="radio"
                                                name="enterprise_scope"
                                                value="all"
                                                checked={enterpriseState.employeeScope === 'all'}
                                                onChange={() => setEnterpriseState((prev) => ({
                                                    ...prev,
                                                    employeeScope: 'all',
                                                    employeeId: '',
                                                }))}
                                                disabled={enterpriseLocked}
                                            />
                                            All Employees
                                        </label>
                                        <label className="inline-flex items-center gap-2 text-sm">
                                            <input
                                                type="radio"
                                                name="enterprise_scope"
                                                value="specific"
                                                checked={enterpriseState.employeeScope === 'specific'}
                                                onChange={() => setEnterpriseState((prev) => ({ ...prev, employeeScope: 'specific' }))}
                                                disabled={enterpriseLocked}
                                            />
                                            Specific Employee
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label className="ui-kpi-label mb-1 block" htmlFor="enterprise_department">Department (Optional)</label>
                                    <select
                                        id="enterprise_department"
                                        value={enterpriseState.department}
                                        onChange={(event) => setEnterpriseState((prev) => ({ ...prev, department: event.target.value, employeeId: '' }))}
                                        className="ui-select"
                                        disabled={enterpriseLocked}
                                    >
                                        <option value="">All departments</option>
                                        {departmentOptions.map((department) => (
                                            <option key={`step1-dept-${department}`} value={department}>{department}</option>
                                        ))}
                                    </select>
                                </div>
                                {enterpriseState.employeeScope === 'specific' ? (
                                    <div className="md:col-span-2">
                                        <label className="ui-kpi-label mb-1 block">Employee</label>
                                        <EmployeeAutocomplete
                                            apiUrl={urls.employeeSearch || '/api/employees/search'}
                                            inputId="enterprise_employee"
                                            selectedEmployee={enterpriseSelectedEmployee}
                                            onSelect={(employee) => setEnterpriseState((prev) => ({
                                                ...prev,
                                                employeeId: employee ? String(employee.id) : '',
                                            }))}
                                            disabled={enterpriseLocked}
                                            placeholder="Search employee by name or email..."
                                        />
                                    </div>
                                ) : null}
                            </div>

                            <button
                                type="button"
                                className="ui-btn ui-btn-primary"
                                onClick={handleWorkflowContinue}
                                disabled={enterpriseLocked || enterpriseState.busy !== ''}
                            >
                                {enterpriseState.busy === 'overview' || enterpriseState.busy === 'preview' ? 'Loading...' : 'Continue'}
                            </button>
                        </div>
                    ) : null}

                    {enterpriseState.activeStep === 2 ? (
                        <div className="space-y-4">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Step 2</p>
                                <h4 className="mt-1 text-base font-extrabold">Preview & Generate</h4>
                            </div>

                            <div className="grid grid-cols-2 gap-2 md:grid-cols-6">
                                <StatPill label="Total Employees" value={toCount(enterpriseState.preview?.summary?.totalEmployees || 0)} />
                                <StatPill label="Gross Total" value={toMoney(enterpriseState.preview?.summary?.grossTotal || 0)} />
                                <StatPill label="Deduction Total" value={toMoney(enterpriseState.preview?.summary?.deductionTotal || 0)} />
                                <StatPill label="Net Total" value={toMoney(enterpriseState.preview?.summary?.netTotal || 0)} />
                                <StatPill label="Employees with Errors" value={toCount(enterpriseState.preview?.summary?.employeesWithErrors || 0)} />
                                <StatPill label="Missing Structure" value={toCount(enterpriseState.preview?.summary?.missingSalaryStructure || 0)} />
                            </div>

                            <div className="ui-table-wrap">
                                <table className="ui-table" style={{ minWidth: '900px' }}>
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Gross</th>
                                            <th>Deductions</th>
                                            <th>Net</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Array.isArray(enterpriseState.preview?.rows) && enterpriseState.preview.rows.length > 0 ? enterpriseState.preview.rows.map((row) => (
                                            <tr key={`preview-row-${row.employeeId}`}>
                                                <td>{row.employeeName}</td>
                                                <td>{row.department || '-'}</td>
                                                <td>{toMoney(row.gross)}</td>
                                                <td>{toMoney(row.deductions)}</td>
                                                <td>{toMoney(row.net)}</td>
                                                <td>{row.error ? <span className="ui-status-chip" style={statusStyle('failed')}>{row.error}</span> : '-'}</td>
                                            </tr>
                                        )) : (
                                            <tr>
                                                <td colSpan="6" className="ui-empty">No preview rows available.</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex items-center gap-2">
                                <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setEnterpriseState((prev) => ({ ...prev, activeStep: 1 }))}>
                                    Back
                                </button>
                                <button
                                    type="button"
                                    className="ui-btn ui-btn-primary"
                                    onClick={handleGenerateBatch}
                                    disabled={enterpriseLocked || enterpriseState.busy !== '' || !enterprisePermissions.canGenerate}
                                >
                                    {enterpriseState.busy === 'generate' ? 'Generating...' : 'Generate Payroll'}
                                </button>
                            </div>
                        </div>
                    ) : null}

                    {enterpriseState.activeStep === 3 ? (
                        <div className="space-y-4">
                            <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p className="text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Step 3</p>
                                    <h4 className="mt-1 text-base font-extrabold">Approve Payroll</h4>
                                </div>
                                <select
                                    value={enterpriseState.statusFilter}
                                    onChange={(event) => setEnterpriseState((prev) => ({ ...prev, statusFilter: event.target.value, selectedIds: [] }))}
                                    className="ui-select md:w-56"
                                >
                                    <option value="all">All statuses</option>
                                    <option value="generated">Generated</option>
                                    <option value="approved">Approved</option>
                                    <option value="paid">Paid</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>

                            <div className="ui-table-wrap">
                                <table className="ui-table" style={{ minWidth: '940px' }}>
                                    <thead>
                                        <tr>
                                            <th>
                                                <input
                                                    type="checkbox"
                                                    checked={allStep3Selected}
                                                    onChange={() => {
                                                        if (allStep3Selected) {
                                                            setEnterpriseState((prev) => ({ ...prev, selectedIds: [] }));
                                                            return;
                                                        }
                                                        setEnterpriseState((prev) => ({
                                                            ...prev,
                                                            selectedIds: approvableRows.map((row) => Number(row.id)),
                                                        }));
                                                    }}
                                                />
                                            </th>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Gross</th>
                                            <th>Deductions</th>
                                            <th>Net</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {step3Rows.length === 0 ? (
                                            <tr>
                                                <td colSpan="7" className="ui-empty">No generated payroll rows available.</td>
                                            </tr>
                                        ) : step3Rows.map((row) => (
                                            <tr key={`approve-row-${row.id}`}>
                                                <td>
                                                    <input
                                                        type="checkbox"
                                                        checked={enterpriseState.selectedIds.includes(Number(row.id))}
                                                        onChange={() => setEnterpriseState((prev) => ({
                                                            ...prev,
                                                            selectedIds: prev.selectedIds.includes(Number(row.id))
                                                                ? prev.selectedIds.filter((id) => id !== Number(row.id))
                                                                : [...prev.selectedIds, Number(row.id)],
                                                        }))}
                                                        disabled={!['generated', 'failed'].includes(String(row.uiStatus || row.status))}
                                                    />
                                                </td>
                                                <td>{row.employeeName}</td>
                                                <td>{row.department || '-'}</td>
                                                <td>{toMoney(row.gross)}</td>
                                                <td>{toMoney(row.deductions)}</td>
                                                <td>{toMoney(row.net)}</td>
                                                <td><span className="ui-status-chip" style={statusStyle(row.uiStatus || row.status)}>{row.statusLabel}</span></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setEnterpriseState((prev) => ({ ...prev, activeStep: 2 }))}>
                                    Back
                                </button>
                                <button
                                    type="button"
                                    className="ui-btn ui-btn-primary"
                                    onClick={() => setEnterpriseState((prev) => ({ ...prev, showApproveConfirm: true }))}
                                    disabled={enterpriseLocked || enterpriseState.selectedIds.length === 0 || !enterprisePermissions.canApprove || enterpriseState.busy !== ''}
                                >
                                    Approve Selected
                                </button>
                                <button
                                    type="button"
                                    className="ui-btn ui-btn-primary"
                                    onClick={() => setEnterpriseState((prev) => ({ ...prev, activeStep: 4, error: '' }))}
                                    disabled={!allApprovedForStep4}
                                >
                                    Continue to Step 4
                                </button>
                            </div>

                            {!allApprovedForStep4 ? (
                                <p className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">
                                    All payroll rows must be approved before payment.
                                </p>
                            ) : null}
                        </div>
                    ) : null}

                    {enterpriseState.activeStep === 4 ? (
                        <div className="space-y-4">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Step 4</p>
                                <h4 className="mt-1 text-base font-extrabold">Pay & Close</h4>
                            </div>

                            <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <StatPill label="Total Net Amount" value={toMoney(enterpriseHeader.totalNetPay || 0)} />
                                <div>
                                    <label className="ui-kpi-label mb-1 block" htmlFor="enterprise_payment_method">Payment Method</label>
                                    <select
                                        id="enterprise_payment_method"
                                        value={enterpriseState.paymentMethod}
                                        onChange={(event) => {
                                            setEnterpriseState((prev) => ({ ...prev, paymentMethod: event.target.value }));
                                            setWorkflowState((prev) => ({ ...prev, paymentMethod: event.target.value }));
                                        }}
                                        className="ui-select"
                                        disabled={enterpriseLocked}
                                    >
                                        <option value="">Select payment method</option>
                                        {paymentMethodOptions.map((paymentMethod) => (
                                            <option key={`enterprise-pay-${paymentMethod}`} value={paymentMethod}>{statusLabel(paymentMethod)}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="ui-kpi-label mb-1 block" htmlFor="enterprise_payment_reference">Payment Reference</label>
                                    <input
                                        id="enterprise_payment_reference"
                                        type="text"
                                        value={enterpriseState.paymentReference}
                                        onChange={(event) => {
                                            setEnterpriseState((prev) => ({ ...prev, paymentReference: event.target.value }));
                                            setWorkflowState((prev) => ({ ...prev, paymentReference: event.target.value }));
                                        }}
                                        className="ui-input"
                                        disabled={enterpriseLocked}
                                    />
                                </div>
                            </div>

                            <label className="inline-flex items-center gap-2 text-sm font-semibold">
                                <input
                                    type="checkbox"
                                    checked={enterpriseState.confirmLock}
                                    onChange={(event) => setEnterpriseState((prev) => ({ ...prev, confirmLock: event.target.checked }))}
                                    disabled={enterpriseLocked}
                                />
                                I confirm payroll should be marked paid and locked.
                            </label>

                            <div className="flex flex-wrap items-center gap-2">
                                <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setEnterpriseState((prev) => ({ ...prev, activeStep: 3 }))}>
                                    Back
                                </button>
                                <button
                                    type="button"
                                    className="ui-btn ui-btn-primary"
                                    onClick={() => setEnterpriseState((prev) => ({ ...prev, showPayConfirm: true }))}
                                    disabled={
                                        enterpriseLocked
                                        || !enterprisePermissions.canMarkPaid
                                        || enterpriseState.paymentMethod === ''
                                        || !enterpriseState.confirmLock
                                        || enterpriseState.busy !== ''
                                    }
                                >
                                    Mark as Paid
                                </button>
                                {enterpriseLocked && enterprisePermissions.canUnlock ? (
                                    <button
                                        type="button"
                                        className="ui-btn ui-btn-ghost"
                                        onClick={unlockPayrollMonth}
                                        disabled={enterpriseState.busy !== ''}
                                    >
                                        {enterpriseState.busy === 'unlock' ? 'Unlocking...' : 'Unlock Payroll'}
                                    </button>
                                ) : null}
                            </div>
                        </div>
                    ) : null}
                </div>
            </section>

            <section id="payroll-directory" className="rounded-2xl border p-5" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                <SectionTitle
                    eyebrow="Section 4"
                    title="Payroll Directory Table"
                    subtitle="Search, review, and update payroll records in one place."
                />

                <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-6">
                    <input
                        type="text"
                        value={tableSearch}
                        onChange={(event) => setTableSearch(event.target.value)}
                        placeholder="Search by employee name or reference..."
                        className="md:col-span-2 ui-input"
                    />

                    <select value={tableStatusFilter} onChange={(event) => setTableStatusFilter(event.target.value)} className="ui-select">
                        <option value="">All Status</option>
                        {STATUS_FILTER_OPTIONS.map((status) => (
                            <option key={`filter-${status}`} value={status}>{statusLabel(status)}</option>
                        ))}
                    </select>

                    <select value={tableSortBy} onChange={(event) => setTableSortBy(event.target.value)} className="ui-select">
                        <option value="net">Sort by Net</option>
                        <option value="gross">Sort by Gross</option>
                        <option value="status">Sort by Status</option>
                    </select>

                    <select value={tableSortDir} onChange={(event) => setTableSortDir(event.target.value)} className="ui-select">
                        <option value="desc">Desc</option>
                        <option value="asc">Asc</option>
                    </select>

                    <div className="flex items-center gap-2">
                        <a
                            href={`${urls.directoryExportCsv}?payroll_month=${encodeURIComponent(toMonthValue(filters.payroll_month))}&status=${encodeURIComponent(tableStatusFilter)}&q=${encodeURIComponent(tableSearch)}`}
                            className="ui-btn ui-btn-ghost"
                        >
                            Export CSV
                        </a>
                        <button
                            type="button"
                            className="ui-btn ui-btn-ghost"
                            onClick={() => {
                                setTableSearch('');
                                setTableStatusFilter('');
                                setTableSortBy('net');
                                setTableSortDir('desc');
                                setActiveAlertFilter('');
                                setSelectedPayrollIds([]);
                            }}
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div className="mt-3 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        className="ui-btn ui-btn-primary"
                        onClick={() => performBulkAction('approve')}
                        disabled={!permissions.canApprove || bulkBusy || selectedPayrollIds.length === 0}
                    >
                        Approve Selected
                    </button>
                    <button
                        type="button"
                        className="ui-btn ui-btn-primary"
                        onClick={() => performBulkAction('mark_paid')}
                        disabled={!permissions.canMarkPaid || bulkBusy || selectedPayrollIds.length === 0}
                    >
                        Mark as Paid
                    </button>
                    <button
                        type="button"
                        className="ui-btn ui-btn-ghost"
                        onClick={() => performBulkAction('delete')}
                        disabled={!permissions.canGenerate || bulkBusy || selectedPayrollIds.length === 0}
                    >
                        Delete (Not Approved)
                    </button>
                </div>

                {tableActionError ? (
                    <p className="mt-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">{tableActionError}</p>
                ) : null}
                {tableActionSuccess ? (
                    <p className="mt-2 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-semibold text-green-700">{tableActionSuccess}</p>
                ) : null}

                <div className="ui-table-wrap mt-4">
                    <table className="ui-table" style={{ minWidth: '1250px' }}>
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" checked={allSelected} onChange={toggleSelectAll} />
                                </th>
                                <th>Month</th>
                                <th>Employee</th>
                                <th>Days</th>
                                <th>Gross</th>
                                <th>Deductions</th>
                                <th>Net</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {activeAlertFilter && issueRows.length > 0 ? (
                                issueRows.map((row) => (
                                    <tr key={row.id}>
                                        <td />
                                        <td>{toMonthValue(filters.payroll_month)}</td>
                                        <td>
                                            <p className="font-semibold">{row.user.name}</p>
                                            <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{row.user.email}</p>
                                        </td>
                                        <td colSpan="3">
                                            <span className="ui-status-chip" style={statusStyle('failed')}>{row.issueType}</span>
                                        </td>
                                        <td className="font-bold">-</td>
                                        <td>
                                            <span className="ui-status-chip" style={statusStyle('failed')}>Failed</span>
                                        </td>
                                        <td>-</td>
                                        <td>-</td>
                                    </tr>
                                ))
                            ) : sortedRows.length === 0 ? (
                                <tr>
                                    <td colSpan="10" className="ui-empty">No payroll records found for selected filters.</td>
                                </tr>
                            ) : sortedRows.map((record) => (
                                <tr key={record.id}>
                                    <td>
                                        <input
                                            type="checkbox"
                                            checked={selectedPayrollIds.includes(Number(record.id))}
                                            onChange={() => toggleSelectOne(record.id)}
                                        />
                                    </td>
                                    <td>{record.payrollMonthLabel}</td>
                                    <td>
                                        <p className="font-semibold">{record.user?.name}</p>
                                        <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{record.user?.email}</p>
                                    </td>
                                    <td>
                                        <p>Payable {toMoney(record.payableDays)} / {toMoney(record.workingDays)}</p>
                                        <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>LOP {toMoney(record.lopDays)}</p>
                                    </td>
                                    <td>{toMoney(record.grossEarnings)}</td>
                                    <td>{toMoney(record.totalDeductions)}</td>
                                    <td className="font-bold">{toMoney(record.netSalary)}</td>
                                    <td>
                                        <span className="ui-status-chip" style={statusStyle(record.uiStatus)}>
                                            {statusLabel(record.uiStatus)}
                                        </span>
                                        <p className="mt-1 text-xs" style={{ color: 'var(--hr-text-muted)' }}>By {record.generatorName}</p>
                                    </td>
                                    <td>
                                        <p>{record.paymentMethod ? statusLabel(record.paymentMethod) : 'N/A'}</p>
                                        <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                                            {record.paidAt ? new Date(record.paidAt).toLocaleString() : 'Not paid'}
                                        </p>
                                        <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{record.paymentReference || 'No reference'}</p>
                                    </td>
                                    <td>
                                        {record.locked ? (
                                            <p className="mb-2 text-[11px] font-bold uppercase tracking-[0.08em]" style={{ color: '#15803d' }}>
                                                Locked
                                            </p>
                                        ) : null}
                                        <details className="rounded-lg border p-2" style={{ borderColor: 'var(--hr-line)' }}>
                                            <summary className="cursor-pointer text-xs font-bold" style={{ color: 'var(--hr-accent)' }}>Update</summary>
                                            <form
                                                method="POST"
                                                action={record.statusUpdateUrl}
                                                className="mt-2 grid grid-cols-1 gap-2"
                                                onSubmit={(event) => {
                                                    const formData = new FormData(event.currentTarget);
                                                    const nextStatus = String(formData.get('status') || '');
                                                    if (nextStatus === 'processed') {
                                                        if (!window.confirm('Approve this payroll record?')) {
                                                            event.preventDefault();
                                                        }
                                                        return;
                                                    }
                                                    if (nextStatus === 'paid') {
                                                        if (!window.confirm('Mark this payroll record as paid? This will lock the record.')) {
                                                            event.preventDefault();
                                                        }
                                                    }
                                                }}
                                            >
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <input type="hidden" name="_method" value="PUT" />
                                                {record.locked && permissions.isAdmin ? <input type="hidden" name="override_lock" value="1" /> : null}
                                                <select
                                                    name="status"
                                                    defaultValue={record.status}
                                                    className="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent"
                                                    style={{ borderColor: 'var(--hr-line)' }}
                                                    disabled={record.locked && !permissions.isAdmin}
                                                >
                                                    {statusOptions.map((statusOption) => (
                                                        <option key={`${record.id}-${statusOption}`} value={statusOption}>{statusLabel(statusOption)}</option>
                                                    ))}
                                                </select>
                                                <select
                                                    name="payment_method"
                                                    defaultValue={record.paymentMethod || ''}
                                                    className="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent"
                                                    style={{ borderColor: 'var(--hr-line)' }}
                                                    disabled={record.locked && !permissions.isAdmin}
                                                >
                                                    <option value="">Payment method</option>
                                                    {paymentMethodOptions.map((paymentMethodOption) => (
                                                        <option key={`${record.id}-pm-${paymentMethodOption}`} value={paymentMethodOption}>
                                                            {statusLabel(paymentMethodOption)}
                                                        </option>
                                                    ))}
                                                </select>
                                                <input
                                                    type="text"
                                                    name="payment_reference"
                                                    defaultValue={record.paymentReference || ''}
                                                    placeholder="Reference"
                                                    className="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent"
                                                    style={{ borderColor: 'var(--hr-line)' }}
                                                    disabled={record.locked && !permissions.isAdmin}
                                                />
                                                <input
                                                    type="text"
                                                    name="notes"
                                                    defaultValue={record.notes || ''}
                                                    placeholder="Internal note"
                                                    className="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent"
                                                    style={{ borderColor: 'var(--hr-line)' }}
                                                    disabled={record.locked && !permissions.isAdmin}
                                                />
                                                <button
                                                    type="submit"
                                                    className="rounded-lg border px-2.5 py-1.5 text-xs font-semibold"
                                                    style={{ borderColor: 'var(--hr-line)' }}
                                                    disabled={record.locked && !permissions.isAdmin}
                                                >
                                                    Update
                                                </button>

                                                {Array.isArray(record.auditTrail) && record.auditTrail.length > 0 ? (
                                                    <div className="rounded-lg border p-2 text-[11px]" style={{ borderColor: 'var(--hr-line)' }}>
                                                        <p className="font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                                            Audit History
                                                        </p>
                                                        <ul className="mt-1 space-y-1">
                                                            {record.auditTrail.map((entry) => (
                                                                <li key={entry.id} style={{ color: 'var(--hr-text-muted)' }}>
                                                                    {statusLabel(entry.action)}
                                                                    {' • '}
                                                                    {entry.performedByName || 'System'}
                                                                    {' • '}
                                                                    {entry.performedAt ? new Date(entry.performedAt).toLocaleString() : '-'}
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                ) : null}
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                            ))}
                            {sortedRows.length > 0 ? (
                                <tr>
                                    <td />
                                    <td colSpan="3" className="font-bold">Summary</td>
                                    <td className="font-bold">{toMoney(summaryTotals.gross)}</td>
                                    <td className="font-bold">{toMoney(summaryTotals.deductions)}</td>
                                    <td className="font-bold">{toMoney(summaryTotals.net)}</td>
                                    <td colSpan="3" />
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <p style={{ color: 'var(--hr-text-muted)' }}>
                        Showing {toCount(pagination.from ?? 0)} - {toCount(pagination.to ?? 0)} of {toCount(pagination.total ?? 0)}
                    </p>
                    <div className="flex items-center gap-2">
                        {pagination.prevPageUrl ? (
                            <a href={pagination.prevPageUrl} className="ui-btn ui-btn-ghost">Previous</a>
                        ) : (
                            <span className="ui-btn ui-btn-ghost" style={{ opacity: 0.45, pointerEvents: 'none' }}>Previous</span>
                        )}
                        <span className="text-xs font-bold" style={{ color: 'var(--hr-text-muted)' }}>
                            Page {toCount(pagination.currentPage ?? 1)} / {toCount(pagination.lastPage ?? 1)}
                        </span>
                        {pagination.nextPageUrl ? (
                            <a href={pagination.nextPageUrl} className="ui-btn ui-btn-ghost">Next</a>
                        ) : (
                            <span className="ui-btn ui-btn-ghost" style={{ opacity: 0.45, pointerEvents: 'none' }}>Next</span>
                        )}
                    </div>
                </div>
            </section>

            <section id="salary-structure-management" className="rounded-2xl border p-5" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface-strong)' }}>
                <SectionTitle
                    eyebrow="Section 5"
                    title="Salary Structure Management"
                    subtitle="Review summary first, edit only when needed, and track all changes."
                />

                <div className="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <article className="rounded-2xl border p-4 xl:col-span-2" style={{ borderColor: 'var(--hr-line)' }}>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <label className="ui-kpi-label mb-1 block" htmlFor="structure_employee_selector">Employee</label>
                                <div className="min-w-[280px]">
                                    <EmployeeAutocomplete
                                        apiUrl={urls.employeeSearch || '/api/employees/search'}
                                        inputId="structure_employee_selector"
                                        selectedEmployee={selectedStructureEmployee}
                                        onSelect={(employee) => setSelectedStructureUserId(employee ? String(employee.id) : '')}
                                        placeholder="Search employee by name or email..."
                                    />
                                </div>
                            </div>
                            <button type="button" className="ui-btn ui-btn-primary" onClick={openStructureEditor} disabled={!permissions.canGenerate}>
                                Edit Structure
                            </button>
                        </div>

                        {structureError ? (
                            <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">{structureError}</p>
                        ) : null}
                        {structureSuccess ? (
                            <p className="mt-3 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm font-semibold text-green-700">{structureSuccess}</p>
                        ) : null}

                        {selectedStructure ? (
                            <>
                                <div className="mt-3 rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                                    <p className="text-sm font-bold">{selectedStructure.userName}</p>
                                    <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>
                                        {selectedStructure.userEmail || selectedStructureEmployee?.email || 'No email'} • {selectedStructure.department || selectedStructureEmployee?.department || 'No department'}
                                    </p>
                                    <p className="mt-2 text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                        Effective From: {selectedStructure.effectiveFrom || 'Immediate'}
                                    </p>
                                </div>
                                <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                                    <StatPill label="Basic Salary" value={toMoney(selectedStructure.basicSalary)} />
                                    <StatPill label="HRA" value={toMoney(selectedStructure.hra)} />
                                    <StatPill label="Special Allowance" value={toMoney(selectedStructure.specialAllowance)} />
                                    <StatPill label="Bonus" value={toMoney(selectedStructure.bonus)} />
                                    <StatPill label="Other Allowance" value={toMoney(selectedStructure.otherAllowance)} />
                                    <StatPill label="PF Deduction" value={toMoney(selectedStructure.pfDeduction)} />
                                    <StatPill label="Tax Deduction" value={toMoney(selectedStructure.taxDeduction)} />
                                    <StatPill label="Other Deduction" value={toMoney(selectedStructure.otherDeduction)} />
                                </div>
                                <div className="mt-3 rounded-xl border px-3 py-3" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                                    <p className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>Net Structure Total</p>
                                    <p className="mt-1 text-2xl font-extrabold">{toMoney(selectedStructureNet)}</p>
                                </div>
                            </>
                        ) : (
                            <p className="mt-3 rounded-xl border px-3 py-2 text-sm" style={{ borderColor: 'var(--hr-line)', color: 'var(--hr-text-muted)' }}>
                                No salary structure available for selected employee.
                            </p>
                        )}
                    </article>

                    <article className="rounded-2xl border p-4" style={{ borderColor: 'var(--hr-line)' }}>
                        <h4 className="text-base font-extrabold">Change History</h4>
                        <p className="mt-1 text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                            Date, actor, and exact fields changed.
                        </p>
                        <div className="mt-3 max-h-[430px] space-y-2 overflow-auto">
                            {historyLoading ? (
                                <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>Loading history...</p>
                            ) : structureHistory.length === 0 ? (
                                <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>No change history available.</p>
                            ) : structureHistory.map((entry) => (
                                <div key={entry.id} className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                                    <p className="text-xs font-semibold" style={{ color: 'var(--hr-text-muted)' }}>
                                        {entry.changedAt ? new Date(entry.changedAt).toLocaleString() : '-'} • {entry.changedBy}
                                    </p>
                                    <ul className="mt-1 space-y-1 text-xs">
                                        {(Array.isArray(entry.changeSummary) ? entry.changeSummary : []).map((change, idx) => (
                                            <li key={`${entry.id}-${idx}`}>
                                                <span className="font-semibold">{statusLabel(change.field)}</span>
                                                {' '}
                                                <span style={{ color: 'var(--hr-text-muted)' }}>
                                                    {String(change.from ?? '-')}
                                                    {' -> '}
                                                    {String(change.to ?? '-')}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </article>
                </div>
            </section>

            <AppModalPortal
                open={showStructureEditor}
                onBackdropClick={structureSaveBusy ? null : () => {
                    setShowStructureEditor(false);
                    setStructureError('');
                    setStructureSuccess('');
                }}
            >
                <div className="app-modal-panel w-full max-w-3xl p-5" role="dialog" aria-modal="true">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h4 className="text-lg font-extrabold">Edit Salary Structure</h4>
                                <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                                    {selectedStructure?.userName || selectedStructureEmployee?.name || 'Selected employee'}
                                    {' • Effective From: '}
                                    {structureForm.effective_from || selectedStructure?.effectiveFrom || 'Immediate'}
                                </p>
                            </div>
                            <button
                                type="button"
                                className="ui-btn ui-btn-ghost"
                                onClick={() => {
                                    setShowStructureEditor(false);
                                    setStructureError('');
                                    setStructureSuccess('');
                                }}
                                disabled={structureSaveBusy}
                            >
                                Close
                            </button>
                        </div>

                        <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_basic_salary">Basic Salary *</label>
                                <input
                                    id="structure_form_basic_salary"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={structureForm.basic_salary}
                                    onChange={(event) => setStructureForm((prev) => ({ ...prev, basic_salary: event.target.value }))}
                                    className="ui-input"
                                    required
                                />
                            </div>
                            <div>
                                <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_hra">HRA</label>
                                <input
                                    id="structure_form_hra"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={structureForm.hra}
                                    onChange={(event) => setStructureForm((prev) => ({ ...prev, hra: event.target.value }))}
                                    className="ui-input"
                                />
                            </div>
                            <div>
                                <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_special_allowance">Special Allowance</label>
                                <input
                                    id="structure_form_special_allowance"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={structureForm.special_allowance}
                                    onChange={(event) => setStructureForm((prev) => ({ ...prev, special_allowance: event.target.value }))}
                                    className="ui-input"
                                />
                            </div>
                            <div>
                                <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_bonus">Bonus</label>
                                <input
                                    id="structure_form_bonus"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={structureForm.bonus}
                                    onChange={(event) => setStructureForm((prev) => ({ ...prev, bonus: event.target.value }))}
                                    className="ui-input"
                                />
                            </div>
                            <div>
                                <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_other_allowance">Other Allowance</label>
                                <input
                                    id="structure_form_other_allowance"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={structureForm.other_allowance}
                                    onChange={(event) => setStructureForm((prev) => ({ ...prev, other_allowance: event.target.value }))}
                                    className="ui-input"
                                />
                            </div>
                            <div>
                                <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_effective_from">Effective From</label>
                                <input
                                    id="structure_form_effective_from"
                                    type="date"
                                    value={structureForm.effective_from}
                                    onChange={(event) => setStructureForm((prev) => ({ ...prev, effective_from: event.target.value }))}
                                    className="ui-input"
                                />
                            </div>
                        </div>

                        <details className="mt-4 rounded-xl border" style={{ borderColor: 'var(--hr-line)' }}>
                            <summary className="cursor-pointer px-3 py-2 text-xs font-bold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Advanced Options
                            </summary>
                            <div className="grid grid-cols-1 gap-3 px-3 pb-3 sm:grid-cols-2">
                                <div>
                                    <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_pf_deduction">PF Deduction</label>
                                    <input
                                        id="structure_form_pf_deduction"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={structureForm.pf_deduction}
                                        onChange={(event) => setStructureForm((prev) => ({ ...prev, pf_deduction: event.target.value }))}
                                        className="ui-input"
                                    />
                                </div>
                                <div>
                                    <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_tax_deduction">Tax Deduction</label>
                                    <input
                                        id="structure_form_tax_deduction"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={structureForm.tax_deduction}
                                        onChange={(event) => setStructureForm((prev) => ({ ...prev, tax_deduction: event.target.value }))}
                                        className="ui-input"
                                    />
                                </div>
                                <div>
                                    <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_other_deduction">Other Deduction</label>
                                    <input
                                        id="structure_form_other_deduction"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={structureForm.other_deduction}
                                        onChange={(event) => setStructureForm((prev) => ({ ...prev, other_deduction: event.target.value }))}
                                        className="ui-input"
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="ui-kpi-label mb-1 block" htmlFor="structure_form_notes">Notes</label>
                                    <textarea
                                        id="structure_form_notes"
                                        rows="3"
                                        value={structureForm.notes}
                                        onChange={(event) => setStructureForm((prev) => ({ ...prev, notes: event.target.value }))}
                                        className="ui-textarea resize-y"
                                        placeholder="Optional notes for this revision."
                                    />
                                </div>
                            </div>
                        </details>

                        {structureError ? (
                            <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">{structureError}</p>
                        ) : null}

                        <div className="mt-4 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                className="ui-btn ui-btn-ghost"
                                onClick={() => setShowStructureEditor(false)}
                                disabled={structureSaveBusy}
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                className="ui-btn ui-btn-primary"
                                onClick={saveStructure}
                                disabled={structureSaveBusy}
                            >
                                {structureSaveBusy ? 'Saving...' : 'Save Structure'}
                            </button>
                        </div>
                </div>
            </AppModalPortal>

            <ConfirmModal
                open={enterpriseState.showApproveConfirm}
                title="Confirm Bulk Approval"
                description="This will approve the selected payroll rows."
                onCancel={() => setEnterpriseState((prev) => ({ ...prev, showApproveConfirm: false }))}
                onConfirm={confirmApproveBatch}
                disabled={enterpriseState.busy === 'approve'}
                confirmLabel="Approve Selected"
            />

            <ConfirmModal
                open={enterpriseState.showPayConfirm}
                title="Confirm Pay & Close"
                description="This will mark payroll as paid for this month and lock the workflow."
                onCancel={() => setEnterpriseState((prev) => ({ ...prev, showPayConfirm: false }))}
                onConfirm={confirmPayClose}
                disabled={enterpriseState.busy === 'pay'}
                confirmLabel="Confirm & Mark Paid"
            />
        </div>
    );
}

export function mountAdminPayrollManagement() {
    const rootElement = document.getElementById('admin-payroll-management-root');
    if (!rootElement) {
        return;
    }

    const payload = parsePayload(rootElement);
    if (!payload) {
        rootElement.innerHTML = '<div class="ui-alert ui-alert-danger">Unable to initialize payroll management view.</div>';
        return;
    }

    createRoot(rootElement).render(<AdminPayrollManagement payload={payload} />);
}
