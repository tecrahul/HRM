<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollMonthLock;
use App\Models\PayrollStructure;
use App\Models\User;
use App\Support\PayrollWorkflow;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PayrollModuleController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        return $this->workspaceView('dashboard', 'Payroll Dashboard', $viewer, $request);
    }

    public function salaryStructures(Request $request): View|RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        return $this->workspaceView('salary_structures', 'Salary Structures', $viewer, $request);
    }

    public function processing(Request $request): View|RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        return $this->workspaceView('processing', 'Payroll Processing', $viewer, $request);
    }

    public function history(Request $request): View|RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        return $this->workspaceView('history', 'Payroll History', $viewer, $request);
    }

    public function payslips(Request $request): View|RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        return $this->workspaceView('payslips', 'Payslips', $viewer, $request);
    }

    public function reports(Request $request): View|RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        return $this->workspaceView('reports', 'Payroll Reports', $viewer, $request);
    }

    public function settings(Request $request): View|RedirectResponse
    {
        $viewer = $this->ensureManagementAccess($request);

        return $this->workspaceView('settings', 'Payroll Settings', $viewer, $request);
    }

    public function branchesApi(Request $request): JsonResponse
    {
        $this->ensureManagementAccess($request);

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'location'])
            ->map(static fn (Branch $branch): array => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'code' => (string) ($branch->code ?? ''),
                'location' => (string) ($branch->location ?? ''),
            ])
            ->values()
            ->all();

        return response()->json([
            'branches' => $branches,
        ]);
    }

    public function departmentsApi(Request $request): JsonResponse
    {
        $this->ensureManagementAccess($request);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
        ]);

        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        $departments = Department::query()
            ->where('is_active', true)
            ->when($branchId !== null, function (Builder $query) use ($branchId): void {
                $query->where(function (Builder $nested) use ($branchId): void {
                    $nested->where('branch_id', $branchId)->orWhereNull('branch_id');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'branch_id'])
            ->map(static fn (Department $department): array => [
                'id' => (int) $department->id,
                'name' => (string) $department->name,
                'code' => (string) ($department->code ?? ''),
                'branch_id' => $department->branch_id !== null ? (int) $department->branch_id : null,
            ])
            ->values()
            ->all();

        return response()->json([
            'departments' => $departments,
        ]);
    }

    public function dashboardSummaryApi(Request $request): JsonResponse
    {
        $this->ensureManagementAccess($request);
        $filters = $this->validatedFilterPayload($request);

        $monthStart = $this->resolveMonthOrCurrent((string) ($filters['payroll_month'] ?? ''));
        $monthEnd = $monthStart->copy()->endOfMonth();

        $employeeQuery = $this->employeeQuery(
            $filters['branch_name'],
            $filters['department_name'],
            $filters['employee_id'],
            null,
        );

        $employeeIds = (clone $employeeQuery)->pluck('users.id');
        $totalEmployees = (int) $employeeIds->count();

        $structuresCount = PayrollStructure::query()
            ->whereIn('user_id', $employeeIds)
            ->count();

        $payrollMonthQuery = Payroll::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('payroll_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $generatedCount = (int) (clone $payrollMonthQuery)->count();
        $approvedCount = (int) (clone $payrollMonthQuery)
            ->whereIn('status', [Payroll::STATUS_PROCESSED, Payroll::STATUS_PAID])
            ->count();
        $paidCount = (int) (clone $payrollMonthQuery)
            ->where('status', Payroll::STATUS_PAID)
            ->count();
        $pendingApprovalsCount = (int) (clone $payrollMonthQuery)
            ->whereIn('status', [Payroll::STATUS_DRAFT, Payroll::STATUS_FAILED])
            ->count();

        $pendingLeaveApprovals = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();

        $netTotal = (float) (clone $payrollMonthQuery)->sum('net_salary');
        $lastProcessedMonth = Payroll::query()
            ->whereIn('user_id', $employeeIds)
            ->max('payroll_month');

        $status = 'draft';
        if ($generatedCount > 0 && $paidCount === $generatedCount) {
            $status = 'paid';
        } elseif ($generatedCount > 0 && $approvedCount === $generatedCount) {
            $status = 'approved';
        } elseif ($generatedCount > 0) {
            $status = 'generated';
        }

        $progressPercent = $generatedCount > 0
            ? min(100, (int) round(($paidCount / max($generatedCount, 1)) * 100))
            : 0;

        $activeLock = PayrollMonthLock::query()
            ->whereDate('payroll_month', $monthStart->toDateString())
            ->whereNull('unlocked_at')
            ->latest('locked_at')
            ->first();

        return response()->json([
            'summary' => [
                'totalEmployees' => $totalEmployees,
                'missingSalaryStructure' => max(0, $totalEmployees - (int) $structuresCount),
                'currentMonthPayrollStatus' => ucfirst($status),
                'pendingApprovals' => $pendingApprovalsCount + (int) $pendingLeaveApprovals,
                'totalNetPayroll' => round($netTotal, 2),
                'lastProcessedMonth' => $lastProcessedMonth
                    ? Carbon::parse((string) $lastProcessedMonth)->format('M Y')
                    : 'N/A',
            ],
            'currentMonthStatus' => [
                'monthLabel' => $monthStart->format('M Y'),
                'status' => $status,
                'statusLabel' => ucfirst($status),
                'generatedCount' => $generatedCount,
                'approvedCount' => $approvedCount,
                'paidCount' => $paidCount,
                'progressPercent' => $progressPercent,
                'locked' => $activeLock !== null,
            ],
        ]);
    }

    public function dashboardAlertsApi(Request $request): JsonResponse
    {
        $this->ensureManagementAccess($request);
        $filters = $this->validatedFilterPayload($request);

        $monthStart = $this->resolveMonthOrCurrent((string) ($filters['payroll_month'] ?? ''));
        $monthEnd = $monthStart->copy()->endOfMonth();

        $employeeIds = $this->employeeQuery(
            $filters['branch_name'],
            $filters['department_name'],
            $filters['employee_id'],
            null,
        )->pluck('users.id');

        $totalEmployees = (int) $employeeIds->count();
        $withStructureCount = (int) PayrollStructure::query()->whereIn('user_id', $employeeIds)->count();

        $monthQuery = Payroll::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('payroll_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $generatedCount = (int) (clone $monthQuery)->count();
        $pendingApprovals = (int) (clone $monthQuery)->whereIn('status', [Payroll::STATUS_DRAFT, Payroll::STATUS_FAILED])->count();
        $errors = (int) (clone $monthQuery)->where('status', Payroll::STATUS_FAILED)->count();

        $missingBankDetails = User::query()
            ->whereIn('id', $employeeIds)
            ->whereHas('profile', function (Builder $profileQuery): void {
                $profileQuery
                    ->where(function (Builder $query): void {
                        $query
                            ->whereNull('bank_account_name')
                            ->orWhere('bank_account_name', '')
                            ->orWhereNull('bank_account_number')
                            ->orWhere('bank_account_number', '')
                            ->orWhereNull('bank_ifsc')
                            ->orWhere('bank_ifsc', '');
                    });
            })
            ->count();

        $payslipsNotGenerated = (int) (clone $monthQuery)
            ->where('status', Payroll::STATUS_PAID)
            ->where(function (Builder $query): void {
                $query->whereNull('payment_reference')->orWhere('payment_reference', '');
            })
            ->count();

        $alerts = [
            [
                'key' => 'missing_salary_structure',
                'label' => 'Missing salary structures',
                'count' => max(0, $totalEmployees - $withStructureCount),
                'target' => route('modules.payroll.salary-structures', ['status' => 'missing_structure']),
                'tone' => 'danger',
            ],
            [
                'key' => 'payroll_not_generated',
                'label' => 'Payroll not generated',
                'count' => max(0, $withStructureCount - $generatedCount),
                'target' => route('modules.payroll.processing', ['alert' => 'not_generated']),
                'tone' => 'warning',
            ],
            [
                'key' => 'pending_approvals',
                'label' => 'Pending approvals',
                'count' => $pendingApprovals,
                'target' => route('modules.payroll.processing', ['alert' => 'pending_approvals']),
                'tone' => 'warning',
            ],
            [
                'key' => 'payroll_errors',
                'label' => 'Payroll errors',
                'count' => $errors,
                'target' => route('modules.payroll.processing', ['alert' => 'failed']),
                'tone' => 'danger',
            ],
            [
                'key' => 'payslips_not_generated',
                'label' => 'Payslips not generated',
                'count' => $payslipsNotGenerated,
                'target' => route('modules.payroll.payslips', ['status' => 'missing_reference']),
                'tone' => 'info',
            ],
            [
                'key' => 'missing_bank_details',
                'label' => 'Missing bank details',
                'count' => (int) $missingBankDetails,
                'target' => route('modules.payroll.salary-structures', ['status' => 'missing_bank']),
                'tone' => 'danger',
            ],
        ];

        return response()->json([
            'alerts' => $alerts,
        ]);
    }

    public function dashboardActivityApi(Request $request): JsonResponse
    {
        $this->ensureManagementAccess($request);

        $activity = AuditLog::query()
            ->with('performedBy:id,name')
            ->whereIn('entity_type', ['payroll', 'payroll_structure', 'payroll_month'])
            ->orderByDesc('performed_at')
            ->limit(20)
            ->get()
            ->map(static function (AuditLog $log): array {
                return [
                    'id' => (int) $log->id,
                    'action' => (string) $log->action,
                    'actionLabel' => str((string) $log->action)->replace('.', ' ')->headline()->toString(),
                    'entityType' => (string) $log->entity_type,
                    'performedBy' => $log->performedBy?->name ?? 'System',
                    'performedAt' => $log->performed_at?->toIso8601String(),
                    'metadata' => $log->metadata ?? [],
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'activity' => $activity,
        ]);
    }

    public function salaryStructuresApi(Request $request): JsonResponse
    {
        $this->ensureManagementAccess($request);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'with_structure', 'missing_structure', 'missing_bank'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $branchName = $this->resolveBranchName(isset($validated['branch_id']) ? (int) $validated['branch_id'] : null);
        $departmentName = $this->resolveDepartmentName(isset($validated['department_id']) ? (int) $validated['department_id'] : null);
        $employeeId = isset($validated['employee_id']) ? (int) $validated['employee_id'] : null;
        $keyword = trim((string) ($validated['q'] ?? ''));
        $status = (string) ($validated['status'] ?? 'all');
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 15;

        $employeeBase = $this->employeeQuery($branchName, $departmentName, $employeeId, $keyword);

        $totalEmployees = (int) (clone $employeeBase)->count();
        $withStructure = (int) (clone $employeeBase)->whereHas('payrollStructure')->count();

        $grossAverage = (float) PayrollStructure::query()
            ->whereIn('user_id', (clone $employeeBase)->pluck('users.id'))
            ->selectRaw('AVG(basic_salary + hra + special_allowance + bonus + other_allowance) as avg_gross')
            ->value('avg_gross');

        $tableQuery = (clone $employeeBase)
            ->with(['profile', 'payrollStructure'])
            ->when($status === 'with_structure', function (Builder $query): void {
                $query->whereHas('payrollStructure');
            })
            ->when($status === 'missing_structure', function (Builder $query): void {
                $query->whereDoesntHave('payrollStructure');
            })
            ->when($status === 'missing_bank', function (Builder $query): void {
                $query->whereHas('profile', function (Builder $profileQuery): void {
                    $profileQuery->where(function (Builder $inner): void {
                        $inner
                            ->whereNull('bank_account_name')
                            ->orWhere('bank_account_name', '')
                            ->orWhereNull('bank_account_number')
                            ->orWhere('bank_account_number', '')
                            ->orWhereNull('bank_ifsc')
                            ->orWhere('bank_ifsc', '');
                    });
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $rows = $tableQuery->getCollection()
            ->map(static function (User $employee): array {
                $structure = $employee->payrollStructure;
                $gross = $structure
                    ? (float) $structure->basic_salary
                        + (float) $structure->hra
                        + (float) $structure->special_allowance
                        + (float) $structure->bonus
                        + (float) $structure->other_allowance
                    : 0.0;

                return [
                    'employeeId' => (int) $employee->id,
                    'employeeName' => (string) $employee->name,
                    'employeeCode' => (string) ($employee->profile?->employee_code ?: User::makeEmployeeCode($employee->id)),
                    'email' => (string) $employee->email,
                    'department' => (string) ($employee->profile?->department ?? ''),
                    'branch' => (string) ($employee->profile?->branch ?? ''),
                    'grossSalary' => round($gross, 2),
                    'effectiveFrom' => $structure?->effective_from?->format('Y-m-d'),
                    'lastUpdated' => $structure?->updated_at?->toIso8601String(),
                    'status' => $structure ? 'with_structure' : 'missing_structure',
                    'statusLabel' => $structure ? 'Configured' : 'Missing',
                    'structure' => $structure ? [
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
                    ] : null,
                ];
            })
            ->values()
            ->all();

        $grossTotal = (float) collect($rows)->sum('grossSalary');

        return response()->json([
            'summary' => [
                'totalEmployees' => $totalEmployees,
                'withStructure' => $withStructure,
                'missingStructure' => max(0, $totalEmployees - $withStructure),
                'averageGrossSalary' => round($grossAverage, 2),
            ],
            'rows' => $rows,
            'totals' => [
                'gross' => round($grossTotal, 2),
            ],
            'pagination' => [
                'currentPage' => $tableQuery->currentPage(),
                'lastPage' => $tableQuery->lastPage(),
                'perPage' => $tableQuery->perPage(),
                'total' => $tableQuery->total(),
            ],
        ]);
    }

    public function payrollHistoryApi(Request $request): JsonResponse
    {
        $this->ensureManagementAccess($request);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['', 'generated', 'approved', 'paid', 'failed'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $branchName = $this->resolveBranchName(isset($validated['branch_id']) ? (int) $validated['branch_id'] : null);
        $departmentName = $this->resolveDepartmentName(isset($validated['department_id']) ? (int) $validated['department_id'] : null);
        $employeeId = isset($validated['employee_id']) ? (int) $validated['employee_id'] : null;
        $keyword = trim((string) ($validated['q'] ?? ''));
        $status = (string) ($validated['status'] ?? '');
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 12;
        $dbStatus = $this->uiStatusToDbStatus($status);

        $historyQuery = DB::table('payrolls')
            ->join('users', 'users.id', '=', 'payrolls.user_id')
            ->leftJoin('user_profiles as profile', 'profile.user_id', '=', 'users.id')
            ->leftJoin('users as generators', 'generators.id', '=', 'payrolls.generated_by_user_id')
            ->leftJoin('users as approvers', 'approvers.id', '=', 'payrolls.approved_by_user_id')
            ->leftJoin('users as payers', 'payers.id', '=', 'payrolls.paid_by_user_id')
            ->where('profile.is_employee', true)
            ->when($branchName !== null, function ($query) use ($branchName): void {
                $this->applyQueryStringMatch($query, 'profile.branch', $branchName);
            })
            ->when($departmentName !== null, function ($query) use ($departmentName): void {
                $this->applyQueryStringMatch($query, 'profile.department', $departmentName);
            })
            ->when($employeeId !== null, function ($query) use ($employeeId): void {
                $query->where('users.id', $employeeId);
            })
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($inner) use ($keyword): void {
                    $inner
                        ->where('users.name', 'like', '%' . $keyword . '%')
                        ->orWhere('users.email', 'like', '%' . $keyword . '%')
                        ->orWhere('payrolls.payment_reference', 'like', '%' . $keyword . '%');
                });
            })
            ->when($dbStatus !== null, function ($query) use ($dbStatus): void {
                $query->where('payrolls.status', $dbStatus);
            });

        $grouped = (clone $historyQuery)
            ->selectRaw('DATE_FORMAT(payrolls.payroll_month, "%Y-%m-01") as month_key')
            ->selectRaw('COUNT(*) as total_employees')
            ->selectRaw('SUM(payrolls.gross_earnings) as gross_total')
            ->selectRaw('SUM(payrolls.total_deductions) as deduction_total')
            ->selectRaw('SUM(payrolls.net_salary) as net_total')
            ->selectRaw('SUM(CASE WHEN payrolls.status = ? THEN 1 ELSE 0 END) as paid_count', [Payroll::STATUS_PAID])
            ->selectRaw('SUM(CASE WHEN payrolls.status IN (?, ?) THEN 1 ELSE 0 END) as approved_count', [Payroll::STATUS_PROCESSED, Payroll::STATUS_PAID])
            ->selectRaw('SUM(CASE WHEN payrolls.status = ? THEN 1 ELSE 0 END) as failed_count', [Payroll::STATUS_FAILED])
            ->selectRaw('MAX(COALESCE(payers.name, approvers.name, generators.name, "System")) as processed_by')
            ->selectRaw('MAX(payrolls.updated_at) as processed_at')
            ->groupBy('month_key')
            ->orderByDesc('month_key')
            ->paginate($perPage)
            ->withQueryString();

        $rows = collect($grouped->items())
            ->map(function ($row): array {
                $totalEmployees = (int) ($row->total_employees ?? 0);
                $paidCount = (int) ($row->paid_count ?? 0);
                $approvedCount = (int) ($row->approved_count ?? 0);
                $failedCount = (int) ($row->failed_count ?? 0);

                $status = 'Generated';
                if ($totalEmployees > 0 && $paidCount === $totalEmployees) {
                    $status = 'Paid';
                } elseif ($totalEmployees > 0 && $approvedCount === $totalEmployees) {
                    $status = 'Approved';
                } elseif ($failedCount > 0) {
                    $status = 'Failed';
                }

                return [
                    'month' => (string) ($row->month_key ?? ''),
                    'monthLabel' => $row->month_key
                        ? Carbon::parse((string) $row->month_key)->format('M Y')
                        : 'N/A',
                    'totalEmployees' => $totalEmployees,
                    'gross' => round((float) ($row->gross_total ?? 0), 2),
                    'deductions' => round((float) ($row->deduction_total ?? 0), 2),
                    'net' => round((float) ($row->net_total ?? 0), 2),
                    'status' => $status,
                    'processedBy' => (string) ($row->processed_by ?? 'System'),
                    'processedAt' => $row->processed_at
                        ? Carbon::parse((string) $row->processed_at)->toIso8601String()
                        : null,
                ];
            })
            ->values()
            ->all();

        $summaryRows = (clone $historyQuery)
            ->selectRaw('COUNT(DISTINCT DATE_FORMAT(payrolls.payroll_month, "%Y-%m")) as total_months')
            ->selectRaw('MAX(payrolls.payroll_month) as last_month')
            ->first();

        $ytdPayrollCost = (float) (clone $historyQuery)
            ->whereYear('payrolls.payroll_month', now()->year)
            ->sum('payrolls.net_salary');

        $totals = [
            'gross' => round((float) collect($rows)->sum('gross'), 2),
            'deductions' => round((float) collect($rows)->sum('deductions'), 2),
            'net' => round((float) collect($rows)->sum('net'), 2),
        ];

        return response()->json([
            'summary' => [
                'totalMonthsProcessed' => (int) ($summaryRows?->total_months ?? 0),
                'ytdPayrollCost' => round($ytdPayrollCost, 2),
                'lastProcessedMonth' => $summaryRows?->last_month
                    ? Carbon::parse((string) $summaryRows->last_month)->format('M Y')
                    : 'N/A',
            ],
            'rows' => $rows,
            'totals' => $totals,
            'pagination' => [
                'currentPage' => $grouped->currentPage(),
                'lastPage' => $grouped->lastPage(),
                'perPage' => $grouped->perPage(),
                'total' => $grouped->total(),
            ],
        ]);
    }

    private function workspaceView(string $pageKey, string $heading, User $viewer, Request $request): View
    {
        $payload = [
            'page' => $pageKey,
            'heading' => $heading,
            'generatedAt' => now()->toIso8601String(),
            'filters' => [
                'branch_id' => (string) $request->string('branch_id'),
                'department_id' => (string) $request->string('department_id'),
                'employee_id' => (string) $request->string('employee_id'),
                'payroll_month' => (string) ($request->string('payroll_month') ?: now()->format('Y-m')),
                'q' => (string) $request->string('q'),
                'status' => (string) $request->string('status'),
                'alert' => (string) $request->string('alert'),
            ],
            'permissions' => [
                'canGenerate' => $this->canGenerate($viewer),
                'canApprove' => $this->canApprove($viewer),
                'canMarkPaid' => $this->canMarkPaid($viewer),
                'canUnlock' => PayrollWorkflow::canUnlock($viewer),
                'role' => $viewer->role instanceof UserRole ? $viewer->role->value : (string) $viewer->role,
            ],
            'urls' => [
                'branches' => route('api.branches.list'),
                'departments' => route('api.departments.list'),
                'employeeSearch' => route('api.employees.search'),
                'dashboardSummary' => route('api.payroll.dashboard.summary'),
                'dashboardAlerts' => route('api.payroll.dashboard.alerts'),
                'dashboardActivity' => route('api.payroll.dashboard.activity'),
                'salaryStructures' => route('api.payroll.salary-structures'),
                'payrollHistory' => route('api.payroll.history'),
                'workflowOverview' => route('modules.payroll.workflow.overview'),
                'workflowPreviewBatch' => route('modules.payroll.workflow.preview-batch'),
                'workflowGenerateBatch' => route('modules.payroll.workflow.generate-batch'),
                'workflowApproveBatch' => route('modules.payroll.workflow.approve-batch'),
                'workflowPayClose' => route('modules.payroll.workflow.pay-close'),
                'workflowUnlock' => route('modules.payroll.workflow.unlock'),
                'structureUpsert' => route('modules.payroll.structure.upsert', ['user' => '__USER_ID__']),
                'structureHistory' => route('modules.payroll.structure.history', ['user' => '__USER_ID__']),
                'directoryBulkAction' => route('modules.payroll.directory.bulk-action'),
                'directoryExportCsv' => route('modules.payroll.directory.export-csv'),
            ],
            'routes' => [
                'dashboard' => route('modules.payroll.dashboard'),
                'salaryStructures' => route('modules.payroll.salary-structures'),
                'processing' => route('modules.payroll.processing'),
                'history' => route('modules.payroll.history'),
                'payslips' => route('modules.payroll.payslips'),
                'reports' => route('modules.payroll.reports'),
                'settings' => route('modules.payroll.settings'),
            ],
        ];

        return view('modules.payroll.workspace', [
            'payrollWorkspacePayload' => $payload,
            'pageHeading' => $heading,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilterPayload(Request $request): array
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('user_profiles', 'user_id')->where(function ($query): void {
                    $query->where('is_employee', true);
                }),
            ],
            'payroll_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $departmentId = isset($validated['department_id']) ? (int) $validated['department_id'] : null;

        return [
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'branch_name' => $this->resolveBranchName($branchId),
            'department_name' => $this->resolveDepartmentName($departmentId),
            'employee_id' => isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            'payroll_month' => (string) ($validated['payroll_month'] ?? ''),
            'q' => trim((string) ($validated['q'] ?? '')),
        ];
    }

    private function employeeQuery(?string $branchName, ?string $departmentName, ?int $employeeId, ?string $search): Builder
    {
        return User::query()
            ->workforce()
            ->when($employeeId !== null, function (Builder $query) use ($employeeId): void {
                $query->where('users.id', $employeeId);
            })
            ->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('users.name', 'like', '%' . $search . '%')
                        ->orWhere('users.email', 'like', '%' . $search . '%');
                });
            })
            ->when($branchName !== null, function (Builder $query) use ($branchName): void {
                $query->whereHas('profile', function (Builder $profileQuery) use ($branchName): void {
                    $this->applyProfileStringMatch($profileQuery, 'branch', $branchName);
                });
            })
            ->when($departmentName !== null, function (Builder $query) use ($departmentName): void {
                $query->whereHas('profile', function (Builder $profileQuery) use ($departmentName): void {
                    $this->applyProfileStringMatch($profileQuery, 'department', $departmentName);
                });
            });
    }

    private function applyProfileStringMatch(Builder $profileQuery, string $field, string $value): void
    {
        $profileQuery->whereRaw('LOWER(TRIM(' . $field . ')) = ?', [mb_strtolower(trim($value))]);
    }

    private function applyQueryStringMatch($query, string $column, string $value): void
    {
        $query->whereRaw('LOWER(TRIM(' . $column . ')) = ?', [mb_strtolower(trim($value))]);
    }

    private function resolveBranchName(?int $branchId): ?string
    {
        if (($branchId ?? 0) <= 0) {
            return null;
        }

        $name = Branch::query()->whereKey($branchId)->value('name');

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    private function resolveDepartmentName(?int $departmentId): ?string
    {
        if (($departmentId ?? 0) <= 0) {
            return null;
        }

        $name = Department::query()->whereKey($departmentId)->value('name');

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    private function resolveMonthOrCurrent(string $month): Carbon
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }

    private function uiStatusToDbStatus(?string $uiStatus): ?string
    {
        return PayrollWorkflow::uiStatusToDbStatus($uiStatus);
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

    private function canGenerate(User $viewer): bool
    {
        return PayrollWorkflow::canGenerate($viewer);
    }

    private function canApprove(User $viewer): bool
    {
        return PayrollWorkflow::canApprove($viewer);
    }

    private function canMarkPaid(User $viewer): bool
    {
        return PayrollWorkflow::canMarkPaid($viewer);
    }
}
