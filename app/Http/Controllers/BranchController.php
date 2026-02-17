<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\UserProfile;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(): View
    {
        return view('modules.branches.index', [
            'branches' => Branch::query()->orderBy('name')->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('branches', 'name')],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('branches', 'code')],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branch = Branch::query()->create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?: null,
            'location' => $validated['location'] ?: null,
            'description' => $validated['description'] ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        ActivityLogger::log(
            $request->user(),
            'branch.created',
            'Branch created',
            $branch->name,
            '#db2777',
            $branch
        );

        return redirect()
            ->route('modules.branches.index')
            ->with('status', 'Branch created successfully.');
    }

    public function edit(Branch $branch): View
    {
        return view('modules.branches.edit', [
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('branches', 'name')->ignore($branch->id)],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('branches', 'code')->ignore($branch->id)],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldBranchName = (string) $branch->name;

        DB::transaction(function () use ($branch, $validated, $oldBranchName): void {
            $branch->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?: null,
                'location' => $validated['location'] ?: null,
                'description' => $validated['description'] ?: null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->syncEmployeeBranchAssignments($oldBranchName, $branch->name);
        });

        ActivityLogger::log(
            $request->user(),
            'branch.updated',
            'Branch updated',
            $branch->name,
            '#db2777',
            $branch
        );

        return redirect()
            ->route('modules.branches.index')
            ->with('status', 'Branch updated successfully.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $assignedEmployeesCount = $this->assignedEmployeesCount((string) $branch->name);
        if ($assignedEmployeesCount > 0) {
            return redirect()
                ->route('modules.branches.index')
                ->with('error', "Cannot delete branch. It is assigned to {$assignedEmployeesCount} employee(s).");
        }

        $branchName = $branch->name;
        $branchId = $branch->id;
        $branch->delete();

        ActivityLogger::log(
            $request->user(),
            'branch.deleted',
            'Branch deleted',
            $branchName,
            '#ef4444',
            $branchId
        );

        return redirect()
            ->route('modules.branches.index')
            ->with('status', 'Branch deleted successfully.');
    }

    private function syncEmployeeBranchAssignments(string $oldName, string $newName): void
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
            ->whereRaw('LOWER(TRIM(branch)) = ?', [$oldNameKey])
            ->update([
                'branch' => $newName,
            ]);
    }

    private function assignedEmployeesCount(string $branchName): int
    {
        $branchNameKey = Str::lower(trim($branchName));

        if ($branchNameKey === '') {
            return 0;
        }

        return UserProfile::query()
            ->whereHas('user', function ($query): void {
                $query->where('role', UserRole::EMPLOYEE->value);
            })
            ->whereRaw('LOWER(TRIM(branch)) = ?', [$branchNameKey])
            ->count();
    }
}
