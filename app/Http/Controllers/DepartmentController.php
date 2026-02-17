<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\UserProfile;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('modules.departments.index', [
            'departments' => Department::query()->orderBy('name')->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request);

        $department = Department::query()->create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?: null,
            'description' => $validated['description'] ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        ActivityLogger::log(
            $request->user(),
            'department.created',
            'Department created',
            $department->name,
            '#db2777',
            $department
        );

        return redirect()
            ->route('modules.departments.index')
            ->with('status', 'Department created successfully.');
    }

    public function edit(Department $department): View
    {
        return view('modules.departments.edit', [
            'department' => $department,
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $this->validatedPayload($request, $department);
        $oldDepartmentName = (string) $department->name;

        DB::transaction(function () use ($department, $validated, $oldDepartmentName): void {
            $department->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?: null,
                'description' => $validated['description'] ?: null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->syncEmployeeDepartmentAssignments($oldDepartmentName, $department->name);
        });

        ActivityLogger::log(
            $request->user(),
            'department.updated',
            'Department updated',
            $department->name,
            '#db2777',
            $department
        );

        return redirect()
            ->route('modules.departments.index')
            ->with('status', 'Department updated successfully.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $assignedEmployeesCount = $this->assignedEmployeesCount((string) $department->name);
        if ($assignedEmployeesCount > 0) {
            return redirect()
                ->route('modules.departments.index')
                ->with('error', "Cannot delete department. It is assigned to {$assignedEmployeesCount} employee(s).");
        }

        $departmentName = $department->name;
        $departmentId = $department->id;
        $department->delete();

        ActivityLogger::log(
            $request->user(),
            'department.deleted',
            'Department deleted',
            $departmentName,
            '#ef4444',
            $departmentId
        );

        return redirect()
            ->route('modules.departments.index')
            ->with('status', 'Department deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?Department $department = null): array
    {
        $nameRules = ['required', 'string', 'max:120', Rule::unique('departments', 'name')];
        $codeRules = ['nullable', 'string', 'max:30', Rule::unique('departments', 'code')];

        if ($department !== null) {
            $nameRules[3] = Rule::unique('departments', 'name')->ignore($department->id);
            $codeRules[3] = Rule::unique('departments', 'code')->ignore($department->id);
        }

        return $request->validate([
            'name' => $nameRules,
            'code' => $codeRules,
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function syncEmployeeDepartmentAssignments(string $oldName, string $newName): void
    {
        $oldNameKey = Str::lower(trim($oldName));
        $newNameKey = Str::lower(trim($newName));

        if ($oldNameKey === '' || $oldNameKey === $newNameKey) {
            return;
        }

        UserProfile::query()
            ->whereHas('user', function ($query): void {
                $query->where('role', UserRole::EMPLOYEE->value);
            })
            ->whereRaw('LOWER(TRIM(department)) = ?', [$oldNameKey])
            ->update([
                'department' => $newName,
            ]);
    }

    private function assignedEmployeesCount(string $departmentName): int
    {
        $departmentNameKey = Str::lower(trim($departmentName));

        if ($departmentNameKey === '') {
            return 0;
        }

        return UserProfile::query()
            ->whereHas('user', function ($query): void {
                $query->where('role', UserRole::EMPLOYEE->value);
            })
            ->whereRaw('LOWER(TRIM(department)) = ?', [$departmentNameKey])
            ->count();
    }
}
