@extends('layouts.dashboard-modern')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
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
                            <span class="text-sm font-medium text-gray-900">Edit {{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">Edit Role Permissions</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Assign or remove permissions for the <strong>{{ ucwords(str_replace('_', ' ', $role->name)) }}</strong> role.
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('settings.roles-permissions.update-role', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Role Information -->
        <div class="bg-white shadow overflow-hidden rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Role Information</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Role Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucwords(str_replace('_', ' ', $role->name)) }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Guard</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $role->guard_name }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Currently Assigned</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ count($rolePermissions) }} permissions</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Permissions Selection -->
        <div class="bg-white shadow overflow-hidden rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Assign Permissions</h3>
                    <p class="mt-1 text-sm text-gray-500">Select permissions to assign to this role</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="selectAllPermissions()" class="text-sm text-indigo-600 hover:text-indigo-900">Select All</button>
                    <span class="text-gray-300">|</span>
                    <button type="button" onclick="deselectAllPermissions()" class="text-sm text-red-600 hover:text-red-900">Deselect All</button>
                </div>
            </div>
            <div class="px-4 py-5 sm:p-6">
                @foreach($permissionsByModule as $module => $modulePermissions)
                    <div class="mb-6 border border-gray-200 rounded-lg">
                        <!-- Module Header -->
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    id="module_{{ $module }}"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded module-checkbox"
                                    data-module="{{ $module }}"
                                    onclick="toggleModule('{{ $module }}')"
                                >
                                <label for="module_{{ $module }}" class="ml-3 text-sm font-semibold text-gray-900 cursor-pointer">
                                    {{ $module }}
                                    <span class="ml-2 text-xs text-gray-500">({{ $modulePermissions->count() }} permissions)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Permissions Grid -->
                        <div class="px-4 py-4 bg-white">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($modulePermissions as $permission)
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input
                                                id="permission_{{ $permission->id }}"
                                                name="permissions[]"
                                                type="checkbox"
                                                value="{{ $permission->id }}"
                                                class="permission-checkbox module-{{ $module }}-permission h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="permission_{{ $permission->id }}" class="font-medium text-gray-700 cursor-pointer">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between">
            <a href="{{ route('settings.roles-permissions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Permissions
            </button>
        </div>
    </form>
</div>

<script>
// Toggle all permissions in a module
function toggleModule(module) {
    const moduleCheckbox = document.getElementById('module_' + module);
    const permissionCheckboxes = document.querySelectorAll('.module-' + module + '-permission');

    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = moduleCheckbox.checked;
    });
}

// Select all permissions
function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.querySelectorAll('.module-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
}

// Deselect all permissions
function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.querySelectorAll('.module-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Update module checkbox state when individual permissions are toggled
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateModuleCheckboxes();
        });
    });

    // Initial state
    updateModuleCheckboxes();
});

function updateModuleCheckboxes() {
    document.querySelectorAll('.module-checkbox').forEach(moduleCheckbox => {
        const module = moduleCheckbox.dataset.module;
        const permissionCheckboxes = document.querySelectorAll('.module-' + module + '-permission');
        const checkedCount = Array.from(permissionCheckboxes).filter(cb => cb.checked).length;

        if (checkedCount === 0) {
            moduleCheckbox.checked = false;
            moduleCheckbox.indeterminate = false;
        } else if (checkedCount === permissionCheckboxes.length) {
            moduleCheckbox.checked = true;
            moduleCheckbox.indeterminate = false;
        } else {
            moduleCheckbox.checked = false;
            moduleCheckbox.indeterminate = true;
        }
    });
}
</script>
@endsection
