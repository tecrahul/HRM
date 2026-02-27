<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\User;

class DesignationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $this->extractFilters($request);
        $perPage = max(5, min(50, (int) $request->integer('per_page', 12)));
        $designations = $this->paginateDesignations(
            $filters['q'],
            $filters['status'],
            max(1, (int) $request->integer('page', 1)),
            $perPage
        );
        $payload = $this->serializePaginator($designations, $filters);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        $editingDesignation = null;
        $editingId = (int) $request->integer('edit', 0);
        if ($editingId > 0) {
            $editingDesignation = Designation::query()->find($editingId);
        }

        return view('modules.designations.index', [
            'pagePayload' => [
                'csrfToken' => csrf_token(),
                'routes' => [
                    'list' => route('modules.designations.index'),
                    'create' => route('modules.designations.store'),
                    'updateTemplate' => $this->routeTemplate('modules.designations.update'),
                    'deleteTemplate' => $this->routeTemplate('modules.designations.destroy'),
                ],
                'filters' => $filters,
                'designations' => $payload,
                'editingDesignation' => $editingDesignation ? $this->transformDesignation($editingDesignation) : null,
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
        $designation = Designation::query()->create($this->normalizedAttributes($validated, $request, true));

        ActivityLogger::log(
            $request->user(),
            'designation.created',
            'Designation created',
            $designation->name,
            '#0ea5e9',
            $designation
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Designation created successfully.',
                'data' => $this->transformDesignation($designation),
            ], 201);
        }

        return redirect()->route('modules.designations.index')
            ->with('status', 'Designation created successfully.');
    }

    public function edit(Request $request, Designation $designation): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->transformDesignation($designation),
            ]);
        }

        return redirect()->route('modules.designations.index', ['edit' => $designation->id]);
    }

    public function update(Request $request, Designation $designation): RedirectResponse|JsonResponse
    {
        $validated = $request->validate($this->rules($request, $designation));
        $designation->update($this->normalizedAttributes($validated, $request, false));

        ActivityLogger::log(
            $request->user(),
            'designation.updated',
            'Designation updated',
            $designation->name,
            '#0ea5e9',
            $designation
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Designation updated successfully.',
                'data' => $this->transformDesignation($designation),
            ]);
        }

        return redirect()->route('modules.designations.index')
            ->with('status', 'Designation updated successfully.');
    }

    public function destroy(Request $request, Designation $designation): RedirectResponse|JsonResponse
    {
        $assignedUsers = User::query()->where('designation_id', $designation->id)->count();
        if ($assignedUsers > 0) {
            $message = "Cannot delete designation. It is assigned to {$assignedUsers} user(s).";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->route('modules.designations.index')->with('error', $message);
        }

        $name = (string) $designation->name;
        $id = (int) $designation->id;
        $designation->delete();

        ActivityLogger::log(
            $request->user(),
            'designation.deleted',
            'Designation deleted',
            $name,
            '#ef4444',
            $id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Designation deleted successfully.',
                'id' => $id,
            ]);
        }

        return redirect()->route('modules.designations.index')
            ->with('status', 'Designation deleted successfully.');
    }

    /**
     * @return array{q:string,status:string}
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

    private function paginateDesignations(string $search, string $status, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->designationListQuery($search, $status)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    private function designationListQuery(string $search, string $status): Builder
    {
        $searchTerm = trim($search);

        return Designation::query()
            ->when($status === 'active', fn (Builder $q): Builder => $q->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $q): Builder => $q->where('is_active', false))
            ->when($searchTerm !== '', function (Builder $q) use ($searchTerm): void {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm) . '%';
                $q->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('level')
            ->orderBy('name');
    }

    /**
     * @param array{q:string,status:string} $filters
     * @return array{data:list<array<string,mixed>>,meta:array<string,mixed>,filters:array<string,mixed>}
     */
    private function serializePaginator(LengthAwarePaginator $designations, array $filters): array
    {
        return [
            'data' => collect($designations->items())
                ->map(fn (Designation $d): array => $this->transformDesignation($d))
                ->values()
                ->all(),
            'meta' => [
                'currentPage' => $designations->currentPage(),
                'lastPage' => $designations->lastPage(),
                'perPage' => $designations->perPage(),
                'total' => $designations->total(),
                'from' => $designations->firstItem(),
                'to' => $designations->lastItem(),
            ],
            'filters' => $filters,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function transformDesignation(Designation $designation): array
    {
        $description = trim((string) ($designation->description ?? ''));

        return [
            'id' => (int) $designation->id,
            'name' => (string) $designation->name,
            'code' => (string) ($designation->code ?? ''),
            'level' => $designation->level !== null ? (int) $designation->level : null,
            'description' => $description,
            'descriptionShort' => $description === '' ? 'N/A' : Str::limit($description, 84),
            'is_active' => (bool) $designation->is_active,
            'statusLabel' => $designation->is_active ? 'Active' : 'Inactive',
            'createdDateLabel' => $designation->created_at?->format('M d, Y') ?? 'N/A',
            'createdDateIso' => $designation->created_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?Designation $designation = null): array
    {
        $nameRule = Rule::unique('designations', 'name')->whereNull('deleted_at');
        if ($designation !== null) {
            $nameRule = $nameRule->ignore($designation->id);
        }

        return [
            'name' => ['required', 'string', 'max:120', $nameRule],
            'code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['nullable', 'integer', 'min:0'],
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
            'code' => (string) Str::of((string) ($validated['code'] ?? ''))->trim(),
            'description' => $normalize($validated['description'] ?? null),
            'level' => isset($validated['level']) && $validated['level'] !== ''
                ? max(0, (int) $validated['level'])
                : null,
            'is_active' => $request->boolean('is_active', $defaultActive),
        ];
    }

    private function routeTemplate(string $routeName): string
    {
        $placeholder = 11223344;

        return str_replace((string) $placeholder, '__DESIGNATION__', route($routeName, ['designation' => $placeholder]));
    }
}

