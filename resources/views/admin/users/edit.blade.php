@extends('layouts.dashboard-modern')

@section('title', 'Edit User')
@section('page_heading', 'Edit User')

@section('content')
    <section class="hrm-modern-surface rounded-2xl p-4 mb-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold">Want to continue the setup wizard?</p>
                <p class="text-xs" style="color: var(--hr-text-muted);">Jump back into the employment/personal steps.</p>
            </div>
            <a href="{{ route('admin.users.create', ['user' => $managedUser->id, 'step' => 2]) }}" class="ui-btn ui-btn-primary">Continue Wizard</a>
        </div>
    </section>
    @if ($errors->any())
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-red-600">Please fix the highlighted fields and submit again.</p>
        </section>
    @endif

    @include('admin.users.partials.form', [
        'action' => route('admin.users.update', $managedUser),
        'method' => 'PUT',
        'submitLabel' => 'Update User',
        'managedUser' => $managedUser,
    ])
@endsection

@push('scripts')
    @includeIf('auth.partials.inline-validation-script')
@endpush
