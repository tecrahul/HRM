<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollStructure;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\HolidayCalendar;
use App\Support\NotificationCenter;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $viewer = $request->user();

        if ($viewer?->hasRole(UserRole::EMPLOYEE->value)) {
            return $this->employeePage($request, $viewer);
        }

        return $this->managementPage($request);
    }

    public function storeStructure(Request $request): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $this->validateStructurePayload($request);

        $structure = PayrollStructure::query()->firstOrNew([
            'user_id' => (int) $validated['user_id'],
        ]);

        if (! $structure->exists) {
            $structure->created_by_user_id = $viewer->id;
        }

        $structure->fill([
            'basic_salary' => $validated['basic_salary'],
            'hra' => $validated['hra'] ?? 0,
            'special_allowance' => $validated['special_allowance'] ?? 0,
            'bonus' => $validated['bonus'] ?? 0,
            'other_allowance' => $validated['other_allowance'] ?? 0,
            'pf_deduction' => $validated['pf_deduction'] ?? 0,
            'tax_deduction' => $validated['tax_deduction'] ?? 0,
            'other_deduction' => $validated['other_deduction'] ?? 0,
            'effective_from' => blank($validated['effective_from'] ?? null) ? null : $validated['effective_from'],
            'notes' => blank($validated['notes'] ?? null) ? null : $validated['notes'],
            'updated_by_user_id' => $viewer->id,
        ]);
        $structure->save();

        $structure->loadMissing('user');
        $employeeName = $structure->user?->name ?? 'Unknown employee';
        $eventKey = $structure->wasRecentlyCreated ? 'payroll.structure_created' : 'payroll.structure_updated';
        $eventTitle = $structure->wasRecentlyCreated ? 'Salary structure created' : 'Salary structure updated';

        ActivityLogger::log(
            $viewer,
            $eventKey,
            $eventTitle,
            $employeeName,
            '#10b981',
            $structure,
            ['user_id' => $structure->user_id]
        );

        return redirect()
            ->route('modules.payroll.index')
            ->with('status', 'Salary structure saved successfully.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $this->validateGeneratePayload($request, false);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);
        $employeeId = (int) $validated['user_id'];
        $overridePayableDays = array_key_exists('payable_days', $validated)
            ? (float) $validated['payable_days']
            : null;

        $employee = User::query()
            ->where('id', $employeeId)
            ->where('role', UserRole::EMPLOYEE->value)
            ->first();

        if (! $employee) {
            return redirect()
                ->route('modules.payroll.index')
                ->withErrors(['user_id' => 'Selected employee was not found.'])
                ->withInput();
        }

        try {
            $result = $this->createOrUpdatePayroll(
                $employee,
                $monthStart,
                $viewer,
                $overridePayableDays,
                $validated['notes'] ?? null,
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('modules.payroll.index')
                ->withErrors(['payroll_month' => $exception->getMessage()])
                ->withInput();
        }

        $payroll = Payroll::query()
            ->where('user_id', $employee->id)
            ->whereDate('payroll_month', $monthStart->toDateString())
            ->latest('id')
            ->first();

        ActivityLogger::log(
            $viewer,
            $result['updated'] ? 'payroll.updated' : 'payroll.generated',
            $result['updated'] ? 'Payroll recalculated' : 'Payroll generated',
            "{$employee->name} • {$monthStart->format('M Y')}",
            '#10b981',
            $payroll
        );

        if ($payroll instanceof Payroll) {
            NotificationCenter::notifyUser(
                $employee,
                "payroll.generated.{$employee->id}.{$monthStart->format('Y-m')}",
                $result['updated'] ? 'Payroll recalculated' : 'Payroll generated',
                "Payroll for {$monthStart->format('M Y')} is now available.",
                route('modules.payroll.index'),
                'info',
                0
            );
        }

        return redirect()
            ->route('modules.payroll.index')
            ->with('status', $result['updated'] ? 'Payroll recalculated successfully.' : 'Payroll generated successfully.');
    }

    public function generateBulk(Request $request): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $this->validateGeneratePayload($request, true);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);

        $employees = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->with('payrollStructure')
            ->orderBy('name')
            ->get();

        $generatedCount = 0;
        $noStructureCount = 0;
        $paidLockedCount = 0;

        foreach ($employees as $employee) {
            if (! $employee->payrollStructure) {
                $noStructureCount++;

                continue;
            }

            try {
                $this->createOrUpdatePayroll(
                    $employee,
                    $monthStart,
                    $viewer,
                    null,
                    $validated['notes'] ?? null,
                );
                $generatedCount++;
            } catch (DomainException) {
                $paidLockedCount++;
            }
        }

        ActivityLogger::log(
            $viewer,
            'payroll.generated_bulk',
            'Bulk payroll generated',
            "{$monthStart->format('M Y')} • generated {$generatedCount}, missing {$noStructureCount}, locked {$paidLockedCount}",
            '#10b981',
            null,
            [
                'month' => $monthStart->format('Y-m'),
                'generated' => $generatedCount,
                'missing_structure' => $noStructureCount,
                'locked_paid' => $paidLockedCount,
            ]
        );

        return redirect()
            ->route('modules.payroll.index')
            ->with(
                'status',
                "Bulk payroll complete. Generated/updated: {$generatedCount}, missing salary structure: {$noStructureCount}, locked paid records: {$paidLockedCount}."
            );
    }

    public function updateStatus(Request $request, Payroll $payroll): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $payroll->loadMissing('user');

        if (! $payroll->user?->hasRole(UserRole::EMPLOYEE->value)) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(Payroll::statuses())],
            'payment_method' => ['nullable', Rule::in(Payroll::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (
            $validated['status'] === Payroll::STATUS_PAID
            && blank($validated['payment_method'] ?? null)
        ) {
            return redirect()
                ->route('modules.payroll.index')
                ->withErrors(['payment_method' => 'Please select a payment method before marking payroll as paid.'])
                ->withInput();
        }

        $payroll->status = $validated['status'];
        $payroll->notes = blank($validated['notes'] ?? null) ? null : $validated['notes'];

        if ($validated['status'] === Payroll::STATUS_PAID) {
            $payroll->payment_method = $validated['payment_method'];
            $payroll->payment_reference = blank($validated['payment_reference'] ?? null)
                ? null
                : $validated['payment_reference'];
            $payroll->paid_by_user_id = $viewer->id;
            $payroll->paid_at = now();
        } else {
            $payroll->payment_method = null;
            $payroll->payment_reference = null;
            $payroll->paid_by_user_id = null;
            $payroll->paid_at = null;
        }

        $payroll->save();

        $statusLabel = str((string) $payroll->status)->replace('_', ' ')->title();
        ActivityLogger::log(
            $viewer,
            'payroll.status_updated',
            "Payroll {$statusLabel}",
            "{$payroll->user?->name} • {$payroll->payroll_month?->format('M Y')}",
            '#10b981',
            $payroll,
            ['status' => (string) $payroll->status]
        );

        if ($payroll->user instanceof User) {
            NotificationCenter::notifyUser(
                $payroll->user,
                "payroll.status.{$payroll->id}.{$payroll->status}",
                "Payroll {$statusLabel}",
                "Your payroll status for {$payroll->payroll_month?->format('M Y')} is now {$statusLabel}.",
                route('modules.payroll.index'),
                $payroll->status === Payroll::STATUS_PAID ? 'success' : 'warning',
                0
            );
        }

        return redirect()
            ->route('modules.payroll.index')
            ->with('status', 'Payroll status updated successfully.');
    }

    private function managementPage(Request $request): View
    {
        $search = (string) $request->string('q');
        $status = (string) $request->string('status');
        $employeeId = (int) $request->integer('employee_id');
        $monthFilter = (string) $request->string('payroll_month');

        $monthStart = $this->resolveMonthOrCurrent($monthFilter);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $statusOptions = Payroll::statuses();
        $employeeRole = UserRole::EMPLOYEE->value;

        $records = Payroll::query()
            ->with(['user.profile', 'generator', 'paidBy'])
            ->whereHas('user', function (Builder $query) use ($employeeRole): void {
                $query->where('role', $employeeRole);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('payment_reference', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '' && in_array($status, $statusOptions, true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($employeeId > 0, function (Builder $query) use ($employeeId): void {
                $query->where('user_id', $employeeId);
            })
            ->when($monthFilter !== '' && $this->isValidMonthFormat($monthFilter), function (Builder $query) use ($monthStart): void {
                $query->whereYear('payroll_month', $monthStart->year)
                    ->whereMonth('payroll_month', $monthStart->month);
            })
            ->orderByDesc('payroll_month')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $employeeIds = User::query()
            ->where('role', $employeeRole)
            ->pluck('id');

        $monthBaseQuery = Payroll::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('payroll_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $structures = PayrollStructure::query()
            ->with('user.profile')
            ->whereIn('user_id', $employeeIds)
            ->orderByDesc('updated_at')
            ->get();

        return view('modules.payroll.admin', [
            'records' => $records,
            'employees' => $this->employeeOptions(),
            'structures' => $structures,
            'statusOptions' => $statusOptions,
            'paymentMethodOptions' => Payroll::paymentMethods(),
            'stats' => [
                'totalEmployees' => $employeeIds->count(),
                'employeesWithStructure' => PayrollStructure::query()->whereIn('user_id', $employeeIds)->count(),
                'generatedThisMonth' => (clone $monthBaseQuery)->count(),
                'paidThisMonth' => (clone $monthBaseQuery)->where('status', Payroll::STATUS_PAID)->count(),
                'pendingThisMonth' => (clone $monthBaseQuery)->where('status', '!=', Payroll::STATUS_PAID)->count(),
                'netThisMonth' => (float) ((clone $monthBaseQuery)->sum('net_salary')),
            ],
            'filters' => [
                'q' => $search,
                'status' => $status,
                'employee_id' => $employeeId > 0 ? (string) $employeeId : '',
                'payroll_month' => $monthStart->format('Y-m'),
            ],
        ]);
    }

    private function employeePage(Request $request, User $viewer): View
    {
        $status = (string) $request->string('status');
        $monthFilter = (string) $request->string('payroll_month');
        $monthStart = $this->resolveMonthOrCurrent($monthFilter);

        $statusOptions = Payroll::statuses();

        $records = Payroll::query()
            ->where('user_id', $viewer->id)
            ->when($status !== '' && in_array($status, $statusOptions, true), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($monthFilter !== '' && $this->isValidMonthFormat($monthFilter), function (Builder $query) use ($monthStart): void {
                $query->whereYear('payroll_month', $monthStart->year)
                    ->whereMonth('payroll_month', $monthStart->month);
            })
            ->orderByDesc('payroll_month')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $latestPayroll = Payroll::query()
            ->where('user_id', $viewer->id)
            ->orderByDesc('payroll_month')
            ->orderByDesc('id')
            ->first();

        $currentYear = (int) now()->format('Y');

        return view('modules.payroll.employee', [
            'viewer' => $viewer,
            'records' => $records,
            'latestPayroll' => $latestPayroll,
            'statusOptions' => $statusOptions,
            'stats' => [
                'paidCount' => Payroll::query()
                    ->where('user_id', $viewer->id)
                    ->where('status', Payroll::STATUS_PAID)
                    ->count(),
                'thisYearNet' => (float) Payroll::query()
                    ->where('user_id', $viewer->id)
                    ->whereYear('payroll_month', $currentYear)
                    ->sum('net_salary'),
                'lastNet' => (float) ($latestPayroll?->net_salary ?? 0),
                'lastStatus' => (string) ($latestPayroll?->status ?? 'not_generated'),
            ],
            'filters' => [
                'status' => $status,
                'payroll_month' => $monthStart->format('Y-m'),
            ],
        ]);
    }

    /**
     * @return array{updated: bool}
     */
    private function createOrUpdatePayroll(
        User $employee,
        Carbon $monthStart,
        User $viewer,
        ?float $overridePayableDays,
        ?string $notes,
    ): array {
        $structure = PayrollStructure::query()
            ->where('user_id', $employee->id)
            ->first();

        if (! $structure) {
            throw new DomainException("Salary structure is missing for {$employee->name}.");
        }

        $employee->loadMissing('profile');

        $monthDate = $monthStart->toDateString();

        $existingPayroll = Payroll::query()
            ->where('user_id', $employee->id)
            ->whereDate('payroll_month', $monthDate)
            ->first();

        if ($existingPayroll && $existingPayroll->status === Payroll::STATUS_PAID) {
            throw new DomainException("Payroll for {$employee->name} is already marked paid for {$monthStart->format('M Y')}.");
        }

        $calculated = $this->calculatePayroll($employee, $monthStart, $structure, $overridePayableDays);

        $payload = array_merge($calculated, [
            'status' => Payroll::STATUS_DRAFT,
            'notes' => blank($notes) ? null : $notes,
            'generated_by_user_id' => $viewer->id,
            'paid_by_user_id' => null,
            'paid_at' => null,
            'payment_method' => null,
            'payment_reference' => null,
        ]);

        if ($existingPayroll) {
            $existingPayroll->fill($payload);
            $existingPayroll->save();

            return ['updated' => true];
        }

        Payroll::query()->create(array_merge($payload, [
            'user_id' => $employee->id,
        ]));

        return ['updated' => false];
    }

    /**
     * @return array<string, float|string>
     */
    private function calculatePayroll(
        User $employee,
        Carbon $monthStart,
        PayrollStructure $structure,
        ?float $overridePayableDays,
    ): array {
        $monthStart = $monthStart->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $holidayMap = HolidayCalendar::dateMap($monthStart, $monthEnd, $employee->profile?->branch, false);
        $holidayCount = (float) count($holidayMap);
        $workingDays = max(0.0, (float) $monthStart->daysInMonth - $holidayCount);

        $attendanceLopDays = $this->attendanceLopDays($employee->id, $monthStart, $monthEnd, $holidayMap);
        $unpaidLeaveDays = $this->unpaidLeaveDays($employee->id, $monthStart, $monthEnd, $holidayMap);
        $lopDays = round(min($workingDays, $attendanceLopDays + $unpaidLeaveDays), 2);
        $payableDays = round(max(0.0, $workingDays - $lopDays), 2);

        if ($overridePayableDays !== null) {
            if ($overridePayableDays < 0 || $overridePayableDays > $workingDays) {
                throw new DomainException('Payable days must be between 0 and total days in month.');
            }

            $payableDays = round($overridePayableDays, 2);
            $lopDays = round(max(0.0, $workingDays - $payableDays), 2);
        }

        $prorationRatio = $workingDays > 0 ? ($payableDays / $workingDays) : 0.0;

        $basicPay = round((float) $structure->basic_salary * $prorationRatio, 2);
        $hra = round((float) $structure->hra * $prorationRatio, 2);
        $specialAllowance = round((float) $structure->special_allowance * $prorationRatio, 2);
        $bonus = round((float) $structure->bonus * $prorationRatio, 2);
        $otherAllowance = round((float) $structure->other_allowance * $prorationRatio, 2);

        $grossEarnings = round($basicPay + $hra + $specialAllowance + $bonus + $otherAllowance, 2);

        $pfDeduction = round((float) $structure->pf_deduction, 2);
        $taxDeduction = round((float) $structure->tax_deduction, 2);
        $otherDeduction = round((float) $structure->other_deduction, 2);
        $totalDeductions = round($pfDeduction + $taxDeduction + $otherDeduction, 2);

        $netSalary = round(max(0.0, $grossEarnings - $totalDeductions), 2);

        return [
            'payroll_month' => $monthStart->toDateString(),
            'working_days' => $workingDays,
            'attendance_lop_days' => $attendanceLopDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'lop_days' => $lopDays,
            'payable_days' => $payableDays,
            'basic_pay' => $basicPay,
            'hra' => $hra,
            'special_allowance' => $specialAllowance,
            'bonus' => $bonus,
            'other_allowance' => $otherAllowance,
            'gross_earnings' => $grossEarnings,
            'pf_deduction' => $pfDeduction,
            'tax_deduction' => $taxDeduction,
            'other_deduction' => $otherDeduction,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
        ];
    }

    /**
     * @param array<string, bool> $holidayMap
     */
    private function attendanceLopDays(int $userId, Carbon $monthStart, Carbon $monthEnd, array $holidayMap): float
    {
        $records = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['status', 'attendance_date']);

        $lopDays = $records->sum(function (Attendance $record) use ($holidayMap): float {
            $attendanceDate = $record->attendance_date?->toDateString();
            if ($attendanceDate && isset($holidayMap[$attendanceDate])) {
                return 0.0;
            }

            return match ($record->status) {
                Attendance::STATUS_ABSENT => 1.0,
                Attendance::STATUS_HALF_DAY => 0.5,
                default => 0.0,
            };
        });

        return round((float) $lopDays, 2);
    }

    /**
     * @param array<string, bool> $holidayMap
     */
    private function unpaidLeaveDays(int $userId, Carbon $monthStart, Carbon $monthEnd, array $holidayMap): float
    {
        $unpaidLeaves = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('leave_type', LeaveRequest::TYPE_UNPAID)
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->get();

        $unpaidDays = 0.0;

        foreach ($unpaidLeaves as $leave) {
            $leaveStart = $leave->start_date?->copy()->startOfDay();
            $leaveEnd = $leave->end_date?->copy()->startOfDay();

            if (! $leaveStart || ! $leaveEnd) {
                continue;
            }

            $effectiveStart = $leaveStart->greaterThan($monthStart) ? $leaveStart : $monthStart->copy();
            $effectiveEnd = $leaveEnd->lessThan($monthEnd) ? $leaveEnd : $monthEnd->copy();

            if ($effectiveStart->greaterThan($effectiveEnd)) {
                continue;
            }

            $isHalfDay = ($leave->day_type ?? null) === LeaveRequest::DAY_TYPE_HALF
                || (float) $leave->total_days === 0.5;

            if ($isHalfDay) {
                if (! isset($holidayMap[$effectiveStart->toDateString()])) {
                    $unpaidDays += 0.5;
                }

                continue;
            }

            $cursor = $effectiveStart->copy();
            while ($cursor->lessThanOrEqualTo($effectiveEnd)) {
                if (! isset($holidayMap[$cursor->toDateString()])) {
                    $unpaidDays += 1.0;
                }
                $cursor->addDay();
            }
        }

        return round($unpaidDays, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStructurePayload(Request $request): array
    {
        return $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->where('role', UserRole::EMPLOYEE->value);
                }),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            'special_allowance' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'pf_deduction' => ['nullable', 'numeric', 'min:0'],
            'tax_deduction' => ['nullable', 'numeric', 'min:0'],
            'other_deduction' => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGeneratePayload(Request $request, bool $bulk): array
    {
        $rules = [
            'payroll_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($bulk) {
            $rules['user_id'] = ['prohibited'];
            $rules['payable_days'] = ['prohibited'];
        } else {
            $rules['user_id'] = [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->where('role', UserRole::EMPLOYEE->value);
                }),
            ];
            $rules['payable_days'] = ['nullable', 'numeric', 'min:0'];
        }

        return $request->validate($rules);
    }

    private function ensureManagementAccess(Request $request): User
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->hasAnyRole([UserRole::ADMIN->value, UserRole::HR->value])) {
            abort(403, 'You do not have access to this resource.');
        }

        return $viewer;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function employeeOptions()
    {
        return User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->with(['profile', 'payrollStructure'])
            ->orderBy('name')
            ->get();
    }

    private function resolveMonth(string $month): Carbon
    {
        if (! $this->isValidMonthFormat($month)) {
            throw new DomainException('Invalid payroll month format. Use YYYY-MM.');
        }

        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }

    private function resolveMonthOrCurrent(string $month): Carbon
    {
        if (! $this->isValidMonthFormat($month)) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }

    private function isValidMonthFormat(string $month): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1;
    }
}
