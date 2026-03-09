@extends('layouts.dashboard-modern')

@section('title', 'Edit Role Permissions')
@section('page_heading', 'Edit Role Permissions')

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb + Header --}}
    <section>
        <nav class="flex items-center gap-2 text-xs mb-3" style="color:var(--hr-text-muted)">
            <a href="{{ route('settings.roles-permissions.index') }}" class="hover:underline" style="color:var(--hr-accent)">Roles & Permissions</a>
            <span>/</span>
            <span>Edit {{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
        </nav>
        <h2 class="text-xl font-extrabold" style="color:var(--hr-text-main)">Edit Role Permissions</h2>
        <p class="text-sm mt-1" style="color:var(--hr-text-muted)">
            Assign or remove permissions for the <strong>{{ ucwords(str_replace('_', ' ', $role->name)) }}</strong> role.
        </p>
    </section>

    <form action="{{ route('settings.roles-permissions.update-role', $role) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Role Info --}}
        <div class="hrm-modern-surface rounded-2xl overflow-hidden mb-6">
            <div class="px-6 py-4 border-b" style="border-color:var(--hr-line)">
                <h3 class="text-base font-extrabold" style="color:var(--hr-text-main)">Role Information</h3>
            </div>
            <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.08em]" style="color:var(--hr-text-muted)">Role Name</dt>
                    <dd class="mt-1 text-sm font-semibold" style="color:var(--hr-text-main)">{{ ucwords(str_replace('_', ' ', $role->name)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.08em]" style="color:var(--hr-text-muted)">Guard</dt>
                    <dd class="mt-1 text-sm font-mono" style="color:var(--hr-text-main)">{{ $role->guard_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.08em]" style="color:var(--hr-text-muted)">Currently Assigned</dt>
                    <dd class="mt-1 text-sm font-semibold" style="color:var(--hr-text-main)">{{ count($rolePermissions) }} permissions</dd>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="hrm-modern-surface rounded-2xl overflow-hidden mb-6">
            <div class="px-6 py-4 border-b flex items-center justify-between" style="border-color:var(--hr-line)">
                <div>
                    <h3 class="text-base font-extrabold" style="color:var(--hr-text-main)">Assign Permissions</h3>
                    <p class="text-sm mt-0.5" style="color:var(--hr-text-muted)">Select permissions to assign to this role</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <button type="button" onclick="selectAllPermissions()" style="color:var(--hr-accent)">Select All</button>
                    <span style="color:var(--hr-line)">|</span>
                    <button type="button" onclick="deselectAllPermissions()" class="text-red-500">Deselect All</button>
                </div>
            </div>

            <div class="p-6 space-y-4">
                @foreach($permissionsByModule as $module => $modulePermissions)
                    <div class="rounded-xl border overflow-hidden" style="border-color:var(--hr-line)">
                        {{-- Module header --}}
                        <div class="px-4 py-3 border-b flex items-center" style="border-color:var(--hr-line);background:var(--hr-surface-strong)">
                            <input
                                type="checkbox"
                                id="module_{{ $module }}"
                                class="h-4 w-4 rounded module-checkbox"
                                style="accent-color:var(--hr-accent)"
                                data-module="{{ $module }}"
                                onclick="toggleModule('{{ $module }}')"
                            >
                            <label for="module_{{ $module }}" class="ml-3 text-sm font-extrabold cursor-pointer" style="color:var(--hr-text-main)">
                                {{ ucfirst($module) }}
                                <span class="ml-1 text-xs font-normal" style="color:var(--hr-text-muted)">({{ $modulePermissions->count() }})</span>
                            </label>
                        </div>

                        {{-- Permissions grid --}}
                        <div class="px-4 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($modulePermissions as $permission)
                                    <div class="flex items-start gap-2">
                                        <input
                                            id="permission_{{ $permission->id }}"
                                            name="permissions[]"
                                            type="checkbox"
                                            value="{{ $permission->id }}"
                                            class="permission-checkbox module-{{ $module }}-permission h-4 w-4 mt-0.5 rounded shrink-0"
                                            style="accent-color:var(--hr-accent)"
                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                        >
                                        <label for="permission_{{ $permission->id }}" class="text-xs font-medium cursor-pointer leading-relaxed" style="color:var(--hr-text-main)">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('settings.roles-permissions.index') }}" class="ui-btn ui-btn-ghost">Cancel</a>
            <button type="submit" class="ui-btn ui-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Permissions
            </button>
        </div>
    </form>

</div>

<script>
function toggleModule(module) {
    const moduleCheckbox = document.getElementById('module_' + module);
    document.querySelectorAll('.module-' + module + '-permission').forEach(cb => {
        cb.checked = moduleCheckbox.checked;
    });
}

function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox, .module-checkbox').forEach(cb => { cb.checked = true; });
}

function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox, .module-checkbox').forEach(cb => { cb.checked = false; });
}

function updateModuleCheckboxes() {
    document.querySelectorAll('.module-checkbox').forEach(moduleCheckbox => {
        const module = moduleCheckbox.dataset.module;
        const all = document.querySelectorAll('.module-' + module + '-permission');
        const checked = Array.from(all).filter(cb => cb.checked).length;
        if (checked === 0) {
            moduleCheckbox.checked = false;
            moduleCheckbox.indeterminate = false;
        } else if (checked === all.length) {
            moduleCheckbox.checked = true;
            moduleCheckbox.indeterminate = false;
        } else {
            moduleCheckbox.checked = false;
            moduleCheckbox.indeterminate = true;
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.addEventListener('change', updateModuleCheckboxes);
    });
    updateModuleCheckboxes();
});
</script>
@endsection
