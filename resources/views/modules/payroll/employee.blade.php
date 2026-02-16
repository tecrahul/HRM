@extends('layouts.dashboard-modern')

@section('title', 'Payroll')
@section('page_heading', 'My Payroll')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">{{ session('error') }}</div>
    @endif

    <section class="ui-kpi-grid is-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Paid Slips</p>
                    <p class="ui-kpi-value">{{ $stats['paidCount'] }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg></span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Net Paid (Year)</p>
                    <p class="ui-kpi-value">{{ number_format((float) $stats['thisYearNet'], 2) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-green"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg></span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Latest Net</p>
                    <p class="ui-kpi-value">{{ number_format((float) $stats['lastNet'], 2) }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-sky"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg></span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kpi-label">Latest Status</p>
                    <p class="ui-kpi-value">{{ str($stats['lastStatus'])->replace('_', ' ')->title() }}</p>
                </div>
                <span class="ui-icon-chip ui-icon-violet"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="9"></circle></svg></span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Payslip History</h3>
                    <p class="ui-section-subtitle">Track all generated payroll records for your account.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('modules.payroll.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <input
                    type="month"
                    name="payroll_month"
                    value="{{ $filters['payroll_month'] }}"
                    class="ui-input"
                >
                <select name="status" class="ui-select">
                    <option value="">All Status</option>
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" {{ $filters['status'] === $statusOption ? 'selected' : '' }}>
                            {{ str($statusOption)->replace('_', ' ')->title() }}
                        </option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
                    <a href="{{ route('modules.payroll.index') }}" class="ui-btn ui-btn-ghost">Reset</a>
                </div>
            </form>

            <div class="ui-table-wrap">
                <table class="ui-table" style="min-width: 980px;">
                    <thead>
                    <tr>
                        <th>Month</th>
                        <th>Payable Days</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net</th>
                        <th>Status</th>
                        <th>Payment</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $record)
                        @php
                            $statusStyles = match ($record->status) {
                                'paid' => 'color:#15803d;background:rgb(34 197 94 / 0.16);',
                                'processed' => 'color:#1d4ed8;background:rgb(59 130 246 / 0.18);',
                                default => 'color:#b45309;background:rgb(245 158 11 / 0.18);',
                            };
                        @endphp
                        <tr>
                            <td>{{ $record->payroll_month?->format('M Y') }}</td>
                            <td>{{ number_format((float) $record->payable_days, 2) }} / {{ number_format((float) $record->working_days, 2) }}</td>
                            <td>{{ number_format((float) $record->gross_earnings, 2) }}</td>
                            <td>{{ number_format((float) $record->total_deductions, 2) }}</td>
                            <td class="font-bold">{{ number_format((float) $record->net_salary, 2) }}</td>
                            <td>
                                <span class="ui-status-chip" style="{{ $statusStyles }}">
                                    {{ str($record->status)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td>
                                <p>{{ $record->payment_method ? str($record->payment_method)->replace('_', ' ')->title() : 'N/A' }}</p>
                                <p class="text-xs" style="color: var(--hr-text-muted);">{{ $record->paid_at?->format('M d, Y') ?? 'Not paid' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="ui-empty">No payroll records found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $records->links() }}
            </div>
        </article>

        <article class="ui-section">
            <div class="ui-section-head">
                <div>
                    <h3 class="ui-section-title">Latest Payslip</h3>
                    <p class="ui-section-subtitle">Breakdown from most recent generated payroll.</p>
                </div>
            </div>

            @if($latestPayroll)
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <p class="font-semibold">{{ $latestPayroll->payroll_month?->format('F Y') }}</p>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Payable days {{ number_format((float) $latestPayroll->payable_days, 2) }}</p>
                    </li>
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <p class="font-semibold">Earnings</p>
                        <p class="text-xs mt-2" style="color: var(--hr-text-muted);">Basic: {{ number_format((float) $latestPayroll->basic_pay, 2) }}</p>
                        <p class="text-xs" style="color: var(--hr-text-muted);">HRA: {{ number_format((float) $latestPayroll->hra, 2) }}</p>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Special: {{ number_format((float) $latestPayroll->special_allowance, 2) }}</p>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Bonus: {{ number_format((float) $latestPayroll->bonus, 2) }}</p>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Other: {{ number_format((float) $latestPayroll->other_allowance, 2) }}</p>
                        <p class="text-sm mt-2 font-bold">Gross: {{ number_format((float) $latestPayroll->gross_earnings, 2) }}</p>
                    </li>
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <p class="font-semibold">Deductions</p>
                        <p class="text-xs mt-2" style="color: var(--hr-text-muted);">PF: {{ number_format((float) $latestPayroll->pf_deduction, 2) }}</p>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Tax: {{ number_format((float) $latestPayroll->tax_deduction, 2) }}</p>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Other: {{ number_format((float) $latestPayroll->other_deduction, 2) }}</p>
                        <p class="text-sm mt-2 font-bold">Net: {{ number_format((float) $latestPayroll->net_salary, 2) }}</p>
                    </li>
                </ul>
            @else
                <div class="ui-empty mt-4 rounded-xl border" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    No payroll generated for your account yet.
                </div>
            @endif
        </article>
    </section>
@endsection
