<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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

class BranchController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $this->extractFilters($request);
        $perPage = max(5, min(50, (int) $request->integer('per_page', 12)));
        $branches = $this->paginateBranches(
            $filters['q'],
            $filters['status'],
            max(1, (int) $request->integer('page', 1)),
            $perPage
        );
        $branchesPayload = $this->serializePaginator($branches, $filters);

        if ($request->expectsJson()) {
            return response()->json($branchesPayload);
        }

        $editingBranch = null;
        $editingBranchId = (int) $request->integer('edit', 0);
        if ($editingBranchId > 0) {
            $editingBranch = Branch::query()->find($editingBranchId);
        }

        return view('modules.branches.index', [
            'pagePayload' => [
                'csrfToken' => csrf_token(),
                'routes' => [
                    'list' => route('modules.branches.index'),
                    'create' => route('modules.branches.store'),
                    'updateTemplate' => $this->branchRouteTemplate('modules.branches.update'),
                    'deleteTemplate' => $this->branchRouteTemplate('modules.branches.destroy'),
                ],
                'filters' => $filters,
                'branches' => $branchesPayload,
                'editingBranch' => $editingBranch ? $this->transformBranch($editingBranch) : null,
                'flash' => [
                    'status' => (string) session('status', ''),
                    'error' => (string) session('error', ''),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->rules());

        $branch = Branch::query()->create($this->normalizedAttributes($validated, $request, true));

        ActivityLogger::log(
            $request->user(),
            'branch.created',
            'Branch created',
            $branch->name,
            '#db2777',
            $branch
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Branch created successfully.',
                'data' => $this->transformBranch($branch),
            ], 201);
        }

        return redirect()
            ->route('modules.branches.index')
            ->with('status', 'Branch created successfully.');
    }

    public function edit(Request $request, Branch $branch): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->transformBranch($branch),
            ]);
        }

        return redirect()->route('modules.branches.index', ['edit' => $branch->id]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->rules($branch));
        $oldBranchName = (string) $branch->name;
        $attributes = $this->normalizedAttributes($validated, $request, false);

        DB::transaction(function () use ($branch, $attributes, $oldBranchName): void {
            $branch->update($attributes);
            $this->syncEmployeeBranchAssignments($oldBranchName, (string) $branch->name);
        });

        ActivityLogger::log(
            $request->user(),
            'branch.updated',
            'Branch updated',
            $branch->name,
            '#db2777',
            $branch
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Branch updated successfully.',
                'data' => $this->transformBranch($branch->fresh()),
            ]);
        }

        return redirect()
            ->route('modules.branches.index')
            ->with('status', 'Branch updated successfully.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse|JsonResponse
    {
        $assignedEmployeesCount = $this->assignedEmployeesCount((string) $branch->name);
        if ($assignedEmployeesCount > 0) {
            $message = "Cannot delete branch. It is assigned to {$assignedEmployeesCount} employee(s).";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('modules.branches.index')
                ->with('error', $message);
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Branch deleted successfully.',
                'id' => $branchId,
            ]);
        }

        return redirect()
            ->route('modules.branches.index')
            ->with('status', 'Branch deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        $status = Str::lower(trim((string) $request->query('status', 'all')));

        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $status,
        ];
    }

    private function paginateBranches(string $search, string $status, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->branchListQuery($search, $status)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    private function branchListQuery(string $search, string $status): Builder
    {
        $searchTerm = trim($search);

        return Branch::query()
            ->when($status === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm) . '%';

                $query->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhere('address_line_1', 'like', $like)
                        ->orWhere('address_line_2', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('state', 'like', $like)
                        ->orWhere('country', 'like', $like)
                        ->orWhere('postal_code', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data:list<array<string,mixed>>,meta:array<string,mixed>,filters:array<string,mixed>}
     */
    private function serializePaginator(LengthAwarePaginator $branches, array $filters): array
    {
        return [
            'data' => collect($branches->items())
                ->map(fn (Branch $branch): array => $this->transformBranch($branch))
                ->values()
                ->all(),
            'meta' => [
                'currentPage' => $branches->currentPage(),
                'lastPage' => $branches->lastPage(),
                'perPage' => $branches->perPage(),
                'total' => $branches->total(),
                'from' => $branches->firstItem(),
                'to' => $branches->lastItem(),
            ],
            'filters' => $filters,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformBranch(Branch $branch): array
    {
        $addressSummary = $this->addressSummary($branch);

        return [
            'id' => (int) $branch->id,
            'name' => (string) $branch->name,
            'code' => (string) $branch->code,
            'address_line_1' => (string) ($branch->address_line_1 ?? ''),
            'address_line_2' => (string) ($branch->address_line_2 ?? ''),
            'city' => (string) ($branch->city ?? ''),
            'state' => (string) ($branch->state ?? ''),
            'country' => (string) ($branch->country ?? ''),
            'postal_code' => (string) ($branch->postal_code ?? ''),
            'location' => (string) ($branch->location ?? ''),
            'description' => (string) ($branch->description ?? ''),
            'is_active' => (bool) $branch->is_active,
            'statusLabel' => $branch->is_active ? 'Active' : 'Inactive',
            'locationLabel' => $this->locationLabel($branch),
            'addressSummary' => $addressSummary === '' ? 'N/A' : $addressSummary,
        ];
    }

    private function locationLabel(Branch $branch): string
    {
        $location = trim((string) ($branch->location ?? ''));
        if ($location !== '') {
            return $location;
        }

        $parts = array_values(array_filter([
            trim((string) ($branch->city ?? '')),
            trim((string) ($branch->state ?? '')),
            trim((string) ($branch->country ?? '')),
        ], static fn (string $value): bool => $value !== ''));

        return empty($parts) ? 'N/A' : implode(', ', $parts);
    }

    private function addressSummary(Branch $branch): string
    {
        $parts = array_values(array_filter([
            trim((string) ($branch->address_line_1 ?? '')),
            trim((string) ($branch->address_line_2 ?? '')),
            trim((string) ($branch->city ?? '')),
            trim((string) ($branch->state ?? '')),
            trim((string) ($branch->country ?? '')),
            trim((string) ($branch->postal_code ?? '')),
        ], static fn (string $value): bool => $value !== ''));

        return implode(', ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Branch $branch = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('branches', 'name')->ignore($branch?->id)],
            'code' => ['required', 'string', 'max:30', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function normalizedAttributes(array $validated, Request $request, bool $defaultActive): array
    {
        $normalize = static fn (?string $value): ?string => ($trimmed = trim((string) $value)) === '' ? null : $trimmed;

        return [
            'name' => (string) Str::of((string) $validated['name'])->trim(),
            'code' => Str::upper((string) Str::of((string) $validated['code'])->trim()),
            'address_line_1' => $normalize($validated['address_line_1'] ?? null),
            'address_line_2' => $normalize($validated['address_line_2'] ?? null),
            'city' => $normalize($validated['city'] ?? null),
            'state' => $normalize($validated['state'] ?? null),
            'country' => $normalize($validated['country'] ?? null),
            'postal_code' => $normalize($validated['postal_code'] ?? null),
            'location' => $normalize($validated['location'] ?? null),
            'description' => $normalize($validated['description'] ?? null),
            'is_active' => $request->boolean('is_active', $defaultActive),
        ];
    }

    private function branchRouteTemplate(string $routeName): string
    {
        $placeholder = 987654321;

        return str_replace((string) $placeholder, '__BRANCH__', route($routeName, ['branch' => $placeholder]));
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
                $query->workforce();
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
                $query->workforce();
            })
            ->whereRaw('LOWER(TRIM(branch)) = ?', [$branchNameKey])
            ->count();
    }
}
