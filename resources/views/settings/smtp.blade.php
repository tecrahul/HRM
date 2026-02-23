@extends('layouts.dashboard-modern')

@section('title', 'SMTP Settings')
@section('page_heading', 'SMTP Settings')

@section('content')
    <div class="flex flex-col gap-4">
        <div class="rounded-2xl border px-4 py-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
            <p class="text-sm font-semibold">Manage SMTP Connectivity</p>
            <p class="text-xs mt-1" style="color: var(--hr-text-muted);">Configure how the platform sends transactional emails. System mode reads from your server's .env file, while custom mode lets administrators override settings securely.</p>
        </div>
        <div id="smtp-settings-root" data-payload='@json($pagePayload)'></div>
    </div>
@endsection
