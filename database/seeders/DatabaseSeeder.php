<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollMonthLock;
use App\Models\PayrollStructure;
use App\Models\PayrollStructureHistory;
use App\Enums\UserRole;
use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $defaultPassword = 'Password@123';
            $today = now()->startOfDay();
            $currentMonth = now()->startOfMonth();
            $previousMonth = now()->copy()->subMonth()->startOfMonth();
            $twoMonthsAgo = now()->copy()->subMonths(2)->startOfMonth();

            $this->seedCompanySettings();
            $roles = $this->seedSystemUsers($defaultPassword);
            $this->seedBranchesAndDepartments();
            $employees = $this->seedEmployees($defaultPassword, $roles);
            $structures = $this->seedPayrollStructures($employees, $roles['hr'], $today);
            $payrollMap = $this->seedPayrolls($employees, $structures, $roles, $currentMonth, $previousMonth, $twoMonthsAgo);

            $this->seedMonthLocks($roles, $previousMonth, $twoMonthsAgo);
            $this->seedLeaveRequests($employees, $roles['hr']);
            $this->seedAttendance($employees, $roles['hr'], $today);
            $this->seedActivities($roles, $employees, $currentMonth);
            $this->seedAuditLogs($roles, $structures, $payrollMap, $previousMonth, $currentMonth);
        });
    }

    private function seedCompanySettings(): void
    {
        CompanySetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'HRM Demo Corp',
                'company_code' => 'HRM-DEMO',
                'company_email' => 'hello@hrm.test',
                'company_phone' => '+1-555-0100',
                'company_website' => 'https://hrm.test',
                'tax_id' => '99-1234567',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'financial_year_start_month' => 4,
                'company_address' => '121 Enterprise Ave, New York, NY',
                'signup_enabled' => true,
                'password_reset_enabled' => true,
                'two_factor_enabled' => true,
            ]
        );
    }

    /**
     * @return array{super_admin: User, admin: User, hr: User, finance: User}
     */
    private function seedSystemUsers(string $defaultPassword): array
    {
        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@hrm.test'],
            [
                'name' => 'Super Admin',
                'role' => UserRole::SUPER_ADMIN->value,
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@hrm.test'],
            [
                'name' => 'System Admin',
                'role' => UserRole::ADMIN->value,
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        );

        $hr = User::query()->updateOrCreate(
            ['email' => 'hr@hrm.test'],
            [
                'name' => 'HR Manager',
                'role' => UserRole::HR->value,
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        );

        $finance = User::query()->updateOrCreate(
            ['email' => 'finance@hrm.test'],
            [
                'name' => 'Finance Controller',
                'role' => UserRole::FINANCE->value,
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        );

        UserProfile::query()->updateOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'is_employee' => false,
                'employee_code' => null,
                'department' => 'Administration',
                'branch' => 'Headquarters',
                'job_title' => 'Platform Owner',
                'status' => 'active',
                'employment_type' => 'full_time',
                'joined_on' => now()->subYears(5)->toDateString(),
                'manager_name' => 'Board',
            ]
        );

        UserProfile::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'is_employee' => false,
                'employee_code' => null,
                'department' => 'Administration',
                'branch' => 'Headquarters',
                'job_title' => 'System Administrator',
                'status' => 'active',
                'employment_type' => 'full_time',
                'joined_on' => now()->subYears(3)->toDateString(),
                'manager_name' => $superAdmin->name,
            ]
        );

        UserProfile::query()->updateOrCreate(
            ['user_id' => $hr->id],
            [
                'is_employee' => true,
                'employee_code' => User::makeEmployeeCode($hr->id),
                'department' => 'Human Resources',
                'branch' => 'Headquarters',
                'job_title' => 'HR Manager',
                'status' => 'active',
                'employment_type' => 'full_time',
                'joined_on' => now()->subYears(2)->toDateString(),
                'manager_name' => $admin->name,
            ]
        );

        UserProfile::query()->updateOrCreate(
            ['user_id' => $finance->id],
            [
                'is_employee' => true,
                'employee_code' => User::makeEmployeeCode($finance->id),
                'department' => 'Finance',
                'branch' => 'Headquarters',
                'job_title' => 'Finance Controller',
                'status' => 'active',
                'employment_type' => 'full_time',
                'joined_on' => now()->subYears(2)->toDateString(),
                'manager_name' => $admin->name,
            ]
        );

        return [
            'super_admin' => $superAdmin,
            'admin' => $admin,
            'hr' => $hr,
            'finance' => $finance,
        ];
    }

    /**
     * @return array{branches: Collection<int, Branch>, departments: Collection<int, Department>}
     */
    private function seedBranchesAndDepartments(): array
    {
        $branches = collect([
            ['name' => 'Headquarters', 'code' => 'HQ', 'location' => 'New York'],
            ['name' => 'West Coast Office', 'code' => 'WCO', 'location' => 'San Francisco'],
            ['name' => 'South Operations', 'code' => 'SOP', 'location' => 'Austin'],
        ])->map(function (array $item): Branch {
            return Branch::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'code' => $item['code'],
                    'location' => $item['location'],
                    'description' => $item['name'].' branch',
                    'is_active' => true,
                ]
            );
        });

        $departments = collect([
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Sales', 'code' => 'SAL'],
        ])->map(function (array $item): Department {
            return Department::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'code' => $item['code'],
                    'description' => $item['name'].' department',
                    'is_active' => true,
                ]
            );
        });

        return [
            'branches' => $branches,
            'departments' => $departments,
        ];
    }

    /**
     * @param array{admin: User, hr: User} $roles
     * @return Collection<int, array{user: User, has_structure: bool, has_bank: bool, base_salary: float}>
     */
    private function seedEmployees(string $defaultPassword, array $roles): Collection
    {
        $employeeBlueprint = collect([
            ['name' => 'Test Employee', 'email' => 'employee@hrm.test', 'department' => 'Operations', 'branch' => 'Headquarters', 'title' => 'Operations Analyst', 'has_structure' => true, 'has_bank' => true, 'base_salary' => 42000],
            ['name' => 'Rahul Kumar', 'email' => 'rahul@hrm.test', 'department' => 'Engineering', 'branch' => 'Headquarters', 'title' => 'Senior Engineer', 'has_structure' => true, 'has_bank' => true, 'base_salary' => 76000],
            ['name' => 'Anita Sharma', 'email' => 'anita@hrm.test', 'department' => 'Human Resources', 'branch' => 'Headquarters', 'title' => 'HR Executive', 'has_structure' => true, 'has_bank' => true, 'base_salary' => 51000],
            ['name' => 'Vikram Singh', 'email' => 'vikram@hrm.test', 'department' => 'Finance', 'branch' => 'Headquarters', 'title' => 'Accounts Executive', 'has_structure' => true, 'has_bank' => true, 'base_salary' => 56000],
            ['name' => 'Meera Iyer', 'email' => 'meera@hrm.test', 'department' => 'Engineering', 'branch' => 'West Coast Office', 'title' => 'Product Engineer', 'has_structure' => true, 'has_bank' => false, 'base_salary' => 68000],
            ['name' => 'Sanjay Nair', 'email' => 'sanjay@hrm.test', 'department' => 'Operations', 'branch' => 'South Operations', 'title' => 'Operations Lead', 'has_structure' => true, 'has_bank' => true, 'base_salary' => 59000],
            ['name' => 'Priya Patel', 'email' => 'priya@hrm.test', 'department' => 'Sales', 'branch' => 'West Coast Office', 'title' => 'Sales Executive', 'has_structure' => true, 'has_bank' => true, 'base_salary' => 54000],
            ['name' => 'Aditya Verma', 'email' => 'aditya@hrm.test', 'department' => 'Engineering', 'branch' => 'South Operations', 'title' => 'QA Engineer', 'has_structure' => true, 'has_bank' => true, 'base_salary' => 50000],
            ['name' => 'Nisha Reddy', 'email' => 'nisha@hrm.test', 'department' => 'Operations', 'branch' => 'Headquarters', 'title' => 'Support Analyst', 'has_structure' => false, 'has_bank' => true, 'base_salary' => 39000],
            ['name' => 'Arjun Mehta', 'email' => 'arjun@hrm.test', 'department' => 'Sales', 'branch' => 'South Operations', 'title' => 'Regional Sales Rep', 'has_structure' => true, 'has_bank' => false, 'base_salary' => 52000],
            ['name' => 'Kavya Menon', 'email' => 'kavya@hrm.test', 'department' => 'Finance', 'branch' => 'West Coast Office', 'title' => 'Payroll Specialist', 'has_structure' => false, 'has_bank' => true, 'base_salary' => 48000],
        ]);

        return $employeeBlueprint->map(function (array $item, int $index) use ($defaultPassword, $roles): array {
            $user = User::query()->updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'role' => UserRole::EMPLOYEE->value,
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]
            );

            $joinedOn = now()->copy()->subMonths(6 + $index)->startOfMonth()->addDays($index % 20);

            $bankDetails = $item['has_bank']
                ? [
                    'bank_account_name' => $item['name'],
                    'bank_account_number' => sprintf('1002003004%02d', $index + 1),
                    'bank_ifsc' => sprintf('DEMO000%03d', $index + 1),
                ]
                : [
                    'bank_account_name' => null,
                    'bank_account_number' => null,
                    'bank_ifsc' => null,
                ];

            UserProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge([
                    'is_employee' => true,
                    'employee_code' => User::makeEmployeeCode($user->id),
                    'department' => $item['department'],
                    'branch' => $item['branch'],
                    'job_title' => $item['title'],
                    'status' => 'active',
                    'employment_type' => 'full_time',
                    'joined_on' => $joinedOn->toDateString(),
                    'manager_name' => $roles['hr']->name,
                    'supervisor_user_id' => $roles['hr']->id,
                    'phone' => sprintf('+1-555-120-%04d', 1000 + $index),
                    'alternate_phone' => sprintf('+1-555-130-%04d', 1000 + $index),
                    'work_location' => $item['branch'],
                ], $bankDetails)
            );

            return [
                'user' => $user,
                'has_structure' => (bool) $item['has_structure'],
                'has_bank' => (bool) $item['has_bank'],
                'base_salary' => (float) $item['base_salary'],
            ];
        });
    }

    /**
     * @param Collection<int, array{user: User, has_structure: bool, has_bank: bool, base_salary: float}> $employees
     * @return array<int, PayrollStructure>
     */
    private function seedPayrollStructures(Collection $employees, User $hr, Carbon $today): array
    {
        $structureMap = [];

        foreach ($employees as $employee) {
            $user = $employee['user'];
            if (! $employee['has_structure']) {
                PayrollStructure::query()->where('user_id', $user->id)->delete();
                continue;
            }

            $base = (float) $employee['base_salary'];
            $structure = PayrollStructure::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'basic_salary' => round($base * 0.58, 2),
                    'hra' => round($base * 0.2, 2),
                    'special_allowance' => round($base * 0.13, 2),
                    'bonus' => round($base * 0.05, 2),
                    'other_allowance' => round($base * 0.04, 2),
                    'pf_deduction' => round($base * 0.03, 2),
                    'tax_deduction' => round($base * 0.05, 2),
                    'other_deduction' => round($base * 0.01, 2),
                    'effective_from' => $today->copy()->subMonths(3)->startOfMonth()->toDateString(),
                    'notes' => 'Demo structure seeded for payroll module walkthrough.',
                    'created_by_user_id' => $hr->id,
                    'updated_by_user_id' => $hr->id,
                ]
            );

            $beforeValues = [
                'basic_salary' => round($base * 0.56, 2),
                'hra' => round($base * 0.19, 2),
                'special_allowance' => round($base * 0.12, 2),
                'bonus' => round($base * 0.04, 2),
                'other_allowance' => round($base * 0.03, 2),
                'pf_deduction' => round($base * 0.028, 2),
                'tax_deduction' => round($base * 0.048, 2),
                'other_deduction' => round($base * 0.01, 2),
                'effective_from' => $today->copy()->subMonths(6)->startOfMonth()->toDateString(),
            ];

            $afterValues = [
                'basic_salary' => (float) $structure->basic_salary,
                'hra' => (float) $structure->hra,
                'special_allowance' => (float) $structure->special_allowance,
                'bonus' => (float) $structure->bonus,
                'other_allowance' => (float) $structure->other_allowance,
                'pf_deduction' => (float) $structure->pf_deduction,
                'tax_deduction' => (float) $structure->tax_deduction,
                'other_deduction' => (float) $structure->other_deduction,
                'effective_from' => $structure->effective_from?->toDateString(),
            ];

            PayrollStructureHistory::query()->updateOrCreate(
                [
                    'payroll_structure_id' => $structure->id,
                    'user_id' => $user->id,
                    'changed_at' => $today->copy()->subDays(18)->setTime(11, 0, 0),
                ],
                [
                    'changed_by_user_id' => $hr->id,
                    'before_values' => $beforeValues,
                    'after_values' => $afterValues,
                    'change_summary' => [
                        ['field' => 'basic_salary', 'from' => $beforeValues['basic_salary'], 'to' => $afterValues['basic_salary']],
                        ['field' => 'hra', 'from' => $beforeValues['hra'], 'to' => $afterValues['hra']],
                    ],
                ]
            );

            $structureMap[$user->id] = $structure;
        }

        return $structureMap;
    }

    /**
     * @param Collection<int, array{user: User, has_structure: bool, has_bank: bool, base_salary: float}> $employees
     * @param array<int, PayrollStructure> $structures
     * @param array{admin: User, hr: User, finance: User} $roles
     * @return array{current: Collection<int, Payroll>, previous: Collection<int, Payroll>, older: Collection<int, Payroll>}
     */
    private function seedPayrolls(
        Collection $employees,
        array $structures,
        array $roles,
        Carbon $currentMonth,
        Carbon $previousMonth,
        Carbon $twoMonthsAgo
    ): array {
        $currentPayrolls = collect();
        $previousPayrolls = collect();
        $olderPayrolls = collect();

        $statusCycle = [
            Payroll::STATUS_PAID,
            Payroll::STATUS_PROCESSED,
            Payroll::STATUS_DRAFT,
            Payroll::STATUS_FAILED,
            Payroll::STATUS_DRAFT,
            Payroll::STATUS_PROCESSED,
        ];

        $structuredEmployees = $employees->values()->filter(fn (array $employee): bool => $employee['has_structure'])->values();

        foreach ($structuredEmployees as $index => $employee) {
            $user = $employee['user'];
            /** @var PayrollStructure $structure */
            $structure = $structures[$user->id];
            $status = $statusCycle[$index % count($statusCycle)];

            $currentPayroll = Payroll::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'payroll_month' => $currentMonth->toDateString(),
                ],
                $this->payrollPayload(
                    $structure,
                    $status,
                    $roles['hr']->id,
                    $roles['admin']->id,
                    $roles['finance']->id,
                    'CURR-'.$currentMonth->format('Ym').'-'.$user->id,
                    $status === Payroll::STATUS_FAILED ? 'Calculation error: verify attendance snapshots.' : 'Current month seeded payroll.'
                )
            );
            $currentPayrolls->push($currentPayroll);

            $previousPayroll = Payroll::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'payroll_month' => $previousMonth->toDateString(),
                ],
                $this->payrollPayload(
                    $structure,
                    Payroll::STATUS_PAID,
                    $roles['hr']->id,
                    $roles['admin']->id,
                    $roles['finance']->id,
                    'PREV-'.$previousMonth->format('Ym').'-'.$user->id,
                    'Previous month payroll paid and closed.'
                )
            );
            $previousPayrolls->push($previousPayroll);

            $olderPayroll = Payroll::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'payroll_month' => $twoMonthsAgo->toDateString(),
                ],
                $this->payrollPayload(
                    $structure,
                    Payroll::STATUS_PAID,
                    $roles['hr']->id,
                    $roles['admin']->id,
                    $roles['finance']->id,
                    'HIST-'.$twoMonthsAgo->format('Ym').'-'.$user->id,
                    'Historical paid payroll record.'
                )
            );
            $olderPayrolls->push($olderPayroll);
        }

        return [
            'current' => $currentPayrolls,
            'previous' => $previousPayrolls,
            'older' => $olderPayrolls,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payrollPayload(
        PayrollStructure $structure,
        string $status,
        int $generatedById,
        int $approvedById,
        int $paidById,
        string $paymentReference,
        string $notes
    ): array {
        $workingDays = 22.0;
        $payableDays = $status === Payroll::STATUS_FAILED ? 20.0 : 22.0;
        $lopDays = max(0.0, $workingDays - $payableDays);
        $ratio = $workingDays > 0 ? ($payableDays / $workingDays) : 1;

        $basicPay = round((float) $structure->basic_salary * $ratio, 2);
        $hra = round((float) $structure->hra * $ratio, 2);
        $specialAllowance = round((float) $structure->special_allowance * $ratio, 2);
        $bonus = round((float) $structure->bonus * $ratio, 2);
        $otherAllowance = round((float) $structure->other_allowance * $ratio, 2);
        $gross = round($basicPay + $hra + $specialAllowance + $bonus + $otherAllowance, 2);

        $pf = round((float) $structure->pf_deduction, 2);
        $tax = round((float) $structure->tax_deduction, 2);
        $otherDeduction = round((float) $structure->other_deduction, 2);
        $deductions = round($pf + $tax + $otherDeduction, 2);
        $net = round(max(0, $gross - $deductions), 2);

        $approvedAt = in_array($status, [Payroll::STATUS_PROCESSED, Payroll::STATUS_PAID], true) ? now()->subDays(4) : null;
        $paidAt = $status === Payroll::STATUS_PAID ? now()->subDays(2) : null;

        return [
            'working_days' => $workingDays,
            'attendance_lop_days' => $lopDays,
            'unpaid_leave_days' => 0,
            'lop_days' => $lopDays,
            'payable_days' => $payableDays,
            'basic_pay' => $basicPay,
            'hra' => $hra,
            'special_allowance' => $specialAllowance,
            'bonus' => $bonus,
            'other_allowance' => $otherAllowance,
            'gross_earnings' => $gross,
            'pf_deduction' => $pf,
            'tax_deduction' => $tax,
            'other_deduction' => $otherDeduction,
            'total_deductions' => $deductions,
            'net_salary' => $net,
            'status' => $status,
            'notes' => $notes,
            'generated_by_user_id' => $generatedById,
            'approved_by_user_id' => $approvedAt ? $approvedById : null,
            'approved_at' => $approvedAt,
            'paid_by_user_id' => $paidAt ? $paidById : null,
            'paid_at' => $paidAt,
            'payment_method' => $paidAt ? Payroll::PAYMENT_BANK_TRANSFER : null,
            'payment_reference' => $paidAt ? $paymentReference : null,
        ];
    }

    /**
     * @param array{super_admin: User, finance: User} $roles
     */
    private function seedMonthLocks(array $roles, Carbon $previousMonth, Carbon $twoMonthsAgo): void
    {
        PayrollMonthLock::query()
            ->whereIn('payroll_month', [$previousMonth->toDateString(), $twoMonthsAgo->toDateString()])
            ->delete();

        PayrollMonthLock::query()->updateOrCreate(
            [
                'payroll_month' => $previousMonth->toDateString(),
                'unlocked_at' => null,
            ],
            [
                'locked_by_user_id' => $roles['finance']->id,
                'locked_at' => now()->subDays(8),
                'metadata' => [
                    'reason' => 'Monthly payout complete',
                    'source' => 'database_seeder',
                ],
            ]
        );

        PayrollMonthLock::query()->updateOrCreate(
            [
                'payroll_month' => $twoMonthsAgo->toDateString(),
                'unlock_reason' => 'Retroactive correction for compliance',
            ],
            [
                'locked_by_user_id' => $roles['finance']->id,
                'unlocked_by_user_id' => $roles['super_admin']->id,
                'unlocked_at' => now()->subDays(40),
                'unlock_reason' => 'Retroactive correction for compliance',
                'metadata' => [
                    'reason' => 'historical_adjustment',
                    'source' => 'database_seeder',
                ],
            ]
        );
    }

    /**
     * @param Collection<int, array{user: User, has_structure: bool, has_bank: bool, base_salary: float}> $employees
     */
    private function seedLeaveRequests(Collection $employees, User $hr): void
    {
        $pick = $employees->take(4)->values();

        foreach ($pick as $index => $employee) {
            $user = $employee['user'];
            $status = match ($index) {
                0, 1 => LeaveRequest::STATUS_PENDING,
                2 => LeaveRequest::STATUS_APPROVED,
                default => LeaveRequest::STATUS_REJECTED,
            };

            LeaveRequest::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'start_date' => now()->addDays(2 + $index)->toDateString(),
                    'end_date' => now()->addDays(2 + $index)->toDateString(),
                ],
                [
                    'leave_type' => $index % 2 === 0 ? LeaveRequest::TYPE_CASUAL : LeaveRequest::TYPE_SICK,
                    'day_type' => LeaveRequest::DAY_TYPE_FULL,
                    'total_days' => 1.0,
                    'reason' => 'Seeded leave request for dashboard approvals',
                    'status' => $status,
                    'reviewer_id' => $status === LeaveRequest::STATUS_PENDING ? null : $hr->id,
                    'reviewed_at' => $status === LeaveRequest::STATUS_PENDING ? null : now()->subDay(),
                    'review_note' => $status === LeaveRequest::STATUS_REJECTED ? 'Insufficient leave balance' : null,
                ]
            );
        }
    }

    /**
     * @param Collection<int, array{user: User, has_structure: bool, has_bank: bool, base_salary: float}> $employees
     */
    private function seedAttendance(Collection $employees, User $hr, Carbon $today): void
    {
        $statuses = [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_REMOTE,
            Attendance::STATUS_HALF_DAY,
            Attendance::STATUS_ABSENT,
            Attendance::STATUS_ON_LEAVE,
        ];

        foreach ($employees->values() as $index => $employee) {
            $user = $employee['user'];
            $status = $statuses[$index % count($statuses)];
            $checkIn = in_array($status, [Attendance::STATUS_PRESENT, Attendance::STATUS_REMOTE, Attendance::STATUS_HALF_DAY], true)
                ? $today->copy()->setTime(9, 15 + ($index % 20), 0)
                : null;
            $checkOut = $checkIn ? $checkIn->copy()->addHours($status === Attendance::STATUS_HALF_DAY ? 4 : 8) : null;

            Attendance::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'attendance_date' => $today->toDateString(),
                ],
                [
                    'status' => $status,
                    'check_in_at' => $checkIn,
                    'check_out_at' => $checkOut,
                    'work_minutes' => $checkIn ? ($status === Attendance::STATUS_HALF_DAY ? 240 : 480) : null,
                    'notes' => 'Seeded attendance sample',
                    'marked_by_user_id' => $hr->id,
                ]
            );
        }
    }

    /**
     * @param array{admin: User, hr: User, finance: User} $roles
     * @param Collection<int, array{user: User, has_structure: bool, has_bank: bool, base_salary: float}> $employees
     */
    private function seedActivities(array $roles, Collection $employees, Carbon $currentMonth): void
    {
        $subjectEmployee = $employees->first()['user'] ?? null;
        if (! $subjectEmployee instanceof User) {
            return;
        }

        Activity::query()->updateOrCreate(
            [
                'event_key' => 'payroll.generated',
                'meta' => $subjectEmployee->name.' • '.$currentMonth->format('M Y'),
            ],
            [
                'actor_user_id' => $roles['hr']->id,
                'title' => 'Payroll generated',
                'tone' => '#10b981',
                'subject_type' => User::class,
                'subject_id' => $subjectEmployee->id,
                'payload' => ['month' => $currentMonth->format('Y-m')],
                'occurred_at' => now()->subDays(3),
            ]
        );

        Activity::query()->updateOrCreate(
            [
                'event_key' => 'payroll.approved',
                'meta' => $subjectEmployee->name.' • '.$currentMonth->format('M Y'),
            ],
            [
                'actor_user_id' => $roles['admin']->id,
                'title' => 'Payroll approved',
                'tone' => '#3b82f6',
                'subject_type' => User::class,
                'subject_id' => $subjectEmployee->id,
                'payload' => ['month' => $currentMonth->format('Y-m')],
                'occurred_at' => now()->subDays(2),
            ]
        );

        Activity::query()->updateOrCreate(
            [
                'event_key' => 'payroll.paid',
                'meta' => $subjectEmployee->name.' • '.$currentMonth->format('M Y'),
            ],
            [
                'actor_user_id' => $roles['finance']->id,
                'title' => 'Payroll paid',
                'tone' => '#22c55e',
                'subject_type' => User::class,
                'subject_id' => $subjectEmployee->id,
                'payload' => ['month' => $currentMonth->format('Y-m')],
                'occurred_at' => now()->subDay(),
            ]
        );
    }

    /**
     * @param array{admin: User, hr: User, finance: User} $roles
     * @param array<int, PayrollStructure> $structures
     * @param array{current: Collection<int, Payroll>, previous: Collection<int, Payroll>, older: Collection<int, Payroll>} $payrollMap
     */
    private function seedAuditLogs(
        array $roles,
        array $structures,
        array $payrollMap,
        Carbon $previousMonth,
        Carbon $currentMonth
    ): void {
        foreach ($structures as $structure) {
            AuditLog::query()->updateOrCreate(
                [
                    'entity_type' => 'payroll_structure',
                    'entity_id' => $structure->id,
                    'action' => 'structure.update',
                ],
                [
                    'performed_by_user_id' => $roles['hr']->id,
                    'old_values' => null,
                    'new_values' => ['user_id' => $structure->user_id, 'effective_from' => $structure->effective_from?->toDateString()],
                    'metadata' => ['source' => 'database_seeder'],
                    'performed_at' => now()->subDays(18),
                ]
            );
        }

        foreach ($payrollMap['current'] as $payroll) {
            AuditLog::query()->updateOrCreate(
                [
                    'entity_type' => 'payroll',
                    'entity_id' => $payroll->id,
                    'action' => 'payroll.generate.created',
                ],
                [
                    'performed_by_user_id' => $roles['hr']->id,
                    'old_values' => null,
                    'new_values' => ['status' => $payroll->status, 'month' => $currentMonth->format('Y-m')],
                    'metadata' => ['source' => 'database_seeder'],
                    'performed_at' => now()->subDays(4),
                ]
            );

            if (in_array($payroll->status, [Payroll::STATUS_PROCESSED, Payroll::STATUS_PAID], true)) {
                AuditLog::query()->updateOrCreate(
                    [
                        'entity_type' => 'payroll',
                        'entity_id' => $payroll->id,
                        'action' => 'payroll.approve',
                    ],
                    [
                        'performed_by_user_id' => $roles['admin']->id,
                        'old_values' => ['status' => Payroll::STATUS_DRAFT],
                        'new_values' => ['status' => Payroll::STATUS_PROCESSED],
                        'metadata' => ['source' => 'database_seeder'],
                        'performed_at' => now()->subDays(3),
                    ]
                );
            }

            if ($payroll->status === Payroll::STATUS_PAID) {
                AuditLog::query()->updateOrCreate(
                    [
                        'entity_type' => 'payroll',
                        'entity_id' => $payroll->id,
                        'action' => 'payroll.mark_paid',
                    ],
                    [
                        'performed_by_user_id' => $roles['finance']->id,
                        'old_values' => ['status' => Payroll::STATUS_PROCESSED],
                        'new_values' => ['status' => Payroll::STATUS_PAID],
                        'metadata' => ['source' => 'database_seeder'],
                        'performed_at' => now()->subDays(2),
                    ]
                );
            }
        }

        AuditLog::query()->updateOrCreate(
            [
                'entity_type' => 'payroll_month',
                'entity_id' => null,
                'action' => 'payroll.workflow.month_locked',
            ],
            [
                'performed_by_user_id' => $roles['finance']->id,
                'old_values' => null,
                'new_values' => ['month' => $previousMonth->format('Y-m'), 'status' => 'locked'],
                'metadata' => ['source' => 'database_seeder'],
                'performed_at' => now()->subDays(8),
            ]
        );
    }
}
