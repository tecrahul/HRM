<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollMonthLock;
use App\Models\PayrollStructure;
use App\Models\PayrollStructureHistory;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\HolidayCalendar;
use App\Support\NotificationCenter;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer?->hasRole(UserRole::EMPLOYEE->value)) {
            return $this->employeePage($request, $viewer);
        }

        return redirect()->route('modules.payroll.dashboard');
    }

    public function storeStructure(Request $request): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);
        $validated = $this->validateStructurePayload($request);

        $result = $this->saveStructureWithHistory($viewer, $validated);
        $structure = $result['structure'];

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

        $this->logPayrollAudit(
            $viewer,
            'payroll_structure',
            (int) $structure->id,
            $structure->wasRecentlyCreated ? 'structure.create' : 'structure.update',
            $result['beforeValues'],
            $result['afterValues'],
            ['user_id' => (int) $structure->user_id]
        );

        return redirect()
            ->route('modules.payroll.index')
            ->with('status', 'Salary structure saved successfully.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);
        $validated = $this->validateGeneratePayload($request, false);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);
        $employeeId = (int) $validated['user_id'];
        $overridePayableDays = array_key_exists('payable_days', $validated)
            ? (float) $validated['payable_days']
            : null;

        $employee = User::query()
            ->where('id', $employeeId)
            ->workforce()
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

    public function previewWorkflow(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ],
            'payroll_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'payable_days' => ['nullable', 'numeric', 'min:0'],
        ]);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);
        $employee = User::query()
            ->where('id', (int) $validated['user_id'])
            ->workforce()
            ->with('profile')
            ->first();

        if (! $employee) {
            return response()->json([
                'message' => 'Selected employee was not found.',
            ], 422);
        }

        $structure = PayrollStructure::query()
            ->where('user_id', $employee->id)
            ->first();

        if (! $structure) {
            return response()->json([
                'message' => "Salary structure is missing for {$employee->name}.",
            ], 422);
        }

        try {
            $calculation = $this->calculatePayroll(
                $employee,
                $monthStart,
                $structure,
                array_key_exists('payable_days', $validated) ? (float) $validated['payable_days'] : null,
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $existingPayroll = Payroll::query()
            ->where('user_id', $employee->id)
            ->whereDate('payroll_month', $monthStart->toDateString())
            ->latest('id')
            ->first();

        return response()->json([
            'preview' => array_merge($calculation, [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'department' => $employee->profile?->department ?? '',
                ],
                'monthLabel' => $monthStart->format('M Y'),
            ]),
            'existingPayroll' => $existingPayroll ? $this->workflowPayrollPayload($existingPayroll) : null,
            'isLocked' => $existingPayroll?->status === Payroll::STATUS_PAID,
        ]);
    }

    public function generateWorkflow(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);
        $validated = $this->validateGeneratePayload($request, false);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);
        $employeeId = (int) $validated['user_id'];
        $overridePayableDays = array_key_exists('payable_days', $validated)
            ? (float) $validated['payable_days']
            : null;

        $employee = User::query()
            ->where('id', $employeeId)
            ->workforce()
            ->first();

        if (! $employee) {
            return response()->json([
                'message' => 'Selected employee was not found.',
            ], 422);
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
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $payroll = Payroll::query()
            ->where('user_id', $employee->id)
            ->whereDate('payroll_month', $monthStart->toDateString())
            ->latest('id')
            ->first();

        if (! $payroll instanceof Payroll) {
            return response()->json([
                'message' => 'Unable to locate generated payroll record.',
            ], 500);
        }

        ActivityLogger::log(
            $viewer,
            $result['updated'] ? 'payroll.updated' : 'payroll.generated',
            $result['updated'] ? 'Payroll recalculated' : 'Payroll generated',
            "{$employee->name} • {$monthStart->format('M Y')}",
            '#10b981',
            $payroll
        );

        NotificationCenter::notifyUser(
            $employee,
            "payroll.generated.{$employee->id}.{$monthStart->format('Y-m')}",
            $result['updated'] ? 'Payroll recalculated' : 'Payroll generated',
            "Payroll for {$monthStart->format('M Y')} is now available.",
            route('modules.payroll.index'),
            'info',
            0
        );

        $this->logPayrollAudit(
            $viewer,
            'payroll',
            (int) $payroll->id,
            $result['updated'] ? 'payroll.generate.updated' : 'payroll.generate.created',
            null,
            $this->workflowPayrollPayload($payroll),
            ['user_id' => $employee->id, 'month' => $monthStart->format('Y-m')]
        );

        return response()->json([
            'message' => $result['updated'] ? 'Payroll recalculated successfully.' : 'Payroll generated successfully.',
            'payroll' => $this->workflowPayrollPayload($payroll),
            'updated' => (bool) $result['updated'],
        ]);
    }

    public function approveWorkflow(Request $request, Payroll $payroll): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanApprove($viewer);
        $payroll->loadMissing(['user.profile']);

        if (! $payroll->user?->isEmployeeRecord()) {
            abort(404);
        }

        if ($this->activeMonthLock($payroll->payroll_month?->copy()->startOfMonth() ?? now()->startOfMonth()) !== null
            && ! $viewer->hasRole(UserRole::SUPER_ADMIN->value)) {
            return response()->json([
                'message' => 'Payroll month is locked.',
            ], 422);
        }

        if ($payroll->status === Payroll::STATUS_PAID) {
            return response()->json([
                'message' => 'Paid payroll is locked and cannot be modified.',
            ], 422);
        }

        $beforeValues = $this->workflowPayrollPayload($payroll);

        $payroll->status = Payroll::STATUS_PROCESSED;
        $payroll->approved_by_user_id = $viewer->id;
        $payroll->approved_at = now();
        $payroll->save();

        ActivityLogger::log(
            $viewer,
            'payroll.status_updated',
            'Payroll Processed',
            "{$payroll->user?->name} • {$payroll->payroll_month?->format('M Y')}",
            '#10b981',
            $payroll,
            ['status' => (string) $payroll->status]
        );

        if ($payroll->user instanceof User) {
            NotificationCenter::notifyUser(
                $payroll->user,
                "payroll.status.{$payroll->id}.{$payroll->status}",
                'Payroll Processed',
                "Your payroll status for {$payroll->payroll_month?->format('M Y')} is now Processed.",
                route('modules.payroll.index'),
                'warning',
                0
            );
        }

        $this->logPayrollAudit(
            $viewer,
            'payroll',
            (int) $payroll->id,
            'payroll.approve',
            $beforeValues,
            $this->workflowPayrollPayload($payroll),
            ['status' => (string) $payroll->status]
        );

        return response()->json([
            'message' => 'Payroll approved successfully.',
            'payroll' => $this->workflowPayrollPayload($payroll),
        ]);
    }

    public function markPaidWorkflow(Request $request, Payroll $payroll): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanMarkPaid($viewer);
        $payroll->loadMissing(['user.profile']);

        if (! $payroll->user?->isEmployeeRecord()) {
            abort(404);
        }

        if ($this->activeMonthLock($payroll->payroll_month?->copy()->startOfMonth() ?? now()->startOfMonth()) !== null
            && ! $viewer->hasRole(UserRole::SUPER_ADMIN->value)) {
            return response()->json([
                'message' => 'Payroll month is locked.',
            ], 422);
        }

        if ($payroll->status === Payroll::STATUS_PAID) {
            return response()->json([
                'message' => 'Paid payroll is locked and cannot be modified.',
            ], 422);
        }

        if ($payroll->status !== Payroll::STATUS_PROCESSED) {
            return response()->json([
                'message' => 'Payroll must be approved before marking as paid.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(Payroll::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $beforeValues = $this->workflowPayrollPayload($payroll);

        $payroll->status = Payroll::STATUS_PAID;
        $payroll->payment_method = (string) $validated['payment_method'];
        $payroll->payment_reference = blank($validated['payment_reference'] ?? null)
            ? null
            : (string) $validated['payment_reference'];
        $payroll->notes = blank($validated['notes'] ?? null)
            ? null
            : (string) $validated['notes'];
        $payroll->paid_by_user_id = $viewer->id;
        $payroll->paid_at = now();
        $payroll->save();

        ActivityLogger::log(
            $viewer,
            'payroll.status_updated',
            'Payroll Paid',
            "{$payroll->user?->name} • {$payroll->payroll_month?->format('M Y')}",
            '#10b981',
            $payroll,
            ['status' => (string) $payroll->status]
        );

        if ($payroll->user instanceof User) {
            NotificationCenter::notifyUser(
                $payroll->user,
                "payroll.status.{$payroll->id}.{$payroll->status}",
                'Payroll Paid',
                "Your payroll status for {$payroll->payroll_month?->format('M Y')} is now Paid.",
                route('modules.payroll.index'),
                'success',
                0
            );
        }

        $this->logPayrollAudit(
            $viewer,
            'payroll',
            (int) $payroll->id,
            'payroll.mark_paid',
            $beforeValues,
            $this->workflowPayrollPayload($payroll),
            ['status' => (string) $payroll->status]
        );

        return response()->json([
            'message' => 'Payroll marked as paid successfully.',
            'payroll' => $this->workflowPayrollPayload($payroll),
            'isLocked' => true,
        ]);
    }

    public function workflowOverviewApi(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $request->validate($this->workflowFilterRules(false));
        $resolvedFilters = $this->resolveWorkflowFilters($validated);

        $monthStart = $this->resolveMonthOrCurrent((string) ($validated['payroll_month'] ?? ''));

        return response()->json(
            $this->buildWorkflowOverviewPayload(
                $monthStart,
                $resolvedFilters['branch'],
                $resolvedFilters['department'],
                $resolvedFilters['employeeId'],
                $viewer
            )
        );
    }

    public function workflowPreviewBatchApi(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);
        $validated = $request->validate($this->workflowFilterRules(true));
        $resolvedFilters = $this->resolveWorkflowFilters($validated);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);

        $activeLock = $this->activeMonthLock($monthStart);
        if ($activeLock !== null) {
            return response()->json([
                'message' => 'Payroll month is closed and locked.',
            ], 422);
        }

        $employees = $this->workflowEmployeeQuery(
            $resolvedFilters['branch'],
            $resolvedFilters['department'],
            $resolvedFilters['employeeId']
        )->get();
        $generatedCount = $this->workflowPayrollQuery(
            $monthStart,
            $resolvedFilters['branch'],
            $resolvedFilters['department'],
            $resolvedFilters['employeeId']
        )->count();

        $rows = [];
        $grossTotal = 0.0;
        $deductionTotal = 0.0;
        $netTotal = 0.0;
        $employeesWithErrors = 0;
        $missingStructure = 0;

        foreach ($employees as $employee) {
            $structure = $employee->payrollStructure;
            if (! $structure) {
                $missingStructure++;
                $employeesWithErrors++;
                $rows[] = [
                    'employeeId' => $employee->id,
                    'employeeName' => $employee->name,
                    'department' => $employee->profile?->department ?? '',
                    'gross' => 0,
                    'deductions' => 0,
                    'net' => 0,
                    'error' => 'Missing salary structure',
                ];
                continue;
            }

            try {
                $calculation = $this->calculatePayroll($employee, $monthStart, $structure, null);
                $gross = (float) ($calculation['gross_earnings'] ?? 0);
                $deductions = (float) ($calculation['total_deductions'] ?? 0);
                $net = (float) ($calculation['net_salary'] ?? 0);

                $grossTotal += $gross;
                $deductionTotal += $deductions;
                $netTotal += $net;

                $rows[] = [
                    'employeeId' => $employee->id,
                    'employeeName' => $employee->name,
                    'department' => $employee->profile?->department ?? '',
                    'gross' => $gross,
                    'deductions' => $deductions,
                    'net' => $net,
                    'error' => null,
                ];
            } catch (DomainException $exception) {
                $employeesWithErrors++;
                $rows[] = [
                    'employeeId' => $employee->id,
                    'employeeName' => $employee->name,
                    'department' => $employee->profile?->department ?? '',
                    'gross' => 0,
                    'deductions' => 0,
                    'net' => 0,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'summary' => [
                'totalEmployees' => $employees->count(),
                'grossTotal' => round($grossTotal, 2),
                'deductionTotal' => round($deductionTotal, 2),
                'netTotal' => round($netTotal, 2),
                'employeesWithErrors' => $employeesWithErrors,
                'missingSalaryStructure' => $missingStructure,
            ],
            'rows' => $rows,
            'warning' => $generatedCount > 0
                ? 'Payroll is already generated for some employees in this month. Regeneration will update existing draft records.'
                : null,
        ]);
    }

    public function workflowGenerateBatchApi(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);
        $validated = $request->validate(array_merge(
            $this->workflowFilterRules(true),
            [
                'notes' => ['nullable', 'string', 'max:1000'],
            ]
        ));
        $resolvedFilters = $this->resolveWorkflowFilters($validated);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);

        $activeLock = $this->activeMonthLock($monthStart);
        if ($activeLock !== null) {
            return response()->json([
                'message' => 'Payroll month is closed and locked.',
            ], 422);
        }

        $employees = $this->workflowEmployeeQuery(
            $resolvedFilters['branch'],
            $resolvedFilters['department'],
            $resolvedFilters['employeeId']
        )->get();
        $generated = 0;
        $updated = 0;
        $missingStructure = 0;
        $failed = 0;

        foreach ($employees as $employee) {
            if (! $employee->payrollStructure) {
                $missingStructure++;
                continue;
            }

            try {
                $result = $this->createOrUpdatePayroll(
                    $employee,
                    $monthStart,
                    $viewer,
                    null,
                    $validated['notes'] ?? null
                );

                if ($result['updated']) {
                    $updated++;
                } else {
                    $generated++;
                }
            } catch (DomainException) {
                $failed++;
            }
        }

        $this->logPayrollAudit(
            $viewer,
            'payroll',
            null,
            'payroll.workflow.generate_batch',
            null,
            null,
            [
                'month' => $monthStart->format('Y-m'),
                'branch' => $resolvedFilters['branch'],
                'department' => $resolvedFilters['department'],
                'employee_id' => $resolvedFilters['employeeId'],
                'generated' => $generated,
                'updated' => $updated,
                'failed' => $failed,
                'missing_structure' => $missingStructure,
            ]
        );

        return response()->json([
            'message' => 'Payroll generated successfully.',
            'summary' => [
                'generated' => $generated,
                'updated' => $updated,
                'failed' => $failed,
                'missingSalaryStructure' => $missingStructure,
            ],
            'overview' => $this->buildWorkflowOverviewPayload(
                $monthStart,
                $resolvedFilters['branch'],
                $resolvedFilters['department'],
                $resolvedFilters['employeeId'],
                $viewer
            ),
        ]);
    }

    public function workflowApproveBatchApi(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanApprove($viewer);
        $validated = $request->validate(array_merge(
            $this->workflowFilterRules(true),
            [
                'payroll_ids' => ['nullable', 'array', 'max:2000'],
                'payroll_ids.*' => ['integer', Rule::exists('payrolls', 'id')],
            ]
        ));
        $resolvedFilters = $this->resolveWorkflowFilters($validated);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);

        $activeLock = $this->activeMonthLock($monthStart);
        if ($activeLock !== null) {
            return response()->json([
                'message' => 'Payroll month is closed and locked.',
            ], 422);
        }

        $query = $this->workflowPayrollQuery(
            $monthStart,
            $resolvedFilters['branch'],
            $resolvedFilters['department'],
            $resolvedFilters['employeeId']
        )
            ->whereIn('status', [Payroll::STATUS_DRAFT, Payroll::STATUS_FAILED]);

        $ids = collect((array) ($validated['payroll_ids'] ?? []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        $records = $query->get();
        $approved = 0;

        foreach ($records as $payroll) {
            $beforeValues = $this->workflowPayrollPayload($payroll);
            $payroll->status = Payroll::STATUS_PROCESSED;
            $payroll->approved_by_user_id = $viewer->id;
            $payroll->approved_at = now();
            $payroll->save();
            $approved++;

            $this->logPayrollAudit(
                $viewer,
                'payroll',
                (int) $payroll->id,
                'payroll.workflow.approve_batch',
                $beforeValues,
                $this->workflowPayrollPayload($payroll),
                ['month' => $monthStart->format('Y-m')]
            );
        }

        return response()->json([
            'message' => 'Selected payroll records approved.',
            'summary' => [
                'approved' => $approved,
            ],
            'overview' => $this->buildWorkflowOverviewPayload(
                $monthStart,
                $resolvedFilters['branch'],
                $resolvedFilters['department'],
                $resolvedFilters['employeeId'],
                $viewer
            ),
        ]);
    }

    public function workflowPayCloseApi(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanMarkPaid($viewer);
        $validated = $request->validate(array_merge(
            $this->workflowFilterRules(true),
            [
                'payment_method' => ['required', Rule::in(Payroll::paymentMethods())],
                'payment_reference' => ['nullable', 'string', 'max:120'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'confirm_lock' => ['required', 'accepted'],
            ]
        ));
        $resolvedFilters = $this->resolveWorkflowFilters($validated);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);

        $activeLock = $this->activeMonthLock($monthStart);
        if ($activeLock !== null) {
            return response()->json([
                'message' => 'Payroll month is already locked.',
            ], 422);
        }

        $records = $this->workflowPayrollQuery(
            $monthStart,
            $resolvedFilters['branch'],
            $resolvedFilters['department'],
            $resolvedFilters['employeeId']
        )->get();
        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No payroll records found for selected month.',
            ], 422);
        }

        $hasPending = $records->contains(function (Payroll $payroll): bool {
            return ! in_array($payroll->status, [Payroll::STATUS_PROCESSED, Payroll::STATUS_PAID], true);
        });

        if ($hasPending) {
            return response()->json([
                'message' => 'All payroll records must be approved before payment.',
            ], 422);
        }

        $paidNow = 0;
        foreach ($records as $payroll) {
            if ($payroll->status === Payroll::STATUS_PAID) {
                continue;
            }

            $beforeValues = $this->workflowPayrollPayload($payroll);
            $payroll->status = Payroll::STATUS_PAID;
            $payroll->payment_method = (string) $validated['payment_method'];
            $payroll->payment_reference = blank($validated['payment_reference'] ?? null)
                ? null
                : (string) $validated['payment_reference'];
            $payroll->notes = blank($validated['notes'] ?? null)
                ? null
                : (string) $validated['notes'];
            $payroll->paid_by_user_id = $viewer->id;
            $payroll->paid_at = now();
            $payroll->save();
            $paidNow++;

            $this->logPayrollAudit(
                $viewer,
                'payroll',
                (int) $payroll->id,
                'payroll.workflow.pay_close',
                $beforeValues,
                $this->workflowPayrollPayload($payroll),
                ['month' => $monthStart->format('Y-m')]
            );
        }

        PayrollMonthLock::query()->create([
            'payroll_month' => $monthStart->toDateString(),
            'locked_by_user_id' => $viewer->id,
            'locked_at' => now(),
            'metadata' => [
                'payment_method' => (string) $validated['payment_method'],
                'payment_reference' => (string) ($validated['payment_reference'] ?? ''),
                'branch' => $resolvedFilters['branch'],
                'department' => $resolvedFilters['department'],
                'employee_id' => $resolvedFilters['employeeId'],
                'paid_now' => $paidNow,
            ],
        ]);

        $this->logPayrollAudit(
            $viewer,
            'payroll_month',
            null,
            'payroll.workflow.month_locked',
            null,
            null,
            ['month' => $monthStart->format('Y-m'), 'paid_now' => $paidNow]
        );

        return response()->json([
            'message' => 'Payroll paid and month closed successfully.',
            'summary' => [
                'paid' => $paidNow,
            ],
            'overview' => $this->buildWorkflowOverviewPayload(
                $monthStart,
                $resolvedFilters['branch'],
                $resolvedFilters['department'],
                $resolvedFilters['employeeId'],
                $viewer
            ),
        ]);
    }

    public function workflowUnlockApi(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanUnlock($viewer);
        $validated = $request->validate([
            'payroll_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'unlock_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);
        $activeLock = $this->activeMonthLock($monthStart);
        if (! $activeLock instanceof PayrollMonthLock) {
            return response()->json([
                'message' => 'Selected payroll month is not locked.',
            ], 422);
        }

        $activeLock->unlocked_by_user_id = $viewer->id;
        $activeLock->unlocked_at = now();
        $activeLock->unlock_reason = blank($validated['unlock_reason'] ?? null)
            ? null
            : (string) $validated['unlock_reason'];
        $activeLock->save();

        $this->logPayrollAudit(
            $viewer,
            'payroll_month',
            (int) $activeLock->id,
            'payroll.workflow.month_unlocked',
            null,
            null,
            ['month' => $monthStart->format('Y-m'), 'reason' => $activeLock->unlock_reason]
        );

        return response()->json([
            'message' => 'Payroll month unlocked successfully.',
            'overview' => $this->buildWorkflowOverviewPayload($monthStart, null, null, null, $viewer),
        ]);
    }

    public function upsertStructureApi(Request $request, User $user): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);

        if (! $user->isEmployeeRecord()) {
            abort(404);
        }

        // Upsert endpoint identifies employee by route param; inject user_id for shared validator.
        $request->merge([
            'user_id' => $user->id,
        ]);

        $validated = $this->validateStructurePayload($request);

        $result = $this->saveStructureWithHistory($viewer, $validated);
        $structure = $result['structure']->loadMissing(['user.profile', 'updatedBy']);

        $this->logPayrollAudit(
            $viewer,
            'payroll_structure',
            (int) $structure->id,
            $structure->wasRecentlyCreated ? 'structure.create' : 'structure.update',
            $result['beforeValues'],
            $result['afterValues'],
            ['user_id' => (int) $structure->user_id, 'source' => 'api']
        );

        $history = PayrollStructureHistory::query()
            ->with('changedBy:id,name')
            ->where('user_id', $user->id)
            ->latest('changed_at')
            ->limit(20)
            ->get()
            ->map(fn (PayrollStructureHistory $entry): array => [
                'id' => $entry->id,
                'changedAt' => $entry->changed_at?->toIso8601String(),
                'changedBy' => $entry->changedBy?->name ?? 'System',
                'changeSummary' => $entry->change_summary ?? [],
            ])
            ->values()
            ->all();

        return response()->json([
            'message' => $structure->wasRecentlyCreated ? 'Salary structure created successfully.' : 'Salary structure updated successfully.',
            'structure' => [
                'id' => $structure->id,
                'userId' => $structure->user_id,
                'userName' => $structure->user?->name ?? 'Unknown',
                'userEmail' => $structure->user?->email ?? '',
                'department' => $structure->user?->profile?->department ?? '',
                'effectiveFrom' => $structure->effective_from?->format('Y-m-d'),
                'updatedAt' => $structure->updated_at?->toIso8601String(),
                'basicSalary' => (float) $structure->basic_salary,
                'hra' => (float) $structure->hra,
                'specialAllowance' => (float) $structure->special_allowance,
                'bonus' => (float) $structure->bonus,
                'otherAllowance' => (float) $structure->other_allowance,
                'pfDeduction' => (float) $structure->pf_deduction,
                'taxDeduction' => (float) $structure->tax_deduction,
                'otherDeduction' => (float) $structure->other_deduction,
                'grossConfigured' => (float) $structure->basic_salary
                    + (float) $structure->hra
                    + (float) $structure->special_allowance
                    + (float) $structure->bonus
                    + (float) $structure->other_allowance,
                'notes' => $structure->notes,
            ],
            'history' => $history,
        ]);
    }

    public function structureHistoryApi(Request $request, User $user): JsonResponse
    {
        $this->ensureManagementAccess($request);

        if (! $user->isEmployeeRecord()) {
            abort(404);
        }

        $history = PayrollStructureHistory::query()
            ->with('changedBy:id,name')
            ->where('user_id', $user->id)
            ->latest('changed_at')
            ->limit(30)
            ->get()
            ->map(fn (PayrollStructureHistory $entry): array => [
                'id' => $entry->id,
                'changedAt' => $entry->changed_at?->toIso8601String(),
                'changedBy' => $entry->changedBy?->name ?? 'System',
                'changeSummary' => $entry->change_summary ?? [],
            ])
            ->values()
            ->all();

        return response()->json([
            'history' => $history,
        ]);
    }

    public function bulkDirectoryAction(Request $request): JsonResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'mark_paid', 'delete'])],
            'payroll_ids' => ['required', 'array', 'min:1', 'max:500'],
            'payroll_ids.*' => ['integer', Rule::exists('payrolls', 'id')],
            'payment_method' => ['nullable', Rule::in(Payroll::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'override_lock' => ['nullable', 'boolean'],
        ]);

        $action = (string) $validated['action'];
        $overrideLock = (bool) ($validated['override_lock'] ?? false);
        if ($action === 'approve') {
            $this->assertCanApprove($viewer);
        } elseif ($action === 'mark_paid') {
            $this->assertCanMarkPaid($viewer);
            if (blank($validated['payment_method'] ?? null)) {
                return response()->json([
                    'message' => 'Payment method is required for mark paid action.',
                ], 422);
            }
        } else {
            $this->assertCanGenerate($viewer);
        }

        $ids = collect((array) $validated['payroll_ids'])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $payrolls = Payroll::query()
            ->with('user')
            ->whereIn('id', $ids)
            ->get();

        $updated = 0;
        $skipped = 0;
        $deleted = 0;

        DB::transaction(function () use (
            $payrolls,
            $action,
            $viewer,
            $overrideLock,
            $validated,
            &$updated,
            &$skipped,
            &$deleted
        ): void {
            foreach ($payrolls as $payroll) {
                if (! $payroll->user?->isEmployeeRecord()) {
                    $skipped++;
                    continue;
                }

                if (
                    $this->activeMonthLock($payroll->payroll_month?->copy()->startOfMonth() ?? now()->startOfMonth()) !== null
                    && ! $viewer->hasRole(UserRole::SUPER_ADMIN->value)
                ) {
                    $skipped++;
                    continue;
                }

                if (
                    $payroll->status === Payroll::STATUS_PAID
                    && ! ($overrideLock && $viewer->hasRole(UserRole::ADMIN->value))
                ) {
                    $skipped++;
                    continue;
                }

                $beforeValues = $this->workflowPayrollPayload($payroll);

                if ($action === 'approve') {
                    if (! in_array($payroll->status, [Payroll::STATUS_DRAFT, Payroll::STATUS_FAILED], true)) {
                        $skipped++;
                        continue;
                    }

                    $payroll->status = Payroll::STATUS_PROCESSED;
                    $payroll->approved_by_user_id = $viewer->id;
                    $payroll->approved_at = now();
                    $payroll->save();
                    $updated++;

                    $this->logPayrollAudit(
                        $viewer,
                        'payroll',
                        (int) $payroll->id,
                        'payroll.bulk.approve',
                        $beforeValues,
                        $this->workflowPayrollPayload($payroll),
                        ['bulk' => true]
                    );

                    continue;
                }

                if ($action === 'mark_paid') {
                    if ($payroll->status !== Payroll::STATUS_PROCESSED) {
                        $skipped++;
                        continue;
                    }

                    $payroll->status = Payroll::STATUS_PAID;
                    $payroll->payment_method = (string) $validated['payment_method'];
                    $payroll->payment_reference = blank($validated['payment_reference'] ?? null)
                        ? null
                        : (string) $validated['payment_reference'];
                    $payroll->notes = blank($validated['notes'] ?? null)
                        ? null
                        : (string) $validated['notes'];
                    $payroll->paid_by_user_id = $viewer->id;
                    $payroll->paid_at = now();
                    $payroll->save();
                    $updated++;

                    $this->logPayrollAudit(
                        $viewer,
                        'payroll',
                        (int) $payroll->id,
                        'payroll.bulk.mark_paid',
                        $beforeValues,
                        $this->workflowPayrollPayload($payroll),
                        ['bulk' => true]
                    );

                    continue;
                }

                if (! in_array($payroll->status, [Payroll::STATUS_DRAFT, Payroll::STATUS_FAILED], true)) {
                    $skipped++;
                    continue;
                }

                $this->logPayrollAudit(
                    $viewer,
                    'payroll',
                    (int) $payroll->id,
                    'payroll.bulk.delete',
                    $beforeValues,
                    null,
                    ['bulk' => true]
                );

                $payroll->delete();
                $deleted++;
            }
        });

        return response()->json([
            'message' => 'Bulk action completed.',
            'summary' => [
                'action' => $action,
                'updated' => $updated,
                'deleted' => $deleted,
                'skipped' => $skipped,
            ],
        ]);
    }

    public function exportDirectoryCsv(Request $request): StreamedResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $statusFilter = (string) $request->string('status');
        $search = (string) $request->string('q');
        $monthFilter = (string) $request->string('payroll_month');
        $monthStart = $this->resolveMonthOrCurrent($monthFilter);

        $query = Payroll::query()
            ->with('user.profile')
            ->whereHas('user', function (Builder $builder): void {
                $builder->workforce();
            })
            ->whereYear('payroll_month', $monthStart->year)
            ->whereMonth('payroll_month', $monthStart->month)
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $innerBuilder) use ($search): void {
                    $innerBuilder
                        ->where('payment_reference', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userBuilder) use ($search): void {
                            $userBuilder
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            });

        $dbStatus = $this->uiStatusToDbStatus($statusFilter);
        if ($dbStatus !== null) {
            $query->where('status', $dbStatus);
        }

        $filename = 'payroll-directory-' . $monthStart->format('Y-m') . '.csv';

        $this->logPayrollAudit(
            $viewer,
            'payroll',
            null,
            'payroll.directory.export_csv',
            null,
            null,
            ['month' => $monthStart->format('Y-m'), 'status' => $statusFilter, 'search' => $search]
        );

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            if (! is_resource($handle)) {
                return;
            }

            fputcsv($handle, [
                'Month',
                'Employee',
                'Email',
                'Department',
                'Status',
                'Working Days',
                'Payable Days',
                'Gross',
                'Deductions',
                'Net',
                'Payment Method',
                'Payment Reference',
                'Paid At',
            ]);

            $query->orderByDesc('payroll_month')
                ->orderBy('user_id')
                ->chunk(500, function ($records) use ($handle): void {
                    foreach ($records as $record) {
                        fputcsv($handle, [
                            $record->payroll_month?->format('Y-m') ?? '',
                            $record->user?->name ?? '',
                            $record->user?->email ?? '',
                            $record->user?->profile?->department ?? '',
                            $this->statusLabelFromDb((string) $record->status),
                            (float) $record->working_days,
                            (float) $record->payable_days,
                            (float) $record->gross_earnings,
                            (float) $record->total_deductions,
                            (float) $record->net_salary,
                            $record->payment_method ?? '',
                            $record->payment_reference ?? '',
                            $record->paid_at?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function generateBulk(Request $request): RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);
        $this->assertCanGenerate($viewer);
        $validated = $this->validateGeneratePayload($request, true);

        $monthStart = $this->resolveMonth((string) $validated['payroll_month']);

        $employees = User::query()
            ->workforce()
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

        if (! $payroll->user?->isEmployeeRecord()) {
            abort(404);
        }

        if (
            $this->activeMonthLock($payroll->payroll_month?->copy()->startOfMonth() ?? now()->startOfMonth()) !== null
            && ! $viewer->hasRole(UserRole::SUPER_ADMIN->value)
        ) {
            return redirect()
                ->route('modules.payroll.index')
                ->with('error', 'Payroll month is locked and cannot be modified.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(Payroll::statuses())],
            'payment_method' => ['nullable', Rule::in(Payroll::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'override_lock' => ['nullable', 'boolean'],
        ]);

        $overrideLock = (bool) ($validated['override_lock'] ?? false);
        if ($payroll->status === Payroll::STATUS_PAID && ! ($overrideLock && $viewer->hasRole(UserRole::ADMIN->value))) {
            return redirect()
                ->route('modules.payroll.index')
                ->with('error', 'Paid payroll is locked and cannot be modified.');
        }

        if ($validated['status'] === Payroll::STATUS_PROCESSED) {
            $this->assertCanApprove($viewer);
        }

        if ($validated['status'] === Payroll::STATUS_PAID) {
            $this->assertCanMarkPaid($viewer);
        }

        if (
            $validated['status'] === Payroll::STATUS_PAID
            && $payroll->status !== Payroll::STATUS_PROCESSED
        ) {
            return redirect()
                ->route('modules.payroll.index')
                ->with('error', 'Payroll must be approved before marking as paid.');
        }

        if (
            $validated['status'] === Payroll::STATUS_PAID
            && blank($validated['payment_method'] ?? null)
        ) {
            return redirect()
                ->route('modules.payroll.index')
                ->withErrors(['payment_method' => 'Please select a payment method before marking payroll as paid.'])
                ->withInput();
        }

        $beforeValues = $this->workflowPayrollPayload($payroll);

        $payroll->status = $validated['status'];
        $payroll->notes = blank($validated['notes'] ?? null) ? null : $validated['notes'];

        if ($validated['status'] === Payroll::STATUS_PAID) {
            $payroll->payment_method = $validated['payment_method'];
            $payroll->payment_reference = blank($validated['payment_reference'] ?? null)
                ? null
                : $validated['payment_reference'];
            $payroll->approved_by_user_id = $payroll->approved_by_user_id ?: $viewer->id;
            $payroll->approved_at = $payroll->approved_at ?: now();
            $payroll->paid_by_user_id = $viewer->id;
            $payroll->paid_at = now();
        } elseif ($validated['status'] === Payroll::STATUS_PROCESSED) {
            $payroll->approved_by_user_id = $viewer->id;
            $payroll->approved_at = now();
            $payroll->paid_by_user_id = null;
            $payroll->paid_at = null;
            $payroll->payment_method = null;
            $payroll->payment_reference = null;
        } else {
            $payroll->approved_by_user_id = null;
            $payroll->approved_at = null;
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

        $this->logPayrollAudit(
            $viewer,
            'payroll',
            (int) $payroll->id,
            'payroll.status.update',
            $beforeValues,
            $this->workflowPayrollPayload($payroll),
            ['status' => (string) $payroll->status, 'override_lock' => $overrideLock]
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
        $viewer = $this->ensureManagementAccess($request);
        $search = (string) $request->string('q');
        $status = (string) $request->string('status');
        $employeeId = (int) $request->integer('employee_id');
        $monthFilter = (string) $request->string('payroll_month');

        $monthStart = $this->resolveMonthOrCurrent($monthFilter);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $statusOptions = Payroll::statuses();
        $records = Payroll::query()
            ->with(['user.profile', 'generator', 'approvedBy', 'paidBy'])
            ->whereHas('user', function (Builder $query): void {
                $query->workforce();
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
            ->workforce()
            ->pluck('id');

        $monthBaseQuery = Payroll::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('payroll_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $employees = $this->employeeOptions();
        $structures = PayrollStructure::query()
            ->with('user.profile')
            ->whereIn('user_id', $employeeIds)
            ->orderByDesc('updated_at')
            ->get();

        $totalEmployees = (int) $employeeIds->count();
        $employeesWithStructure = (int) PayrollStructure::query()->whereIn('user_id', $employeeIds)->count();
        $generatedThisMonth = (int) (clone $monthBaseQuery)->count();
        $paidThisMonth = (int) (clone $monthBaseQuery)->where('status', Payroll::STATUS_PAID)->count();
        $draftThisMonth = (int) (clone $monthBaseQuery)->where('status', Payroll::STATUS_DRAFT)->count();
        $processedThisMonth = (int) (clone $monthBaseQuery)->where('status', Payroll::STATUS_PROCESSED)->count();
        $failedThisMonth = (int) (clone $monthBaseQuery)->where('status', Payroll::STATUS_FAILED)->count();
        $pendingThisMonth = (int) (clone $monthBaseQuery)->where('status', '!=', Payroll::STATUS_PAID)->count();
        $netThisMonth = (float) ((clone $monthBaseQuery)->sum('net_salary'));

        $stats = [
            'totalEmployees' => $totalEmployees,
            'employeesWithStructure' => $employeesWithStructure,
            'generatedThisMonth' => $generatedThisMonth,
            'paidThisMonth' => $paidThisMonth,
            'pendingThisMonth' => $pendingThisMonth,
            'draftThisMonth' => $draftThisMonth,
            'processedThisMonth' => $processedThisMonth,
            'failedThisMonth' => $failedThisMonth,
            'netThisMonth' => $netThisMonth,
        ];

        $notGeneratedCount = max(0, $employeesWithStructure - $generatedThisMonth);
        $missingBankDetailsCount = User::query()
            ->workforce()
            ->whereHas('payrolls', function (Builder $builder) use ($monthStart): void {
                $builder
                    ->whereYear('payroll_month', $monthStart->year)
                    ->whereMonth('payroll_month', $monthStart->month);
            })
            ->whereHas('profile', function (Builder $builder): void {
                $builder
                    ->where(function (Builder $innerBuilder): void {
                        $innerBuilder
                            ->whereNull('bank_account_name')
                            ->orWhere('bank_account_name', '')
                            ->orWhereNull('bank_account_number')
                            ->orWhere('bank_account_number', '')
                            ->orWhereNull('bank_ifsc')
                            ->orWhere('bank_ifsc', '');
                    });
            })
            ->count();

        $alerts = [
            'missing_structure' => max(0, $totalEmployees - $employeesWithStructure),
            'not_generated' => $notGeneratedCount,
            'pending_approvals' => $draftThisMonth,
            'calculation_errors' => $failedThisMonth,
            'missing_bank_details' => (int) $missingBankDetailsCount,
        ];

        $filters = [
            'q' => $search,
            'status' => $status,
            'employee_id' => $employeeId > 0 ? (string) $employeeId : '',
            'payroll_month' => $monthStart->format('Y-m'),
        ];

        $recordsPayload = $records->getCollection()
            ->tap(function ($collection): void {
                $collection->loadMissing(['user.profile', 'generator', 'approvedBy', 'paidBy']);
            });

        $auditTrailByPayrollId = AuditLog::query()
            ->with('performedBy:id,name')
            ->where('entity_type', 'payroll')
            ->whereIn('entity_id', $recordsPayload->pluck('id')->all())
            ->orderByDesc('performed_at')
            ->get()
            ->groupBy('entity_id')
            ->map(function ($items): array {
                return $items->take(5)->map(function (AuditLog $log): array {
                    return [
                        'id' => $log->id,
                        'action' => (string) $log->action,
                        'performedAt' => $log->performed_at?->toIso8601String(),
                        'performedByUserId' => $log->performed_by_user_id,
                        'performedByName' => $log->performedBy?->name ?? 'System',
                    ];
                })->values()->all();
            });

        $recordsPayload = $recordsPayload
            ->map(function (Payroll $record) use ($auditTrailByPayrollId): array {
                $user = $record->user;
                $profile = $user?->profile;

                return [
                    'id' => $record->id,
                    'payrollMonth' => $record->payroll_month?->format('Y-m-d'),
                    'payrollMonthLabel' => $record->payroll_month?->format('M Y') ?? 'N/A',
                    'user' => [
                        'id' => $user?->id,
                        'name' => $user?->name ?? 'Unknown',
                        'employeeCode' => $user instanceof User
                            ? ($user->profile?->employee_code ?: User::makeEmployeeCode($user->id))
                            : null,
                        'email' => $user?->email ?? '',
                        'department' => $profile?->department ?? '',
                    ],
                    'workingDays' => (float) $record->working_days,
                    'payableDays' => (float) $record->payable_days,
                    'lopDays' => (float) $record->lop_days,
                    'grossEarnings' => (float) $record->gross_earnings,
                    'totalDeductions' => (float) $record->total_deductions,
                    'netSalary' => (float) $record->net_salary,
                    'status' => (string) $record->status,
                    'uiStatus' => $this->dbStatusToUiStatus((string) $record->status),
                    'statusLabel' => $this->statusLabelFromDb((string) $record->status),
                    'locked' => $record->status === Payroll::STATUS_PAID,
                    'generatorName' => $record->generator?->name ?? 'System',
                    'approvedByName' => $record->approvedBy?->name,
                    'approvedAt' => $record->approved_at?->toIso8601String(),
                    'paymentMethod' => $record->payment_method,
                    'paymentReference' => $record->payment_reference,
                    'paidAt' => $record->paid_at?->toIso8601String(),
                    'notes' => $record->notes,
                    'statusUpdateUrl' => route('modules.payroll.status.update', $record),
                    'auditTrail' => $auditTrailByPayrollId->get($record->id, []),
                ];
            })
            ->values()
            ->all();

        $employeesPayload = $employees
            ->map(function (User $employee): array {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employeeCode' => (string) ($employee->profile?->employee_code ?: User::makeEmployeeCode($employee->id)),
                    'email' => $employee->email,
                    'department' => $employee->profile?->department ?? '',
                    'branch' => $employee->profile?->branch ?? '',
                    'hasStructure' => $employee->payrollStructure !== null,
                    'hasBankDetails' => filled($employee->profile?->bank_account_name)
                        && filled($employee->profile?->bank_account_number)
                        && filled($employee->profile?->bank_ifsc),
                ];
            })
            ->values()
            ->all();

        $structuresPayload = $structures
            ->map(function (PayrollStructure $structure): array {
                $grossConfigured = (float) $structure->basic_salary
                    + (float) $structure->hra
                    + (float) $structure->special_allowance
                    + (float) $structure->bonus
                    + (float) $structure->other_allowance;

                return [
                    'id' => $structure->id,
                    'userId' => $structure->user_id,
                    'userName' => $structure->user?->name ?? 'Unknown',
                    'userEmail' => $structure->user?->email ?? '',
                    'department' => $structure->user?->profile?->department ?? '',
                    'effectiveFrom' => $structure->effective_from?->format('Y-m-d'),
                    'updatedAt' => $structure->updated_at?->toIso8601String(),
                    'grossConfigured' => $grossConfigured,
                    'basicSalary' => (float) $structure->basic_salary,
                    'hra' => (float) $structure->hra,
                    'specialAllowance' => (float) $structure->special_allowance,
                    'bonus' => (float) $structure->bonus,
                    'otherAllowance' => (float) $structure->other_allowance,
                    'pfDeduction' => (float) $structure->pf_deduction,
                    'taxDeduction' => (float) $structure->tax_deduction,
                    'otherDeduction' => (float) $structure->other_deduction,
                    'notes' => $structure->notes,
                ];
            })
            ->values()
            ->all();

        $managementPayload = [
            'generatedAt' => now()->toIso8601String(),
            'monthLabel' => $monthStart->format('M Y'),
            'stats' => $stats,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'paymentMethodOptions' => Payroll::paymentMethods(),
            'alerts' => $alerts,
            'employees' => $employeesPayload,
            'records' => $recordsPayload,
            'structures' => $structuresPayload,
            'pagination' => [
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'total' => $records->total(),
                'currentPage' => $records->currentPage(),
                'lastPage' => $records->lastPage(),
                'prevPageUrl' => $records->previousPageUrl(),
                'nextPageUrl' => $records->nextPageUrl(),
            ],
            'urls' => [
                'index' => route('modules.payroll.index'),
                'structureStore' => route('modules.payroll.structure.store'),
                'generate' => route('modules.payroll.generate'),
                'generateBulk' => route('modules.payroll.generate-bulk'),
                'workflowPreview' => route('modules.payroll.workflow.preview'),
                'workflowGenerate' => route('modules.payroll.workflow.generate'),
                'workflowOverview' => route('modules.payroll.workflow.overview'),
                'workflowPreviewBatch' => route('modules.payroll.workflow.preview-batch'),
                'workflowGenerateBatch' => route('modules.payroll.workflow.generate-batch'),
                'workflowApproveBatch' => route('modules.payroll.workflow.approve-batch'),
                'workflowPayClose' => route('modules.payroll.workflow.pay-close'),
                'workflowUnlock' => route('modules.payroll.workflow.unlock'),
                'employeeSearch' => route('api.employees.search'),
                'structureUpsert' => route('modules.payroll.structure.upsert', ['user' => '__USER_ID__']),
                'structureHistory' => route('modules.payroll.structure.history', ['user' => '__USER_ID__']),
                'directoryBulkAction' => route('modules.payroll.directory.bulk-action'),
                'directoryExportCsv' => route('modules.payroll.directory.export-csv'),
            ],
            'permissions' => [
                'canGenerate' => $this->canGenerate($viewer),
                'canApprove' => $this->canApprove($viewer),
                'canMarkPaid' => $this->canMarkPaid($viewer),
                'canUnlock' => $this->canUnlock($viewer),
                'isAdmin' => $viewer->hasAnyRole([UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value]),
            ],
        ];

        return view('modules.payroll.admin', [
            'managementPayload' => $managementPayload,
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

        if ($this->activeMonthLock($monthStart) !== null && ! $viewer->hasRole(UserRole::SUPER_ADMIN->value)) {
            throw new DomainException("Payroll month {$monthStart->format('M Y')} is locked.");
        }

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
            'approved_by_user_id' => null,
            'approved_at' => null,
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
    private function workflowPayrollPayload(Payroll $payroll): array
    {
        $payroll->loadMissing(['user.profile', 'generator', 'approvedBy', 'paidBy']);

        return [
            'id' => $payroll->id,
            'status' => (string) $payroll->status,
            'uiStatus' => $this->dbStatusToUiStatus((string) $payroll->status),
            'statusLabel' => (string) str((string) $payroll->status)->replace('_', ' ')->title(),
            'locked' => $payroll->status === Payroll::STATUS_PAID,
            'payrollMonth' => $payroll->payroll_month?->format('Y-m-d'),
            'payrollMonthLabel' => $payroll->payroll_month?->format('M Y'),
            'workingDays' => (float) $payroll->working_days,
            'payableDays' => (float) $payroll->payable_days,
            'lopDays' => (float) $payroll->lop_days,
            'grossEarnings' => (float) $payroll->gross_earnings,
            'totalDeductions' => (float) $payroll->total_deductions,
            'netSalary' => (float) $payroll->net_salary,
            'paymentMethod' => $payroll->payment_method,
            'paymentReference' => $payroll->payment_reference,
            'paidAt' => $payroll->paid_at?->toIso8601String(),
            'approvedAt' => $payroll->approved_at?->toIso8601String(),
            'approvedByName' => $payroll->approvedBy?->name,
            'notes' => $payroll->notes,
            'user' => [
                'id' => $payroll->user?->id,
                'name' => $payroll->user?->name ?? 'Unknown',
                'email' => $payroll->user?->email ?? '',
                'department' => $payroll->user?->profile?->department ?? '',
            ],
            'approveUrl' => route('modules.payroll.workflow.approve', $payroll),
            'payUrl' => route('modules.payroll.workflow.pay', $payroll),
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{structure: PayrollStructure, beforeValues: ?array<string, mixed>, afterValues: ?array<string, mixed>}
     */
    private function saveStructureWithHistory(User $viewer, array $validated): array
    {
        $structure = PayrollStructure::query()->firstOrNew([
            'user_id' => (int) $validated['user_id'],
        ]);

        $beforeValues = $structure->exists ? [
            'basic_salary' => (float) $structure->basic_salary,
            'hra' => (float) $structure->hra,
            'special_allowance' => (float) $structure->special_allowance,
            'bonus' => (float) $structure->bonus,
            'other_allowance' => (float) $structure->other_allowance,
            'pf_deduction' => (float) $structure->pf_deduction,
            'tax_deduction' => (float) $structure->tax_deduction,
            'other_deduction' => (float) $structure->other_deduction,
            'effective_from' => $structure->effective_from?->format('Y-m-d'),
            'notes' => $structure->notes,
        ] : null;

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

        $afterValues = [
            'basic_salary' => (float) $structure->basic_salary,
            'hra' => (float) $structure->hra,
            'special_allowance' => (float) $structure->special_allowance,
            'bonus' => (float) $structure->bonus,
            'other_allowance' => (float) $structure->other_allowance,
            'pf_deduction' => (float) $structure->pf_deduction,
            'tax_deduction' => (float) $structure->tax_deduction,
            'other_deduction' => (float) $structure->other_deduction,
            'effective_from' => $structure->effective_from?->format('Y-m-d'),
            'notes' => $structure->notes,
        ];

        $changeSummary = $this->diffKeyValueSummary($beforeValues ?? [], $afterValues);
        if ($changeSummary !== [] || $beforeValues === null) {
            PayrollStructureHistory::query()->create([
                'payroll_structure_id' => $structure->id,
                'user_id' => $structure->user_id,
                'changed_by_user_id' => $viewer->id,
                'before_values' => $beforeValues,
                'after_values' => $afterValues,
                'change_summary' => $changeSummary,
                'changed_at' => now(),
            ]);
        }

        return [
            'structure' => $structure,
            'beforeValues' => $beforeValues,
            'afterValues' => $afterValues,
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<int, array{field: string, from: mixed, to: mixed}>
     */
    private function diffKeyValueSummary(array $before, array $after): array
    {
        $keys = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));
        $changes = [];

        foreach ($keys as $key) {
            $from = $before[$key] ?? null;
            $to = $after[$key] ?? null;

            if ((string) $from === (string) $to) {
                continue;
            }

            $changes[] = [
                'field' => (string) $key,
                'from' => $from,
                'to' => $to,
            ];
        }

        return $changes;
    }

    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     * @param array<string, mixed> $metadata
     */
    private function logPayrollAudit(
        User $viewer,
        string $entityType,
        ?int $entityId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $metadata = [],
    ): void {
        AuditLog::query()->create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'performed_by_user_id' => $viewer->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function workflowFilterRules(bool $monthRequired): array
    {
        $monthRule = $monthRequired ? 'required' : 'nullable';

        return [
            'payroll_month' => [$monthRule, 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'department' => ['nullable', 'string', 'max:120'],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{branch: ?string, department: ?string, employeeId: ?int}
     */
    private function resolveWorkflowFilters(array $validated): array
    {
        $branch = null;
        if (isset($validated['branch_id'])) {
            $branchName = Branch::query()->whereKey((int) $validated['branch_id'])->value('name');
            if (is_string($branchName) && trim($branchName) !== '') {
                $branch = trim($branchName);
            }
        }

        $department = null;
        if (isset($validated['department_id'])) {
            $departmentName = Department::query()->whereKey((int) $validated['department_id'])->value('name');
            if (is_string($departmentName) && trim($departmentName) !== '') {
                $department = trim($departmentName);
            }
        } elseif (filled($validated['department'] ?? null)) {
            $department = trim((string) $validated['department']);
        }

        return [
            'branch' => $branch,
            'department' => $department,
            'employeeId' => isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
        ];
    }

    private function workflowEmployeeQuery(?string $branch, ?string $department, ?int $employeeId): Builder
    {
        return User::query()
            ->workforce()
            ->when(filled($branch), function (Builder $query) use ($branch): void {
                $query->whereHas('profile', function (Builder $profileQuery) use ($branch): void {
                    $profileQuery->whereRaw('LOWER(TRIM(branch)) = ?', [mb_strtolower(trim((string) $branch))]);
                });
            })
            ->when(filled($department), function (Builder $query) use ($department): void {
                $query->whereHas('profile', function (Builder $profileQuery) use ($department): void {
                    $profileQuery->whereRaw('LOWER(TRIM(department)) = ?', [mb_strtolower(trim((string) $department))]);
                });
            })
            ->when(($employeeId ?? 0) > 0, function (Builder $query) use ($employeeId): void {
                $query->where('id', (int) $employeeId);
            })
            ->with(['profile', 'payrollStructure']);
    }

    private function workflowPayrollQuery(Carbon $monthStart, ?string $branch, ?string $department, ?int $employeeId): Builder
    {
        return Payroll::query()
            ->with(['user.profile', 'generator', 'approvedBy', 'paidBy'])
            ->whereYear('payroll_month', $monthStart->year)
            ->whereMonth('payroll_month', $monthStart->month)
            ->whereHas('user', function (Builder $query) use ($branch, $department, $employeeId): void {
                $query
                    ->workforce()
                    ->when(filled($branch), function (Builder $userQuery) use ($branch): void {
                        $userQuery->whereHas('profile', function (Builder $profileQuery) use ($branch): void {
                            $profileQuery->whereRaw('LOWER(TRIM(branch)) = ?', [mb_strtolower(trim((string) $branch))]);
                        });
                    })
                    ->when(filled($department), function (Builder $userQuery) use ($department): void {
                        $userQuery->whereHas('profile', function (Builder $profileQuery) use ($department): void {
                            $profileQuery->whereRaw('LOWER(TRIM(department)) = ?', [mb_strtolower(trim((string) $department))]);
                        });
                    })
                    ->when(($employeeId ?? 0) > 0, function (Builder $userQuery) use ($employeeId): void {
                        $userQuery->where('id', (int) $employeeId);
                    });
            });
    }

    private function activeMonthLock(Carbon $monthStart): ?PayrollMonthLock
    {
        return PayrollMonthLock::query()
            ->with('lockedBy:id,name')
            ->whereDate('payroll_month', $monthStart->toDateString())
            ->whereNull('unlocked_at')
            ->latest('locked_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWorkflowOverviewPayload(
        Carbon $monthStart,
        ?string $branch,
        ?string $department,
        ?int $employeeId,
        User $viewer
    ): array {
        $records = $this->workflowPayrollQuery($monthStart, $branch, $department, $employeeId)->get();
        $totalEmployees = $this->workflowEmployeeQuery($branch, $department, $employeeId)->count();
        $activeLock = $this->activeMonthLock($monthStart);

        $generatedCount = (int) $records->count();
        $approvedCount = (int) $records->filter(function (Payroll $payroll): bool {
            return in_array($payroll->status, [Payroll::STATUS_PROCESSED, Payroll::STATUS_PAID], true);
        })->count();
        $paidCount = (int) $records->where('status', Payroll::STATUS_PAID)->count();
        $failedCount = (int) $records->where('status', Payroll::STATUS_FAILED)->count();
        $draftCount = (int) $records->where('status', Payroll::STATUS_DRAFT)->count();
        $netTotal = (float) $records->sum('net_salary');

        $allApproved = $generatedCount > 0 && $approvedCount === $generatedCount;
        $allPaid = $generatedCount > 0 && $paidCount === $generatedCount;
        $isLocked = $activeLock !== null || $allPaid;

        $workflowStatus = $isLocked
            ? 'paid'
            : ($allApproved ? 'approved' : ($generatedCount > 0 ? 'generated' : 'draft'));

        $latestRecordUpdatedAt = $records->max('updated_at');
        $latestTimestamp = $activeLock?->locked_at;
        if ($latestRecordUpdatedAt instanceof Carbon) {
            $latestTimestamp = $latestTimestamp instanceof Carbon
                ? ($latestRecordUpdatedAt->greaterThan($latestTimestamp) ? $latestRecordUpdatedAt : $latestTimestamp)
                : $latestRecordUpdatedAt;
        }

        $rows = $records->map(function (Payroll $record) use ($isLocked): array {
            return [
                'id' => $record->id,
                'employeeId' => $record->user?->id,
                'employeeName' => $record->user?->name ?? 'Unknown',
                'department' => $record->user?->profile?->department ?? '',
                'gross' => (float) $record->gross_earnings,
                'deductions' => (float) $record->total_deductions,
                'net' => (float) $record->net_salary,
                'status' => (string) $record->status,
                'uiStatus' => $this->dbStatusToUiStatus((string) $record->status),
                'statusLabel' => $this->statusLabelFromDb((string) $record->status),
                'error' => $record->status === Payroll::STATUS_FAILED ? ($record->notes ?: 'Calculation error') : null,
                'locked' => $record->status === Payroll::STATUS_PAID || $isLocked,
                'generatedBy' => $record->generator?->name,
                'generatedAt' => $record->created_at?->toIso8601String(),
                'approvedBy' => $record->approvedBy?->name,
                'approvedAt' => $record->approved_at?->toIso8601String(),
                'paidBy' => $record->paidBy?->name,
                'paidAt' => $record->paid_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'month' => $monthStart->format('Y-m'),
            'header' => [
                'payrollMonth' => $monthStart->format('M Y'),
                'payrollMonthValue' => $monthStart->format('Y-m'),
                'status' => $workflowStatus,
                'statusLabel' => ucfirst($workflowStatus),
                'totalEmployees' => $totalEmployees,
                'totalNetPay' => round($netTotal, 2),
                'lastUpdatedAt' => $latestTimestamp?->toIso8601String(),
                'locked' => $isLocked,
                'lockedAt' => $activeLock?->locked_at?->toIso8601String(),
                'lockedBy' => $activeLock?->lockedBy?->name,
            ],
            'permissions' => [
                'canGenerate' => $this->canGenerate($viewer),
                'canApprove' => $this->canApprove($viewer),
                'canMarkPaid' => $this->canMarkPaid($viewer),
                'canUnlock' => $this->canUnlock($viewer),
            ],
            'steps' => [
                'step1' => ! $isLocked,
                'step2' => ! $isLocked,
                'step3' => $generatedCount > 0 && ! $isLocked,
                'step4' => $allApproved && ! $isLocked,
            ],
            'summary' => [
                'generatedCount' => $generatedCount,
                'approvedCount' => $approvedCount,
                'paidCount' => $paidCount,
                'failedCount' => $failedCount,
                'draftCount' => $draftCount,
            ],
            'records' => $rows,
        ];
    }

    private function canGenerate(User $viewer): bool
    {
        return $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::HR->value,
            UserRole::ADMIN->value,
        ]);
    }

    private function canApprove(User $viewer): bool
    {
        return $viewer->hasAnyRole([UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value]);
    }

    private function canMarkPaid(User $viewer): bool
    {
        return $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::FINANCE->value,
            UserRole::ADMIN->value,
        ]);
    }

    private function canUnlock(User $viewer): bool
    {
        return $viewer->hasRole(UserRole::SUPER_ADMIN->value);
    }

    private function assertCanGenerate(User $viewer): void
    {
        if (! $this->canGenerate($viewer)) {
            abort(403, 'Only HR Manager, Admin, or Super Admin can generate payroll.');
        }
    }

    private function assertCanApprove(User $viewer): void
    {
        if (! $this->canApprove($viewer)) {
            abort(403, 'Only Admin or Super Admin can approve payroll.');
        }
    }

    private function assertCanMarkPaid(User $viewer): void
    {
        if (! $this->canMarkPaid($viewer)) {
            abort(403, 'Only Finance, Admin, or Super Admin can mark payroll as paid.');
        }
    }

    private function assertCanUnlock(User $viewer): void
    {
        if (! $this->canUnlock($viewer)) {
            abort(403, 'Only Super Admin can unlock paid payroll months.');
        }
    }

    private function dbStatusToUiStatus(string $dbStatus): string
    {
        return match ($dbStatus) {
            Payroll::STATUS_DRAFT => 'generated',
            Payroll::STATUS_PROCESSED => 'approved',
            Payroll::STATUS_PAID => 'paid',
            Payroll::STATUS_FAILED => 'failed',
            default => 'generated',
        };
    }

    private function uiStatusToDbStatus(?string $uiStatus): ?string
    {
        $status = strtolower((string) ($uiStatus ?? ''));

        return match ($status) {
            'generated' => Payroll::STATUS_DRAFT,
            'approved' => Payroll::STATUS_PROCESSED,
            'paid' => Payroll::STATUS_PAID,
            'failed' => Payroll::STATUS_FAILED,
            default => null,
        };
    }

    private function statusLabelFromDb(string $dbStatus): string
    {
        return match ($dbStatus) {
            Payroll::STATUS_DRAFT => 'Generated',
            Payroll::STATUS_PROCESSED => 'Approved',
            Payroll::STATUS_PAID => 'Paid',
            Payroll::STATUS_FAILED => 'Failed',
            default => (string) str($dbStatus)->replace('_', ' ')->title(),
        };
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
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
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
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ];
            $rules['payable_days'] = ['nullable', 'numeric', 'min:0'];
        }

        return $request->validate($rules);
    }

    private function ensureManagementAccess(Request $request): User
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || ! $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
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
            ->workforce()
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
