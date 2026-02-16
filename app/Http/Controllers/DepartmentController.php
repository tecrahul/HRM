<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('departments', 'name')],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('departments', 'code')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

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
}
