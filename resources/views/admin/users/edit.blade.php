@extends('layouts.dashboard-modern')

@section('title', 'Edit User')
@section('page_heading', 'Edit User')

@section('content')
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
