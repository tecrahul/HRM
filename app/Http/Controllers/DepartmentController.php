<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\UserProfile;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $this->extractFilters($request);
        $perPage = max(5, min(50, (int) $request->integer('per_page', 12)));
        $departments = $this->paginateDepartments(
            $filters['q'],
            $filters['status'],
            $this->parseBranchFilterId((string) $filters['branch_id']),
            max(1, (int) $request->integer('page', 1)),
            $perPage
        );
        $departmentsPayload = $this->serializePaginator($departments, $filters);

        if ($request->expectsJson()) {
            return response()->json($departmentsPayload);
        }

        $editingDepartment = null;
        $editingDepartmentId = (int) $request->integer('edit', 0);
        if ($editingDepartmentId > 0) {
            $editingDepartment = Department::query()->with('branch:id,name')->find($editingDepartmentId);
        }

        return view('modules.departments.index', [
            'pagePayload' => [
                'csrfToken' => csrf_token(),
                'routes' => [
                    'list' => route('modules.departments.index'),
                    'create' => route('modules.departments.store'),
                    'updateTemplate' => $this->departmentRouteTemplate('modules.departments.update'),
                    'deleteTemplate' => $this->departmentRouteTemplate('modules.departments.destroy'),
                ],
                'filters' => $filters,
                'departments' => $departmentsPayload,
                'branches' => $this->branchOptions(),
                'editingDepartment' => $editingDepartment ? $this->transformDepartment($editingDepartment) : null,
                'flash' => [
                    'status' => (string) session('status', ''),
                    'error' => (string) session('error', ''),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $department = Department::query()->create($this->normalizedAttributes($validated, $request, true));

        ActivityLogger::log(
            $request->user(),
            'department.created',
            'Department created',
            $department->name,
            '#db2777',
            $department
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Department created successfully.',
                'data' => $this->transformDepartment($department->fresh('branch:id,name')),
            ], 201);
        }

        return redirect()
            ->route('modules.departments.index')
            ->with('status', 'Department created successfully.');
    }

    public function edit(Request $request, Department $department): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->transformDepartment($department->loadMissing('branch:id,name')),
            ]);
        }

        return redirect()->route('modules.departments.index', ['edit' => $department->id]);
    }

    public function update(Request $request, Department $department): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->rules($request, $department));
        $oldDepartmentName = (string) $department->name;
        $attributes = $this->normalizedAttributes($validated, $request, false);

        DB::transaction(function () use ($department, $attributes, $oldDepartmentName): void {
            $department->update($attributes);
            $this->syncEmployeeDepartmentAssignments($oldDepartmentName, (string) $department->name);
        });

        ActivityLogger::log(
            $request->user(),
            'department.updated',
            'Department updated',
            $department->name,
            '#db2777',
            $department
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Department updated successfully.',
                'data' => $this->transformDepartment($department->fresh('branch:id,name')),
            ]);
        }

        return redirect()
            ->route('modules.departments.index')
            ->with('status', 'Department updated successfully.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse|JsonResponse
    {
        $assignedEmployeesCount = $this->assignedEmployeesCount((string) $department->name);
        if ($assignedEmployeesCount > 0) {
            $message = "Cannot delete department. It is assigned to {$assignedEmployeesCount} employee(s).";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('modules.departments.index')
                ->with('error', $message);
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Department deleted successfully.',
                'id' => $departmentId,
            ]);
        }

        return redirect()
            ->route('modules.departments.index')
            ->with('status', 'Department deleted successfully.');
    }

    /**
     * @return array{q:string,status:string,branch_id:string}
     */
    private function extractFilters(Request $request): array
    {
        $status = Str::lower(trim((string) $request->query('status', 'all')));
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $branchId = (int) $request->integer('branch_id', 0);

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $status,
            'branch_id' => $branchId > 0 ? (string) $branchId : '',
        ];
    }

    private function parseBranchFilterId(string $branchId): ?int
    {
        $parsed = (int) $branchId;

        return $parsed > 0 ? $parsed : null;
    }

    private function paginateDepartments(string $search, string $status, ?int $branchId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->departmentListQuery($search, $status, $branchId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    private function departmentListQuery(string $search, string $status, ?int $branchId): Builder
    {
        $searchTerm = trim($search);

        return Department::query()
            ->with('branch:id,name')
            ->when($status === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->when($branchId !== null, fn (Builder $query): Builder => $query->where('branch_id', $branchId))
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm) . '%';

                $query->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('branch', function (Builder $branchQuery) use ($like): void {
                            $branchQuery->where('name', 'like', $like);
                        });
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name');
    }

    /**
     * @param array{q:string,status:string,branch_id:string} $filters
     * @return array{data:list<array<string,mixed>>,meta:array<string,mixed>,filters:array<string,mixed>}
     */
    private function serializePaginator(LengthAwarePaginator $departments, array $filters): array
    {
        return [
            'data' => collect($departments->items())
                ->map(fn (Department $department): array => $this->transformDepartment($department))
                ->values()
                ->all(),
            'meta' => [
                'currentPage' => $departments->currentPage(),
                'lastPage' => $departments->lastPage(),
                'perPage' => $departments->perPage(),
                'total' => $departments->total(),
                'from' => $departments->firstItem(),
                'to' => $departments->lastItem(),
            ],
            'filters' => $filters,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function transformDepartment(Department $department): array
    {
        $description = trim((string) ($department->description ?? ''));

        return [
            'id' => (int) $department->id,
            'name' => (string) $department->name,
            'code' => (string) ($department->code ?? ''),
            'branch_id' => $department->branch_id !== null ? (int) $department->branch_id : null,
            'branchName' => (string) ($department->branch?->name ?? 'N/A'),
            'description' => $description,
            'descriptionShort' => $description === '' ? 'N/A' : Str::limit($description, 84),
            'is_active' => (bool) $department->is_active,
            'statusLabel' => $department->is_active ? 'Active' : 'Inactive',
            'createdDateLabel' => $department->created_at?->format('M d, Y') ?? 'N/A',
            'createdDateIso' => $department->created_at?->toDateString(),
        ];
    }

    /**
     * @return list<array{id:int,name:string,code:string}>
     */
    private function branchOptions(): array
    {
        return Branch::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(static fn (Branch $branch): array => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'code' => (string) ($branch->code ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?Department $department = null): array
    {
        $branchId = (int) $request->input('branch_id');
        $codeUniquenessRule = Rule::unique('departments', 'code')
            ->where(function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            });

        if ($department !== null) {
            $codeUniquenessRule = $codeUniquenessRule->ignore($department->id);
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', $codeUniquenessRule],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param array<string,mixed> $validated
     * @return array<string,mixed>
     */
    private function normalizedAttributes(array $validated, Request $request, bool $defaultActive): array
    {
        $normalize = static fn (?string $value): ?string => ($trimmed = trim((string) $value)) === '' ? null : $trimmed;

        return [
            'name' => (string) Str::of((string) $validated['name'])->trim(),
            'code' => Str::upper((string) Str::of((string) $validated['code'])->trim()),
            'branch_id' => (int) $validated['branch_id'],
            'description' => $normalize($validated['description'] ?? null),
            'is_active' => $request->boolean('is_active', $defaultActive),
        ];
    }

    private function departmentRouteTemplate(string $routeName): string
    {
        $placeholder = 987654321;

        return str_replace((string) $placeholder, '__DEPARTMENT__', route($routeName, ['department' => $placeholder]));
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
                $query->workforce();
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
                $query->workforce();
            })
            ->whereRaw('LOWER(TRIM(department)) = ?', [$departmentNameKey])
            ->count();
    }
}
