@extends('layouts.dashboard-modern')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li>
                    <a href="{{ route('settings.roles-permissions.index') }}" class="text-gray-400 hover:text-gray-500">
                        <svg class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L4.414 9H17a1 1 0 110 2H4.414l5.293 5.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.roles-permissions.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Roles & Permissions</a>
                </li>
                <li>
                    <span class="text-sm font-medium text-gray-500">/</span>
                </li>
                <li>
                    <span class="text-sm font-medium text-gray-900">System Health</span>
                </li>
            </ol>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">RBAC System Health</h1>
                <p class="mt-2 text-sm text-gray-600">Check the health and configuration of the RBAC system</p>
            </div>
            @if($isHealthy)
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    System Healthy
                </span>
            @else
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    Issues Found
                </span>
            @endif
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Roles</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_roles'] }}</dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500 truncate">Total Permissions</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_permissions'] }}</dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500 truncate">Users with Roles</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_users_with_roles'] }}</dd>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <dt class="text-sm font-medium text-gray-500 truncate">Role-Permission Links</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_role_permission_assignments'] }}</dd>
            </div>
        </div>
    </div>

    <!-- Health Check Output -->
    <div class="bg-white shadow overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Health Check Report</h3>
            <p class="mt-1 text-sm text-gray-500">Detailed system health analysis</p>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <pre class="bg-gray-900 text-green-400 p-6 rounded-lg overflow-x-auto text-sm font-mono leading-relaxed">{{ $output }}</pre>
        </div>
    </div>
</div>
@endsection
