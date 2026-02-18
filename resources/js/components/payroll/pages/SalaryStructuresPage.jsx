import React, { useEffect, useMemo, useState } from 'react';
import { payrollApi } from '../api';
import {
    ConfirmModal,
    InfoCard,
    SectionHeader,
    StatusBadge,
    TableEmptyState,
    formatCount,
    formatDateTime,
    formatMoney,
    useDebouncedValue,
} from '../shared/ui';
import { AppModalPortal } from '../../shared/AppModalPortal';

const initialFormState = {
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
};

const toNumberString = (value) => String(value ?? '');

const toPayload = (form) => ({
    basic_salary: Number(form.basic_salary || 0),
    hra: Number(form.hra || 0),
    special_allowance: Number(form.special_allowance || 0),
    bonus: Number(form.bonus || 0),
    other_allowance: Number(form.other_allowance || 0),
    pf_deduction: Number(form.pf_deduction || 0),
    tax_deduction: Number(form.tax_deduction || 0),
    other_deduction: Number(form.other_deduction || 0),
    effective_from: form.effective_from || '',
    notes: form.notes || '',
});

export function SalaryStructuresPage({ urls, csrfToken, filters, initialStatus = 'all' }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [rows, setRows] = useState([]);
    const [summary, setSummary] = useState(null);
    const [totals, setTotals] = useState({ gross: 0 });
    const [pagination, setPagination] = useState({ currentPage: 1, lastPage: 1, total: 0, perPage: 15 });

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState(() => {
        const allowed = ['all', 'with_structure', 'missing_structure', 'missing_bank'];
        return allowed.includes(initialStatus) ? initialStatus : 'all';
    });
    const [page, setPage] = useState(1);

    const [editorOpen, setEditorOpen] = useState(false);
    const [editorRow, setEditorRow] = useState(null);
    const [form, setForm] = useState(initialFormState);
    const [formError, setFormError] = useState('');
    const [formSuccess, setFormSuccess] = useState('');
    const [saving, setSaving] = useState(false);
    const [advancedOpen, setAdvancedOpen] = useState(false);
    const [history, setHistory] = useState([]);
    const [historyLoading, setHistoryLoading] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);

    const debouncedSearch = useDebouncedValue(search, 300);

    const query = useMemo(() => ({
        branch_id: filters.branchId || '',
        department_id: filters.departmentId || '',
        employee_id: filters.employeeId || '',
        q: debouncedSearch,
        status,
        page,
    }), [filters.branchId, filters.departmentId, filters.employeeId, debouncedSearch, page, status]);

    const loadStructures = () => {
        setLoading(true);
        setError('');

        payrollApi.getSalaryStructures(urls.salaryStructures, query)
            .then((data) => {
                setRows(Array.isArray(data?.rows) ? data.rows : []);
                setSummary(data?.summary ?? null);
                setTotals(data?.totals ?? { gross: 0 });
                setPagination(data?.pagination ?? { currentPage: 1, lastPage: 1, total: 0, perPage: 15 });
            })
            .catch(() => {
                setRows([]);
                setSummary(null);
                setTotals({ gross: 0 });
                setError('Unable to load salary structures.');
            })
            .finally(() => {
                setLoading(false);
            });
    };

    useEffect(() => {
        loadStructures();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [query]);

    const openEditor = (row) => {
        setEditorRow(row);
        setFormError('');
        setFormSuccess('');
        setAdvancedOpen(false);
        setForm({
            basic_salary: toNumberString(row?.structure?.basic_salary ?? ''),
            hra: toNumberString(row?.structure?.hra ?? ''),
            special_allowance: toNumberString(row?.structure?.special_allowance ?? ''),
            bonus: toNumberString(row?.structure?.bonus ?? ''),
            other_allowance: toNumberString(row?.structure?.other_allowance ?? ''),
            pf_deduction: toNumberString(row?.structure?.pf_deduction ?? ''),
            tax_deduction: toNumberString(row?.structure?.tax_deduction ?? ''),
            other_deduction: toNumberString(row?.structure?.other_deduction ?? ''),
            effective_from: row?.structure?.effective_from ?? '',
            notes: row?.structure?.notes ?? '',
        });
        setEditorOpen(true);

        const historyUrl = String(urls.structureHistory || '').replace('__USER_ID__', String(row.employeeId));
        if (!historyUrl) {
            setHistory([]);
            return;
        }

        setHistoryLoading(true);
        payrollApi.getStructureHistory(historyUrl)
            .then((data) => {
                setHistory(Array.isArray(data?.history) ? data.history : []);
            })
            .catch(() => {
                setHistory([]);
            })
            .finally(() => {
                setHistoryLoading(false);
            });
    };

    const validateForm = () => {
        if (String(form.basic_salary).trim() === '') {
            return 'Basic salary is required.';
        }

        if (String(form.effective_from).trim() === '') {
            return 'Effective from date is required.';
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

        for (const field of numericFields) {
            if (Number(form[field] || 0) < 0) {
                return 'Negative values are not allowed.';
            }
        }

        return '';
    };

    const onSave = () => {
        if (!editorRow) {
            return;
        }

        const validationMessage = validateForm();
        if (validationMessage) {
            setFormError(validationMessage);
            return;
        }

        setSaving(true);
        setFormError('');
        setFormSuccess('');

        const endpoint = String(urls.structureUpsert || '').replace('__USER_ID__', String(editorRow.employeeId));

        payrollApi.upsertStructure(endpoint, toPayload(form), csrfToken)
            .then((data) => {
                setFormSuccess(String(data?.message || 'Structure saved successfully.'));
                loadStructures();
                const historyUrl = String(urls.structureHistory || '').replace('__USER_ID__', String(editorRow.employeeId));
                return payrollApi.getStructureHistory(historyUrl);
            })
            .then((historyData) => {
                setHistory(Array.isArray(historyData?.history) ? historyData.history : []);
            })
            .catch((apiError) => {
                const message = apiError?.response?.data?.message || 'Unable to save salary structure.';
                setFormError(String(message));
            })
            .finally(() => {
                setSaving(false);
                setConfirmOpen(false);
            });
    };

    return (
        <div className="space-y-5">
            <section className="ui-section">
                <SectionHeader title="Salary Structure Overview" subtitle="Configuration health and salary baseline across selected employees." />
                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <InfoCard label="Total Employees" value={loading ? '...' : formatCount(summary?.totalEmployees ?? 0)} />
                    <InfoCard label="With Structure" value={loading ? '...' : formatCount(summary?.withStructure ?? 0)} tone="success" />
                    <InfoCard label="Missing Structure" value={loading ? '...' : formatCount(summary?.missingStructure ?? 0)} tone="warning" />
                    <InfoCard label="Average Gross Salary" value={loading ? '...' : formatMoney(summary?.averageGrossSalary ?? 0)} tone="info" />
                </div>
            </section>

            <section className="ui-section">
                <SectionHeader
                    title="Salary Structure Directory"
                    subtitle="View and manage salary structures with effective-date history."
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <input
                                type="search"
                                className="ui-input"
                                placeholder="Search employee name or email"
                                value={search}
                                onChange={(event) => {
                                    setPage(1);
                                    setSearch(event.target.value);
                                }}
                            />
                            <select
                                className="ui-select"
                                value={status}
                                onChange={(event) => {
                                    setPage(1);
                                    setStatus(event.target.value);
                                }}
                            >
                                <option value="all">All Status</option>
                                <option value="with_structure">With Structure</option>
                                <option value="missing_structure">Missing Structure</option>
                                <option value="missing_bank">Missing Bank Details</option>
                            </select>
                        </div>
                    }
                />

                {error ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}

                <div className="ui-table-wrap">
                    <table className="ui-table">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Gross Salary</th>
                                <th>Effective From</th>
                                <th>Last Updated</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <TableEmptyState loading={loading} error={error} colSpan={7} emptyMessage="No salary structures found." />
                            ) : rows.map((row) => (
                                <tr key={`structure-row-${row.employeeId}`}>
                                    <td>
                                        <p className="font-semibold">{row.employeeName}</p>
                                        <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{row.email}</p>
                                    </td>
                                    <td>{row.department || 'N/A'}</td>
                                    <td>{formatMoney(row.grossSalary)}</td>
                                    <td>{row.effectiveFrom || 'N/A'}</td>
                                    <td>{formatDateTime(row.lastUpdated)}</td>
                                    <td><StatusBadge status={row.status === 'with_structure' ? 'approved' : 'failed'} /></td>
                                    <td>
                                        <button type="button" className="ui-btn ui-btn-ghost" onClick={() => openEditor(row)}>
                                            View / Edit
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        {rows.length > 0 ? (
                            <tfoot>
                                <tr>
                                    <td className="font-bold" colSpan={2}>Page Totals</td>
                                    <td className="font-bold">{formatMoney(totals?.gross ?? 0)}</td>
                                    <td colSpan={4}></td>
                                </tr>
                            </tfoot>
                        ) : null}
                    </table>
                </div>

                <div className="mt-4 flex items-center justify-between">
                    <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        Showing page {pagination.currentPage} of {pagination.lastPage} ({formatCount(pagination.total)} records)
                    </p>
                    <div className="flex items-center gap-2">
                        <button type="button" className="ui-btn ui-btn-ghost" disabled={pagination.currentPage <= 1} onClick={() => setPage((prev) => Math.max(1, prev - 1))}>Previous</button>
                        <button type="button" className="ui-btn ui-btn-ghost" disabled={pagination.currentPage >= pagination.lastPage} onClick={() => setPage((prev) => Math.min(pagination.lastPage, prev + 1))}>Next</button>
                    </div>
                </div>
            </section>

            <AppModalPortal open={editorOpen} onBackdropClick={saving ? null : () => setEditorOpen(false)}>
                <div className="app-modal-panel w-full max-w-5xl p-5" role="dialog" aria-modal="true">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h4 className="text-lg font-extrabold">Edit Salary Structure</h4>
                                <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>{editorRow?.employeeName} ({editorRow?.email})</p>
                            </div>
                            <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setEditorOpen(false)}>Close</button>
                        </div>

                        {formError ? <p className="mt-3 text-sm text-red-600">{formError}</p> : null}
                        {formSuccess ? <p className="mt-3 text-sm text-green-700">{formSuccess}</p> : null}

                        <div className="mt-4 grid gap-3 md:grid-cols-3">
                            <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Basic Salary *
                                <input className="ui-input mt-1" type="number" min="0" value={form.basic_salary} onChange={(event) => setForm((prev) => ({ ...prev, basic_salary: event.target.value }))} />
                            </label>
                            <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                HRA
                                <input className="ui-input mt-1" type="number" min="0" value={form.hra} onChange={(event) => setForm((prev) => ({ ...prev, hra: event.target.value }))} />
                            </label>
                            <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Special Allowance
                                <input className="ui-input mt-1" type="number" min="0" value={form.special_allowance} onChange={(event) => setForm((prev) => ({ ...prev, special_allowance: event.target.value }))} />
                            </label>
                            <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Bonus
                                <input className="ui-input mt-1" type="number" min="0" value={form.bonus} onChange={(event) => setForm((prev) => ({ ...prev, bonus: event.target.value }))} />
                            </label>
                            <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Other Allowance
                                <input className="ui-input mt-1" type="number" min="0" value={form.other_allowance} onChange={(event) => setForm((prev) => ({ ...prev, other_allowance: event.target.value }))} />
                            </label>
                            <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                Effective From *
                                <input className="ui-input mt-1" type="date" value={form.effective_from} onChange={(event) => setForm((prev) => ({ ...prev, effective_from: event.target.value }))} />
                            </label>
                        </div>

                        <button type="button" className="mt-4 ui-btn ui-btn-ghost" onClick={() => setAdvancedOpen((prev) => !prev)}>
                            {advancedOpen ? 'Hide Advanced Options' : 'Show Advanced Options'}
                        </button>

                        {advancedOpen ? (
                            <div className="mt-3 grid gap-3 md:grid-cols-3">
                                <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                    PF Deduction
                                    <input className="ui-input mt-1" type="number" min="0" value={form.pf_deduction} onChange={(event) => setForm((prev) => ({ ...prev, pf_deduction: event.target.value }))} />
                                </label>
                                <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                    Tax Deduction
                                    <input className="ui-input mt-1" type="number" min="0" value={form.tax_deduction} onChange={(event) => setForm((prev) => ({ ...prev, tax_deduction: event.target.value }))} />
                                </label>
                                <label className="text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                                    Other Deduction
                                    <input className="ui-input mt-1" type="number" min="0" value={form.other_deduction} onChange={(event) => setForm((prev) => ({ ...prev, other_deduction: event.target.value }))} />
                                </label>
                            </div>
                        ) : null}

                        <label className="mt-3 block text-xs font-semibold uppercase tracking-[0.08em]" style={{ color: 'var(--hr-text-muted)' }}>
                            Notes
                            <textarea className="ui-textarea mt-1" rows="3" value={form.notes} onChange={(event) => setForm((prev) => ({ ...prev, notes: event.target.value }))}></textarea>
                        </label>

                        <div className="mt-4 flex justify-end gap-2">
                            <button type="button" className="ui-btn ui-btn-ghost" onClick={() => setEditorOpen(false)}>Cancel</button>
                            <button type="button" className="ui-btn ui-btn-primary" onClick={() => setConfirmOpen(true)} disabled={saving}>
                                {saving ? 'Saving...' : 'Save Structure'}
                            </button>
                        </div>

                        <section className="mt-5 rounded-2xl border p-4" style={{ borderColor: 'var(--hr-line)' }}>
                            <h5 className="text-sm font-extrabold">Change History</h5>
                            {historyLoading ? <p className="mt-2 text-sm">Loading history...</p> : null}
                            {!historyLoading && history.length === 0 ? <p className="mt-2 text-sm" style={{ color: 'var(--hr-text-muted)' }}>No change history found.</p> : null}
                            <div className="mt-2 space-y-2">
                                {history.map((entry) => (
                                    <article key={`history-${entry.id}`} className="rounded-xl border p-3" style={{ borderColor: 'var(--hr-line)', background: 'var(--hr-surface)' }}>
                                        <p className="text-xs" style={{ color: 'var(--hr-text-muted)' }}>{formatDateTime(entry.changedAt)} • {entry.changedBy}</p>
                                        <div className="mt-2 space-y-1">
                                            {(Array.isArray(entry.changeSummary) ? entry.changeSummary : []).map((change, index) => (
                                                <p key={`change-${entry.id}-${index}`} className="text-xs" style={{ color: 'var(--hr-text-main)' }}>
                                                    {change.field}: {String(change.from ?? 'N/A')} → {String(change.to ?? 'N/A')}
                                                </p>
                                            ))}
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </section>
                </div>
            </AppModalPortal>

            <ConfirmModal
                open={confirmOpen}
                title="Confirm Salary Structure Update"
                body="This change will update salary calculations and be tracked in the change history audit."
                confirmLabel="Confirm Save"
                busy={saving}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={onSave}
            />
        </div>
    );
}
