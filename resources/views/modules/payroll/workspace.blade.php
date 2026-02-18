@extends('layouts.dashboard-modern')

@section('title', $pageHeading)
@section('page_heading', $pageHeading)

@section('content')
    @php
        $clientPayload = array_merge($payrollWorkspacePayload ?? [], [
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

    <div id="payroll-workspace-root" data-payload='@json($clientPayload)'>
        <section class="ui-section">
            <p class="ui-section-subtitle">Loading payroll workspace...</p>
        </section>
    </div>
@endsection
