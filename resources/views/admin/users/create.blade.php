@extends('layouts.dashboard-modern')

@section('title', 'Create User')
@section('page_heading', 'Create User')

@section('content')
    @if ($errors->any())
        <section class="hrm-modern-surface rounded-2xl p-4">
            <p class="text-sm font-semibold text-red-600">Please fix the highlighted fields and submit again.</p>
        </section>
    @endif

    @include('admin.users.partials.form', [
        'action' => route('admin.users.store'),
        'method' => 'POST',
        'submitLabel' => 'Create User',
        'managedUser' => null,
    ])
@endsection
