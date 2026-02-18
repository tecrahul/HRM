@extends('layouts.dashboard-modern')

@section('title', 'Payroll')
@section('page_heading', 'Payroll Management')

@section('content')
    @php
        $clientPayload = array_merge($managementPayload ?? [], [
            'csrfToken' => csrf_token(),
            'flash' => [
                'status' => session('status'),
                'error' => session('error'),
            ],
            'validation' => [
                'hasErrors' => $errors->any(),
                'messages' => $errors->all(),
                'fieldErrors' => $errors->toArray(),
            ],
            'oldInput' => [
                'user_id' => old('user_id'),
                'basic_salary' => old('basic_salary'),
                'hra' => old('hra'),
                'special_allowance' => old('special_allowance'),
                'bonus' => old('bonus'),
                'other_allowance' => old('other_allowance'),
                'pf_deduction' => old('pf_deduction'),
                'tax_deduction' => old('tax_deduction'),
                'other_deduction' => old('other_deduction'),
                'effective_from' => old('effective_from'),
                'payroll_month' => old('payroll_month', data_get($managementPayload ?? [], 'filters.payroll_month')),
                'payable_days' => old('payable_days'),
                'notes' => old('notes'),
                'status' => old('status'),
                'payment_method' => old('payment_method'),
                'payment_reference' => old('payment_reference'),
            ],
        ]);
    @endphp

    <div id="admin-payroll-management-root" data-payload='@json($clientPayload)'>
        <section class="ui-section">
            <p class="ui-section-subtitle">Loading payroll management...</p>
        </section>
    </div>
@endsection
