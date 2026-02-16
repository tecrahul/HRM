<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Holiday;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\FinancialYear;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureManagementAccess($request);

        $search = (string) $request->string('q');
        $branchId = (int) $request->integer('branch_id');
        $fy = (int) $request->integer('fy', FinancialYear::currentStartYear());
        if ($fy < 2000 || $fy > 2100) {
            $fy = FinancialYear::currentStartYear();
        }

        $range = FinancialYear::rangeForStartYear($fy);
        $rangeStart = $range['start']->toDateString();
        $rangeEnd = $range['end']->toDateString();

        $holidays = Holiday::query()
            ->with(['branch', 'createdBy'])
            ->withinDateRange($rangeStart, $rangeEnd)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('branch', function (Builder $branchQuery) use ($search): void {
                            $branchQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($branchId > 0, function (Builder $query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->orderBy('holiday_date')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $baseQuery = Holiday::query()->withinDateRange($rangeStart, $rangeEnd);
        $branches = Branch::query()->orderBy('name')->get();

        $currentFy = FinancialYear::currentStartYear();
        $fyOptions = collect(range($currentFy - 2, $currentFy + 2))
            ->mapWithKeys(fn (int $startYear): array => [$startYear => FinancialYear::label($startYear)])
            ->all();

        return view('modules.holidays.index', [
            'holidays' => $holidays,
            'branches' => $branches,
            'fyOptions' => $fyOptions,
            'selectedFy' => $fy,
            'financialYearStartMonth' => FinancialYear::startMonth(),
            'financialYearStartMonthLabel' => FinancialYear::monthLabel(FinancialYear::startMonth()),
            'rangeStart' => $range['start'],
            'rangeEnd' => $range['end'],
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'companyWide' => (clone $baseQuery)->whereNull('branch_id')->count(),
                'branchSpecific' => (clone $baseQuery)->whereNotNull('branch_id')->count(),
                'optional' => (clone $baseQuery)->where('is_optional', true)->count(),
            ],
            'filters' => [
                'q' => $search,
                'branch_id' => $branchId > 0 ? (string) $branchId : '',
                'fy' => (string) $fy,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $this->validatePayload($request);

        $branchId = blank($validated['branch_id'] ?? null) ? null : (int) $validated['branch_id'];
        if ($this->duplicateExists((string) $validated['name'], (string) $validated['holiday_date'], $branchId)) {
            return redirect()
                ->route('modules.holidays.index', ['fy' => (string) $request->string('fy')])
                ->withErrors(['name' => 'A holiday with this name and date already exists for the selected scope.'])
                ->withInput();
        }

        $holiday = Holiday::query()->create([
            'name' => trim((string) $validated['name']),
            'holiday_date' => $validated['holiday_date'],
            'branch_id' => $branchId,
            'is_optional' => (bool) ($validated['is_optional'] ?? false),
            'description' => blank($validated['description'] ?? null) ? null : $validated['description'],
            'created_by_user_id' => $viewer->id,
            'updated_by_user_id' => $viewer->id,
        ]);

        $scopeLabel = $holiday->branch?->name ?? 'Company-wide';

        ActivityLogger::log(
            $viewer,
            'holiday.created',
            'Holiday created',
            "{$holiday->name} • {$holiday->holiday_date?->format('M d, Y')} • {$scopeLabel}",
            '#0ea5e9',
            $holiday
        );

        return redirect()
            ->route('modules.holidays.index', ['fy' => (string) $request->string('fy')])
            ->with('status', 'Holiday created successfully.');
    }

    public function edit(Request $request, Holiday $holiday): View
    {
        $this->ensureManagementAccess($request);

        return view('modules.holidays.edit', [
            'holiday' => $holiday,
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $this->validatePayload($request);

        $branchId = blank($validated['branch_id'] ?? null) ? null : (int) $validated['branch_id'];
        if ($this->duplicateExists((string) $validated['name'], (string) $validated['holiday_date'], $branchId, (int) $holiday->id)) {
            return redirect()
                ->route('modules.holidays.edit', $holiday)
                ->withErrors(['name' => 'A holiday with this name and date already exists for the selected scope.'])
                ->withInput();
        }

        $holiday->update([
            'name' => trim((string) $validated['name']),
            'holiday_date' => $validated['holiday_date'],
            'branch_id' => $branchId,
            'is_optional' => (bool) ($validated['is_optional'] ?? false),
            'description' => blank($validated['description'] ?? null) ? null : $validated['description'],
            'updated_by_user_id' => $viewer->id,
        ]);

        $scopeLabel = $holiday->branch?->name ?? 'Company-wide';

        ActivityLogger::log(
            $viewer,
            'holiday.updated',
            'Holiday updated',
            "{$holiday->name} • {$holiday->holiday_date?->format('M d, Y')} • {$scopeLabel}",
            '#0ea5e9',
            $holiday
        );

        return redirect()
            ->route('modules.holidays.index')
            ->with('status', 'Holiday updated successfully.');
    }

    public function destroy(Request $request, Holiday $holiday): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        $name = (string) $holiday->name;
        $date = $holiday->holiday_date?->format('M d, Y') ?? 'N/A';
        $holiday->delete();

        ActivityLogger::log(
            $viewer,
            'holiday.deleted',
            'Holiday deleted',
            "{$name} • {$date}",
            '#ef4444',
            null
        );

        return redirect()
            ->route('modules.holidays.index')
            ->with('status', 'Holiday deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'holiday_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'is_optional' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function duplicateExists(string $name, string $holidayDate, ?int $branchId, ?int $ignoreId = null): bool
    {
        $query = Holiday::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($name))])
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

    private function ensureManagementAccess(Request $request): User
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->hasAnyRole([UserRole::ADMIN->value, UserRole::HR->value])) {
            abort(403, 'You do not have access to this resource.');
        }

        return $viewer;
    }
}
