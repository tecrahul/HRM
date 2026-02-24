@extends('layouts.dashboard-modern')

@section('title', 'SMTP Settings')
@section('page_heading', 'SMTP Settings')

@section('content')
    <div class="flex flex-col gap-4">
        <p class="text-sm" style="color: var(--hr-text-muted);">Configure how the system sends transactional emails.</p>
        <div id="smtp-settings-root" data-payload='@json($pagePayload)'></div>
    </div>
@endsection
