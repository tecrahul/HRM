import React, { useEffect, useMemo, useState } from 'react';
import { payrollApi } from '../api';
import {
    InfoCard,
    SectionHeader,
    StatusBadge,
    TableEmptyState,
    formatCount,
    formatDateTime,
    formatMoney,
    useDebouncedValue,
} from '../shared/ui';

export function HistoryPage({ urls, routes, filters, initialStatus = '' }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [summary, setSummary] = useState(null);
    const [rows, setRows] = useState([]);
    const [totals, setTotals] = useState({ gross: 0, deductions: 0, net: 0 });
    const [pagination, setPagination] = useState({ currentPage: 1, lastPage: 1, total: 0 });

    const [search, setSearch] = useState('');
    const [status, setStatus] = useState(() => {
        const allowed = ['', 'generated', 'approved', 'paid', 'failed'];
        return allowed.includes(initialStatus) ? initialStatus : '';
    });
    const [page, setPage] = useState(1);

    const debouncedSearch = useDebouncedValue(search, 300);

    const query = useMemo(() => ({
        branch_id: filters.branchId || '',
        department_id: filters.departmentId || '',
        employee_id: filters.employeeId || '',
        q: debouncedSearch,
        status,
        page,
    }), [filters.branchId, filters.departmentId, filters.employeeId, debouncedSearch, status, page]);

    useEffect(() => {
        setLoading(true);
        setError('');

        payrollApi.getHistory(urls.payrollHistory, query)
            .then((data) => {
                setSummary(data?.summary ?? null);
                setRows(Array.isArray(data?.rows) ? data.rows : []);
                setTotals(data?.totals ?? { gross: 0, deductions: 0, net: 0 });
                setPagination(data?.pagination ?? { currentPage: 1, lastPage: 1, total: 0 });
            })
            .catch(() => {
                setSummary(null);
                setRows([]);
                setTotals({ gross: 0, deductions: 0, net: 0 });
                setError('Unable to load payroll history.');
            })
            .finally(() => {
                setLoading(false);
            });
    }, [query, urls.payrollHistory]);

    const csvUrl = useMemo(() => {
        const params = new URLSearchParams();
        params.set('payroll_month', filters.payrollMonth || '');
        params.set('q', debouncedSearch || '');
        params.set('status', status || '');

        return `${urls.directoryExportCsv}?${params.toString()}`;
    }, [urls.directoryExportCsv, filters.payrollMonth, debouncedSearch, status]);

    return (
        <div className="space-y-5">
            <section className="ui-section">
                <SectionHeader title="Payroll History Summary" subtitle="Monthly payroll output and cost trends." />
                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <InfoCard label="Total Months Processed" value={loading ? '...' : formatCount(summary?.totalMonthsProcessed ?? 0)} />
                    <InfoCard label="YTD Payroll Cost" value={loading ? '...' : formatMoney(summary?.ytdPayrollCost ?? 0)} tone="success" />
                    <InfoCard label="Last Processed Month" value={loading ? '...' : String(summary?.lastProcessedMonth ?? 'N/A')} />
                </div>
            </section>

            <section className="ui-section">
                <SectionHeader
                    title="Payroll History Table"
                    subtitle="Month-wise processed payroll history with quick drill-down."
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <input
                                type="search"
                                className="ui-input"
                                placeholder="Search employee or reference"
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
                                <option value="">All Status</option>
                                <option value="generated">Generated</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                            <a className="ui-btn ui-btn-ghost" href={csvUrl}>Export CSV</a>
                        </div>
                    }
                />

                {error ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}

                <div className="ui-table-wrap mt-4">
                    <table className="ui-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Employees</th>
                                <th>Gross</th>
                                <th>Net</th>
                                <th>Status</th>
                                <th>Processed By</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <TableEmptyState loading={loading} error={error} colSpan={8} emptyMessage="No payroll history found." />
                            ) : rows.map((row) => (
                                <tr key={`history-row-${row.month}`}>
                                    <td>{row.monthLabel}</td>
                                    <td>{formatCount(row.totalEmployees)}</td>
                                    <td>{formatMoney(row.gross)}</td>
                                    <td>{formatMoney(row.net)}</td>
                                    <td><StatusBadge status={String(row.status || '').toLowerCase()} /></td>
                                    <td>{row.processedBy || 'System'}</td>
                                    <td>{formatDateTime(row.processedAt)}</td>
                                    <td>
                                        <a
                                            href={`${routes.processing}?payroll_month=${encodeURIComponent(String(row.month || ''))}`}
                                            className="ui-btn ui-btn-ghost"
                                        >
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        {rows.length > 0 ? (
                            <tfoot>
                                <tr>
                                    <td className="font-bold" colSpan={2}>Page Totals</td>
                                    <td className="font-bold">{formatMoney(totals.gross)}</td>
                                    <td className="font-bold">{formatMoney(totals.net)}</td>
                                    <td colSpan={4}></td>
                                </tr>
                            </tfoot>
                        ) : null}
                    </table>
                </div>

                <div className="mt-4 flex items-center justify-between">
                    <p className="text-sm" style={{ color: 'var(--hr-text-muted)' }}>
                        Page {pagination.currentPage} of {pagination.lastPage} ({formatCount(pagination.total)} records)
                    </p>
                    <div className="flex gap-2">
                        <button type="button" className="ui-btn ui-btn-ghost" disabled={pagination.currentPage <= 1} onClick={() => setPage((prev) => Math.max(1, prev - 1))}>Previous</button>
                        <button type="button" className="ui-btn ui-btn-ghost" disabled={pagination.currentPage >= pagination.lastPage} onClick={() => setPage((prev) => Math.min(pagination.lastPage, prev + 1))}>Next</button>
                    </div>
                </div>
            </section>
        </div>
    );
}
