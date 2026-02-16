@extends('layouts.dashboard-modern')

@section('title', 'Payroll')
@section('page_heading', 'Payroll Management')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="ui-alert ui-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">
            Please fix payroll form errors and try again.
        </div>
    @endif

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Employees</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['totalEmployees'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(59 130 246 / 0.16); color: #2563eb;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">With Structure</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['employeesWithStructure'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(124 58 237 / 0.16); color: #7c3aed;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Generated (Month)</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['generatedThisMonth'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(14 165 233 / 0.16); color: #0284c7;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Paid (Month)</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['paidThisMonth'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(34 197 94 / 0.16); color: #15803d;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Pending (Month)</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ $stats['pendingThisMonth'] }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(245 158 11 / 0.16); color: #d97706;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2"></path><circle cx="12" cy="12" r="9"></circle></svg>
                </span>
            </div>
        </article>
        <article class="ui-kpi-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.1em] font-semibold" style="color: var(--hr-text-muted);">Net Payout (Month)</p>
                    <p class="mt-2 text-3xl font-extrabold">{{ number_format((float) $stats['netThisMonth'], 2) }}</p>
                </div>
                <span class="h-10 w-10 rounded-xl flex items-center justify-center" style="background: rgb(16 185 129 / 0.16); color: #059669;">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 13l4-4 3 3 5-6"></path></svg>
                </span>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <article class="ui-section xl:col-span-2">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Payroll Setup</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Configure salary structure and run payroll for monthly payslips.</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-5">
                <form method="POST" action="{{ route('modules.payroll.structure.store') }}" class="rounded-2xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    @csrf
                    <h4 class="text-sm font-bold">Salary Structure</h4>
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Create or update standard monthly salary components.</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="structure_user_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Employee</label>
                            <select id="structure_user_id" name="user_id" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                                <option value="">Select employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ (string) old('user_id') === (string) $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }} ({{ $employee->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="basic_salary" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Basic Salary</label>
                                <input id="basic_salary" name="basic_salary" type="number" min="0" step="0.01" value="{{ old('basic_salary') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                                @error('basic_salary')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="hra" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">HRA</label>
                                <input id="hra" name="hra" type="number" min="0" step="0.01" value="{{ old('hra') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                                @error('hra')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="special_allowance" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Special Allowance</label>
                                <input id="special_allowance" name="special_allowance" type="number" min="0" step="0.01" value="{{ old('special_allowance') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                            </div>
                            <div>
                                <label for="bonus" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Bonus</label>
                                <input id="bonus" name="bonus" type="number" min="0" step="0.01" value="{{ old('bonus') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                            </div>
                            <div>
                                <label for="other_allowance" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Other Allowance</label>
                                <input id="other_allowance" name="other_allowance" type="number" min="0" step="0.01" value="{{ old('other_allowance') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                            </div>
                            <div>
                                <label for="pf_deduction" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">PF Deduction</label>
                                <input id="pf_deduction" name="pf_deduction" type="number" min="0" step="0.01" value="{{ old('pf_deduction') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                            </div>
                            <div>
                                <label for="tax_deduction" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Tax Deduction</label>
                                <input id="tax_deduction" name="tax_deduction" type="number" min="0" step="0.01" value="{{ old('tax_deduction') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                            </div>
                            <div>
                                <label for="other_deduction" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Other Deduction</label>
                                <input id="other_deduction" name="other_deduction" type="number" min="0" step="0.01" value="{{ old('other_deduction') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="0.00">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="effective_from" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Effective From</label>
                                <input id="effective_from" name="effective_from" type="date" value="{{ old('effective_from') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                            </div>
                        </div>

                        <div>
                            <label for="structure_notes" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Notes</label>
                            <textarea id="structure_notes" name="notes" rows="2" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                            Save Structure
                        </button>
                    </div>
                </form>

                <div class="rounded-2xl border p-4 flex flex-col gap-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div>
                        <h4 class="text-sm font-bold">Generate Payroll</h4>
                        <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Create or recalculate payroll for a selected month.</p>
                    </div>

                    <form method="POST" action="{{ route('modules.payroll.generate') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="generate_user_id" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Employee</label>
                            <select id="generate_user_id" name="user_id" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                                <option value="">Select employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="generate_payroll_month" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Payroll Month</label>
                                <input id="generate_payroll_month" name="payroll_month" type="month" value="{{ old('payroll_month', $filters['payroll_month']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                            </div>
                            <div>
                                <label for="generate_payable_days" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Payable Days (Optional)</label>
                                <input id="generate_payable_days" name="payable_days" type="number" min="0" step="0.5" value="{{ old('payable_days') }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);" placeholder="Auto">
                            </div>
                        </div>
                        <div>
                            <label for="generate_notes" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Notes</label>
                            <textarea id="generate_notes" name="notes" rows="2" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-white" style="background: linear-gradient(120deg, #7c3aed, #ec4899);">
                            Generate Payroll
                        </button>
                    </form>

                    <hr style="border-color: var(--hr-line);">

                    <form method="POST" action="{{ route('modules.payroll.generate-bulk') }}" class="space-y-3">
                        @csrf
                        <h4 class="text-sm font-bold">Bulk Generate</h4>
                        <p class="text-xs" style="color: var(--hr-text-muted);">Run payroll for all employees having salary structure.</p>
                        <div>
                            <label for="bulk_payroll_month" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Payroll Month</label>
                            <input id="bulk_payroll_month" name="payroll_month" type="month" value="{{ old('payroll_month', $filters['payroll_month']) }}" class="w-full rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                        </div>
                        <div>
                            <label for="bulk_notes" class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Notes</label>
                            <textarea id="bulk_notes" name="notes" rows="2" class="w-full rounded-xl border px-3 py-2.5 bg-transparent resize-y" style="border-color: var(--hr-line);"></textarea>
                        </div>
                        <button type="submit" class="rounded-xl px-3.5 py-2 text-sm font-semibold border" style="border-color: var(--hr-line);">
                            Generate For All
                        </button>
                    </form>
                </div>
            </div>
        </article>

        <article class="ui-section">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Salary Structures</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Latest monthly gross configured per employee.</p>
                </div>
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @forelse($structures->take(8) as $structure)
                    @php
                        $grossConfigured = (float) $structure->basic_salary
                            + (float) $structure->hra
                            + (float) $structure->special_allowance
                            + (float) $structure->bonus
                            + (float) $structure->other_allowance;
                    @endphp
                    <li class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold">{{ $structure->user?->name }}</p>
                            <span class="text-xs font-bold uppercase tracking-[0.08em] rounded-full px-2 py-1" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                                {{ number_format($grossConfigured, 2) }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">
                            {{ $structure->user?->profile?->department ?? 'No Department' }} •
                            Effective {{ $structure->effective_from?->format('M d, Y') ?? 'Immediate' }}
                        </p>
                    </li>
                @empty
                    <li class="rounded-xl border p-3 text-sm" style="border-color: var(--hr-line); background: var(--hr-surface-strong); color: var(--hr-text-muted);">
                        No salary structures saved yet.
                    </li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="ui-section">
        <div class="flex items-center gap-2">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5"></path><path d="M5 9.9V21h14V9.9"></path></svg>
            </span>
            <div>
                <h3 class="text-lg font-extrabold">Payroll Directory</h3>
                <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Review generated payslips and update processing/payment status.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('modules.payroll.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
            <input
                type="text"
                name="q"
                value="{{ $filters['q'] }}"
                placeholder="Search employee, reference, notes..."
                class="md:col-span-2 rounded-xl border px-3 py-2.5 bg-transparent"
                style="border-color: var(--hr-line);"
            >

            <select name="employee_id" class="rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ (string) $filters['employee_id'] === (string) $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>

            <input type="month" name="payroll_month" value="{{ $filters['payroll_month'] }}" class="rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">

            <select name="status" class="rounded-xl border px-3 py-2.5 bg-transparent" style="border-color: var(--hr-line);">
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
            <table class="ui-table" style="min-width: 1400px;">
                <thead>
                <tr>
                    <th>Month</th>
                    <th>Employee</th>
                    <th>Days</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Action</th>
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
                        <td>
                            <p class="font-semibold">{{ $record->user?->name }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $record->user?->email }}</p>
                        </td>
                        <td>
                            <p>Payable {{ number_format((float) $record->payable_days, 2) }} / {{ number_format((float) $record->working_days, 2) }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">LOP {{ number_format((float) $record->lop_days, 2) }}</p>
                        </td>
                        <td>{{ number_format((float) $record->gross_earnings, 2) }}</td>
                        <td>{{ number_format((float) $record->total_deductions, 2) }}</td>
                        <td class="font-bold">{{ number_format((float) $record->net_salary, 2) }}</td>
                        <td>
                            <span class="ui-status-chip" style="{{ $statusStyles }}">
                                {{ str($record->status)->replace('_', ' ')->title() }}
                            </span>
                            <p class="text-xs mt-1" style="color: var(--hr-text-muted);">By {{ $record->generator?->name ?? 'System' }}</p>
                        </td>
                        <td>
                            <p>{{ $record->payment_method ? str($record->payment_method)->replace('_', ' ')->title() : 'N/A' }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $record->paid_at?->format('M d, Y h:i A') ?? 'Not paid' }}</p>
                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $record->payment_reference ?: 'No reference' }}</p>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('modules.payroll.status.update', $record) }}" class="grid grid-cols-1 gap-2">
                                @csrf
                                @method('PUT')
                                <select name="status" class="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent" style="border-color: var(--hr-line);">
                                    @foreach($statusOptions as $statusOption)
                                        <option value="{{ $statusOption }}" {{ $record->status === $statusOption ? 'selected' : '' }}>
                                            {{ str($statusOption)->replace('_', ' ')->title() }}
                                        </option>
                                    @endforeach
                                </select>
                                <select name="payment_method" class="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent" style="border-color: var(--hr-line);">
                                    <option value="">Payment method</option>
                                    @foreach($paymentMethodOptions as $paymentMethodOption)
                                        <option value="{{ $paymentMethodOption }}" {{ $record->payment_method === $paymentMethodOption ? 'selected' : '' }}>
                                            {{ str($paymentMethodOption)->replace('_', ' ')->title() }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="text" name="payment_reference" value="{{ $record->payment_reference }}" placeholder="Reference" class="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent" style="border-color: var(--hr-line);">
                                <input type="text" name="notes" value="{{ $record->notes }}" placeholder="Internal note" class="rounded-lg border px-2.5 py-1.5 text-xs bg-transparent" style="border-color: var(--hr-line);">
                                <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold border" style="border-color: var(--hr-line);">
                                    Update
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="ui-empty">No payroll records found for selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $records->links() }}
        </div>
    </section>
@endsection
