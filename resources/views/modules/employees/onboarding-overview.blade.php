@extends('layouts.dashboard-modern')

@section('title', 'Employee Onboarding Overview')
@section('page_heading', 'Employee Onboarding Overview')

@section('content')
    @php
        $clientPayload = array_merge($overviewPayload ?? [], [
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
        ]);
    @endphp

    <div id="employee-onboarding-overview-root" data-payload='@json($clientPayload)'>
        <section class="hrm-modern-surface rounded-2xl p-5">
            <p class="text-sm" style="color: var(--hr-text-muted);">Loading employee onboarding overview...</p>
        </section>
    </div>
@endsection
