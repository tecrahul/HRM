<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Holiday;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\NotificationCenter;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $viewer = $this->ensurePermission($request, 'holiday.view');

        $filters = $this->extractFilters($request);
        $perPage = max(5, min(50, (int) $request->integer('per_page', 12)));
        $holidays = $this->paginateHolidays($filters, max(1, (int) $request->integer('page', 1)), $perPage);
        $holidaysPayload = $this->serializePaginator($holidays, $filters);
        $stats = $this->buildStats($filters);

        if ($request->expectsJson()) {
            return response()->json(array_merge($holidaysPayload, [
                'stats' => $stats,
            ]));
        }

        $editingHoliday = null;
        $editingHolidayId = (int) $request->integer('edit', 0);
        if ($editingHolidayId > 0) {
            $editingHoliday = Holiday::query()->with('branch:id,name')->find($editingHolidayId);
        }

        return view('modules.holidays.index', [
            'pagePayload' => [
                'csrfToken' => csrf_token(),
                'routes' => [
                    'list' => route('modules.holidays.index'),
                    'create' => route('modules.holidays.store'),
                    'updateTemplate' => $this->holidayRouteTemplate('modules.holidays.update'),
                    'deleteTemplate' => $this->holidayRouteTemplate('modules.holidays.destroy'),
                ],
                'filters' => $filters,
                'defaults' => $this->defaultFilters(),
                'holidays' => $holidaysPayload,
                'stats' => $stats,
                'branches' => $this->branchOptions(),
                'yearOptions' => $this->yearOptions(),
                'holidayTypeOptions' => $this->holidayTypeOptions(),
                'editingHoliday' => $editingHoliday ? $this->transformHoliday($editingHoliday) : null,
                'capabilities' => [
                    'canCreate' => $viewer->hasPermission('holiday.create'),
                    'canEdit' => $viewer->hasPermission('holiday.edit'),
                    'canDelete' => $viewer->hasPermission('holiday.delete'),
                ],
                'flash' => [
                    'status' => (string) session('status', ''),
                    'error' => (string) session('error', ''),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $viewer = $this->ensurePermission($request, 'holiday.create');
        $validated = $request->validate($this->rules());

        $branchId = $this->normalizeBranchId($validated['branch_id'] ?? null);
        $startDate = (string) $validated['holiday_date'];

        if ($this->duplicateExists($startDate, $branchId)) {
            return $this->duplicateErrorResponse($request, 'A holiday already exists for this date and branch scope.');
        }

        $holiday = Holiday::query()->create($this->normalizedAttributes($validated, $request, $viewer, true));

        ActivityLogger::log(
            $viewer,
            'holiday.created',
            'Holiday created',
            $this->activityMetaLabel($holiday),
            '#0ea5e9',
            $holiday
        );

        NotificationCenter::notifyRoles(
            [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value, UserRole::HR->value, UserRole::EMPLOYEE->value],
            "holiday.created.{$holiday->id}",
            'Holiday calendar updated',
            "{$holiday->name} was added to the holiday calendar.",
            route('modules.holidays.index'),
            'info',
            0
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Holiday created successfully.',
                'data' => $this->transformHoliday($holiday->fresh('branch:id,name')),
            ], 201);
        }

        return redirect()
            ->route('modules.holidays.index')
            ->with('status', 'Holiday created successfully.');
    }

    public function edit(Request $request, Holiday $holiday): RedirectResponse|JsonResponse
    {
        $this->ensurePermission($request, 'holiday.edit');

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->transformHoliday($holiday->loadMissing('branch:id,name')),
            ]);
        }

        return redirect()->route('modules.holidays.index', ['edit' => $holiday->id]);
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse|JsonResponse
    {
        $viewer = $this->ensurePermission($request, 'holiday.edit');
        $validated = $request->validate($this->rules());

        $branchId = $this->normalizeBranchId($validated['branch_id'] ?? null);
        $startDate = (string) $validated['holiday_date'];

        if ($this->duplicateExists($startDate, $branchId, (int) $holiday->id)) {
            return $this->duplicateErrorResponse($request, 'A holiday already exists for this date and branch scope.');
        }

        $holiday->update($this->normalizedAttributes($validated, $request, $viewer, false));

        ActivityLogger::log(
            $viewer,
            'holiday.updated',
            'Holiday updated',
            $this->activityMetaLabel($holiday),
            '#0ea5e9',
            $holiday
        );

        NotificationCenter::notifyRoles(
            [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value, UserRole::HR->value, UserRole::EMPLOYEE->value],
            "holiday.updated.{$holiday->id}",
            'Holiday calendar updated',
            "{$holiday->name} was updated in the holiday calendar.",
            route('modules.holidays.index'),
            'info',
            0
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Holiday updated successfully.',
                'data' => $this->transformHoliday($holiday->fresh('branch:id,name')),
            ]);
        }

        return redirect()
            ->route('modules.holidays.index')
            ->with('status', 'Holiday updated successfully.');
    }

    public function destroy(Request $request, Holiday $holiday): RedirectResponse|JsonResponse
    {
        $viewer = $this->ensurePermission($request, 'holiday.delete');

        $name = (string) $holiday->name;
        $dateLabel = $holiday->holiday_date?->format('M d, Y') ?? 'N/A';
        $holidayId = (int) $holiday->id;
        $holiday->delete();

        ActivityLogger::log(
            $viewer,
            'holiday.deleted',
            'Holiday deleted',
            "{$name} • {$dateLabel}",
            '#ef4444',
            $holidayId
        );

        NotificationCenter::notifyRoles(
            [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value, UserRole::HR->value, UserRole::EMPLOYEE->value],
            'holiday.deleted',
            'Holiday calendar updated',
            "{$name} ({$dateLabel}) was removed from the holiday calendar.",
            route('modules.holidays.index'),
            'warning',
            10
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Holiday deleted successfully.',
                'id' => $holidayId,
            ]);
        }

        return redirect()
            ->route('modules.holidays.index')
            ->with('status', 'Holiday deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        $currentYear = now()->year;
        $year = (int) $request->integer('year', $currentYear);
        if ($year < 2000 || $year > 2100) {
            $year = $currentYear;
        }

        $status = Str::lower(trim((string) $request->query('status', 'all')));
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $holidayType = Str::lower(trim((string) $request->query('holiday_type', 'all')));
        if (! in_array($holidayType, ['all', 'public', 'company', 'optional'], true)) {
            $holidayType = 'all';
        }

        $sort = Str::lower(trim((string) $request->query('sort', 'date_asc')));
        if (! in_array($sort, ['date_asc', 'date_desc'], true)) {
            $sort = 'date_asc';
        }

        $branchId = (int) $request->integer('branch_id', 0);

        return [
            'q' => trim((string) $request->query('q', '')),
            'year' => $year,
            'branch_id' => $branchId > 0 ? (string) $branchId : '',
            'holiday_type' => $holidayType,
            'status' => $status,
            'sort' => $sort,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function paginateHolidays(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->holidayListQuery($filters)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function holidayListQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $year = (int) ($filters['year'] ?? now()->year);
        $branchId = $this->parseBranchFilterId((string) ($filters['branch_id'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $holidayType = (string) ($filters['holiday_type'] ?? 'all');
        $sort = (string) ($filters['sort'] ?? 'date_asc');

        return Holiday::query()
            ->with('branch:id,name')
            ->whereYear('holiday_date', $year)
            ->when($branchId !== null, fn (Builder $query): Builder => $query->where('branch_id', $branchId))
            ->when($holidayType !== 'all', fn (Builder $query): Builder => $query->where('holiday_type', $holidayType))
            ->when($status === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

                $query->where(function (Builder $nested) use ($like): void {
                    $nested
                        ->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('holiday_type', 'like', $like)
                        ->orWhereHas('branch', function (Builder $branchQuery) use ($like): void {
                            $branchQuery->where('name', 'like', $like);
                        });
                });
            })
            ->orderBy('holiday_date', $sort === 'date_desc' ? 'desc' : 'asc')
            ->orderBy('name');
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{data:list<array<string,mixed>>,meta:array<string,mixed>,filters:array<string,mixed>}
     */
    private function serializePaginator(LengthAwarePaginator $holidays, array $filters): array
    {
        return [
            'data' => collect($holidays->items())
                ->map(fn (Holiday $holiday): array => $this->transformHoliday($holiday))
                ->values()
                ->all(),
            'meta' => [
                'currentPage' => $holidays->currentPage(),
                'lastPage' => $holidays->lastPage(),
                'perPage' => $holidays->perPage(),
                'total' => $holidays->total(),
                'from' => $holidays->firstItem(),
                'to' => $holidays->lastItem(),
            ],
            'filters' => $filters,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformHoliday(Holiday $holiday): array
    {
        $startDate = $holiday->holiday_date;
        $endDate = $holiday->end_date;

        if ($startDate !== null && $endDate !== null && $endDate->lt($startDate)) {
            $endDate = $startDate->copy();
        }

        $today = now()->startOfDay();
        $effectiveEnd = $endDate ?? $startDate;
        $isPast = $effectiveEnd?->lt($today) ?? false;

        return [
            'id' => (int) $holiday->id,
            'name' => (string) $holiday->name,
            'holiday_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'dateLabel' => $this->dateRangeLabel($startDate?->toDateString(), $endDate?->toDateString()),
            'holiday_type' => (string) ($holiday->holiday_type ?: ($holiday->is_optional ? 'optional' : 'public')),
            'holidayTypeLabel' => $this->holidayTypeLabel((string) ($holiday->holiday_type ?: ($holiday->is_optional ? 'optional' : 'public'))),
            'branch_id' => $holiday->branch_id !== null ? (int) $holiday->branch_id : null,
            'branchName' => (string) ($holiday->branch?->name ?? 'All Branches'),
            'description' => (string) ($holiday->description ?? ''),
            'descriptionShort' => blank($holiday->description) ? 'N/A' : Str::limit((string) $holiday->description, 72),
            'is_active' => (bool) $holiday->is_active,
            'statusLabel' => $holiday->is_active ? 'Active' : 'Inactive',
            'temporalStatus' => $isPast ? 'past' : 'upcoming',
            'temporalStatusLabel' => $isPast ? 'Past' : 'Upcoming',
            'createdDateLabel' => $holiday->created_at?->format('M d, Y') ?? 'N/A',
            'createdDateIso' => $holiday->created_at?->toDateString(),
        ];
    }

    private function dateRangeLabel(?string $startDate, ?string $endDate): string
    {
        if ($startDate === null || $startDate === '') {
            return 'N/A';
        }

        $start = Carbon::parse($startDate);
        if ($endDate === null || $endDate === '' || $endDate === $startDate) {
            return $start->format('M d, Y');
        }

        $end = Carbon::parse($endDate);

        return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'holiday_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:holiday_date'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'holiday_type' => ['required', 'string', Rule::in(array_keys($this->holidayTypeOptions()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param array<string,mixed> $validated
     * @return array<string,mixed>
     */
    private function normalizedAttributes(array $validated, Request $request, User $viewer, bool $defaultActive): array
    {
        $normalize = static fn (?string $value): ?string => ($trimmed = trim((string) $value)) === '' ? null : $trimmed;

        $type = (string) ($validated['holiday_type'] ?? 'public');
        $attributes = [
            'name' => (string) Str::of((string) $validated['name'])->trim(),
            'holiday_date' => (string) $validated['holiday_date'],
            'end_date' => $normalize($validated['end_date'] ?? null),
            'branch_id' => $this->normalizeBranchId($validated['branch_id'] ?? null),
            'holiday_type' => $type,
            'is_optional' => $type === 'optional',
            'description' => $normalize($validated['description'] ?? null),
            'is_active' => $request->boolean('is_active', $defaultActive),
            'updated_by_user_id' => $viewer->id,
        ];

        if ($defaultActive) {
            $attributes['created_by_user_id'] = $viewer->id;
        }

        return $attributes;
    }

    private function parseBranchFilterId(string $branchId): ?int
    {
        $parsed = (int) $branchId;

        return $parsed > 0 ? $parsed : null;
    }

    private function normalizeBranchId(mixed $branchId): ?int
    {
        $parsed = (int) $branchId;

        return $parsed > 0 ? $parsed : null;
    }

    private function duplicateExists(string $holidayDate, ?int $branchId, ?int $ignoreId = null): bool
    {
        $query = Holiday::query()
            ->whereDate('holiday_date', $holidayDate)
            ->when($branchId === null, function (Builder $innerQuery): void {
                $innerQuery->whereNull('branch_id');
            }, function (Builder $innerQuery) use ($branchId): void {
                $innerQuery->where('branch_id', $branchId);
            });

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function duplicateErrorResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    'holiday_date' => [$message],
                ],
            ], 422);
        }

        return redirect()
            ->route('modules.holidays.index')
            ->withErrors(['holiday_date' => $message])
            ->withInput();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{total:int,upcoming:int,past:int}
     */
    private function buildStats(array $filters): array
    {
        $base = Holiday::query()->whereYear('holiday_date', (int) $filters['year']);

        $branchId = $this->parseBranchFilterId((string) ($filters['branch_id'] ?? ''));
        if ($branchId !== null) {
            $base->where('branch_id', $branchId);
        }

        $holidayType = (string) ($filters['holiday_type'] ?? 'all');
        if ($holidayType !== 'all') {
            $base->where('holiday_type', $holidayType);
        }

        $status = (string) ($filters['status'] ?? 'all');
        if ($status === 'active') {
            $base->where('is_active', true);
        }
        if ($status === 'inactive') {
            $base->where('is_active', false);
        }

        $today = now()->toDateString();

        return [
            'total' => (clone $base)->count(),
            'upcoming' => (clone $base)
                ->whereRaw('COALESCE(end_date, holiday_date) >= ?', [$today])
                ->count(),
            'past' => (clone $base)
                ->whereRaw('COALESCE(end_date, holiday_date) < ?', [$today])
                ->count(),
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
     * @return array<int, string>
     */
    private function yearOptions(): array
    {
        $currentYear = now()->year;

        return collect(range($currentYear - 5, $currentYear + 4))
            ->mapWithKeys(static fn (int $year): array => [$year => (string) $year])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function holidayTypeOptions(): array
    {
        return [
            'public' => 'Public Holiday',
            'company' => 'Company Holiday',
            'optional' => 'Optional Holiday',
        ];
    }

    private function holidayTypeLabel(string $type): string
    {
        $options = $this->holidayTypeOptions();

        return $options[$type] ?? 'Public Holiday';
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFilters(): array
    {
        return [
            'q' => '',
            'year' => now()->year,
            'branch_id' => '',
            'holiday_type' => 'all',
            'status' => 'all',
            'sort' => 'date_asc',
        ];
    }

    private function holidayRouteTemplate(string $routeName): string
    {
        $placeholder = 987654321;

        return str_replace((string) $placeholder, '__HOLIDAY__', route($routeName, ['holiday' => $placeholder]));
    }

    private function activityMetaLabel(Holiday $holiday): string
    {
        $scope = $holiday->branch?->name ?? 'All Branches';
        $range = $this->dateRangeLabel($holiday->holiday_date?->toDateString(), $holiday->end_date?->toDateString());

        return "{$holiday->name} • {$range} • {$scope}";
    }

    private function ensurePermission(Request $request, string $permission): User
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $viewer;
    }
}
