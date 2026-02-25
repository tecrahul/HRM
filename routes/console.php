<?php

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollStructure;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled tasks
Schedule::command('communication:purge-trashed')->daily();

Artisan::command(
    'demo:data
    {--clean : Drop all tables and rerun migrations before generating demo data}
    {--clean-only : Drop all tables and rerun migrations, then stop}
    {--employees=30 : Number of demo employees to create/update}
    {--months=3 : Number of months for attendance/payroll history (1-12)}',
    function (): int {
        $clean = (bool) $this->option('clean');
        $cleanOnly = (bool) $this->option('clean-only');
        $employeeCount = max(1, (int) $this->option('employees'));
        $months = max(1, min(12, (int) $this->option('months')));

        if ($cleanOnly) {
            $clean = true;
        }

        if ($clean) {
            $this->warn('Cleaning database with migrate:fresh...');
            $this->call('migrate:fresh', ['--force' => true]);
            $this->info('Database cleanup completed.');
        }

        if ($cleanOnly) {
            return self::SUCCESS;
        }

        $this->warn('Running base seeder...');
        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $admin = User::query()->firstWhere('email', 'admin@hrm.test');
        $hr = User::query()->firstWhere('email', 'hr@hrm.test');

        if (! $admin || ! $hr) {
            $this->error('Base users missing after seeding. Ensure DatabaseSeeder creates admin@hrm.test and hr@hrm.test.');
            return self::FAILURE;
        }

        CompanySetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Demo HRM Inc.',
                'company_code' => 'DHRM',
                'company_email' => 'contact@demo-hrm.test',
                'company_phone' => '+1-555-0100',
                'company_website' => 'https://demo-hrm.test',
                'tax_id' => 'TAX-DEMO-001',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'financial_year_start_month' => 4,
                'company_address' => '100 Demo Avenue, New York, NY',
            ]
        );

        $departmentSeed = [
            ['name' => 'Administration', 'code' => 'ADMIN'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Engineering', 'code' => 'ENG'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Sales', 'code' => 'SAL'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Support', 'code' => 'SUP'],
        ];

        $departments = collect($departmentSeed)
            ->map(function (array $row): Department {
                return Department::query()->updateOrCreate(
                    ['name' => $row['name']],
                    [
                        'code' => $row['code'],
                        'description' => "{$row['name']} department",
                        'is_active' => true,
                    ]
                );
            })
            ->values();

        $branchSeed = [
            ['name' => 'New York HQ', 'code' => 'NYC', 'location' => 'New York'],
            ['name' => 'San Francisco', 'code' => 'SFO', 'location' => 'San Francisco'],
            ['name' => 'Chicago', 'code' => 'CHI', 'location' => 'Chicago'],
            ['name' => 'Austin', 'code' => 'AUS', 'location' => 'Austin'],
        ];

        $branches = collect($branchSeed)
            ->map(function (array $row): Branch {
                return Branch::query()->updateOrCreate(
                    ['name' => $row['name']],
                    [
                        'code' => $row['code'],
                        'location' => $row['location'],
                        'description' => "{$row['name']} branch",
                        'is_active' => true,
                    ]
                );
            })
            ->values();

        $managerUsers = [$admin, $hr];
        $employmentTypes = ['full_time', 'part_time', 'contract', 'intern'];
        $profileStatuses = ['active', 'active', 'active', 'inactive', 'suspended'];
        $defaultPassword = 'Password@123';

        for ($index = 1; $index <= $employeeCount; $index++) {
            $sequence = str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $department = $departments->get(($index - 1) % max(1, $departments->count()));
            $branch = $branches->get(($index - 1) % max(1, $branches->count()));
            $manager = $managerUsers[($index - 1) % count($managerUsers)];
            $employmentType = $employmentTypes[($index - 1) % count($employmentTypes)];
            $profileStatus = $profileStatuses[($index - 1) % count($profileStatuses)];

            $employee = User::query()->updateOrCreate(
                ['email' => "employee{$sequence}@demo.hrm.test"],
                [
                    'name' => "Employee {$sequence}",
                    'role' => UserRole::EMPLOYEE->value,
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]
            );

            $joinedOn = now()->subDays(45 + (($index * 13) % 900))->toDateString();
            $phoneSeed = str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT);

            UserProfile::query()->updateOrCreate(
                ['user_id' => $employee->id],
                [
                    'phone' => "+1-555-{$phoneSeed}",
                    'alternate_phone' => "+1-555-9{$phoneSeed}",
                    'department' => $department?->name,
                    'branch' => $branch?->name,
                    'job_title' => "{$department?->name} Associate",
                    'employment_type' => $employmentType,
                    'status' => $profileStatus,
                    'joined_on' => $joinedOn,
                    'work_location' => $branch?->name,
                    'manager_name' => $manager->name,
                    'nationality' => 'American',
                ]
            );
        }

        $employees = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->with('profile')
            ->orderBy('id')
            ->get();

        $attendanceStart = now()->copy()->subMonthsNoOverflow($months)->startOfMonth();
        $attendanceEnd = now()->copy()->subDay();
        $attendanceRows = 0;
        foreach ($employees as $employeeIndex => $employee) {
            $cursor = $attendanceStart->copy();
            while ($cursor->lessThanOrEqualTo($attendanceEnd)) {
                if ($cursor->isWeekend()) {
                    $cursor->addDay();
                    continue;
                }

                $seed = (($employeeIndex + 3) * 17) + ((int) $cursor->format('z'));
                $roll = $seed % 100;
                $status = match (true) {
                    $roll < 74 => Attendance::STATUS_PRESENT,
                    $roll < 84 => Attendance::STATUS_REMOTE,
                    $roll < 92 => Attendance::STATUS_HALF_DAY,
                    $roll < 97 => Attendance::STATUS_ABSENT,
                    default => Attendance::STATUS_ON_LEAVE,
                };

                $checkInAt = null;
                $checkOutAt = null;
                $workMinutes = null;
                if ($status === Attendance::STATUS_PRESENT || $status === Attendance::STATUS_REMOTE) {
                    $checkInAt = $cursor->copy()->setTime(9, ($seed % 30));
                    $checkOutAt = $cursor->copy()->setTime(18, ($seed % 20));
                    $workMinutes = (int) $checkOutAt->diffInMinutes($checkInAt);
                } elseif ($status === Attendance::STATUS_HALF_DAY) {
                    $checkInAt = $cursor->copy()->setTime(9, 30);
                    $checkOutAt = $cursor->copy()->setTime(13, 30);
                    $workMinutes = 240;
                }

                Attendance::query()->updateOrCreate(
                    [
                        'user_id' => $employee->id,
                        'attendance_date' => $cursor->toDateString(),
                    ],
                    [
                        'status' => $status,
                        'check_in_at' => $checkInAt,
                        'check_out_at' => $checkOutAt,
                        'work_minutes' => $workMinutes,
                        'notes' => null,
                        'marked_by_user_id' => $managerUsers[$employeeIndex % count($managerUsers)]->id,
                    ]
                );

                $attendanceRows++;
                $cursor->addDay();
            }
        }

        $leaveRows = 0;
        $leaveTypes = LeaveRequest::leaveTypes();
        foreach ($employees as $employeeIndex => $employee) {
            for ($offset = 0; $offset < 2; $offset++) {
                $start = now()->copy()->subDays((($employeeIndex + 1) * 19) + ($offset * 37))->startOfDay();
                $isHalfDay = (($employeeIndex + $offset) % 5) === 0;
                $dayType = $isHalfDay ? LeaveRequest::DAY_TYPE_HALF : LeaveRequest::DAY_TYPE_FULL;
                $duration = $isHalfDay ? 0 : (1 + (($employeeIndex + $offset) % 3));
                $end = $isHalfDay ? $start->copy() : $start->copy()->addDays($duration - 1);
                $totalDays = $isHalfDay ? 0.5 : (float) $duration;
                $status = match (($employeeIndex + $offset) % 4) {
                    0 => LeaveRequest::STATUS_PENDING,
                    1 => LeaveRequest::STATUS_APPROVED,
                    2 => LeaveRequest::STATUS_REJECTED,
                    default => LeaveRequest::STATUS_CANCELLED,
                };
                $reviewer = $status === LeaveRequest::STATUS_PENDING
                    ? null
                    : $managerUsers[($employeeIndex + $offset) % count($managerUsers)];

                LeaveRequest::query()->updateOrCreate(
                    [
                        'user_id' => $employee->id,
                        'leave_type' => $leaveTypes[($employeeIndex + $offset) % count($leaveTypes)],
                        'start_date' => $start->toDateString(),
                        'end_date' => $end->toDateString(),
                    ],
                    [
                        'day_type' => $dayType,
                        'total_days' => $totalDays,
                        'reason' => $status === LeaveRequest::STATUS_PENDING
                            ? 'Personal leave request for planned commitment.'
                            : 'Auto-generated demo leave request.',
                        'status' => $status,
                        'half_day_session' => $isHalfDay
                            ? (($employeeIndex % 2) === 0 ? LeaveRequest::HALF_DAY_FIRST : LeaveRequest::HALF_DAY_SECOND)
                            : null,
                        'reviewer_id' => $reviewer?->id,
                        'reviewed_at' => $reviewer ? $end->copy()->setTime(11, 0) : null,
                        'review_note' => $status === LeaveRequest::STATUS_REJECTED
                            ? 'Rejected for staffing constraints.'
                            : ($reviewer ? 'Reviewed in demo setup.' : null),
                    ]
                );

                $leaveRows++;
            }
        }

        $structureRows = 0;
        foreach ($employees as $employeeIndex => $employee) {
            $basic = 30000 + (($employeeIndex % 14) * 2400);
            $hra = round($basic * 0.4, 2);
            $specialAllowance = round($basic * 0.25, 2);
            $bonus = (($employeeIndex + 1) % 3 === 0) ? 3000.00 : 1500.00;
            $otherAllowance = 1200.00;
            $pf = round($basic * 0.12, 2);
            $tax = round($basic * 0.08, 2);
            $otherDeduction = 300.00;

            PayrollStructure::query()->updateOrCreate(
                ['user_id' => $employee->id],
                [
                    'basic_salary' => $basic,
                    'hra' => $hra,
                    'special_allowance' => $specialAllowance,
                    'bonus' => $bonus,
                    'other_allowance' => $otherAllowance,
                    'pf_deduction' => $pf,
                    'tax_deduction' => $tax,
                    'other_deduction' => $otherDeduction,
                    'effective_from' => now()->copy()->subMonthsNoOverflow(6)->startOfMonth()->toDateString(),
                    'notes' => 'Generated by demo:data command.',
                    'created_by_user_id' => $admin->id,
                    'updated_by_user_id' => $admin->id,
                ]
            );

            $structureRows++;
        }

        $targetMonths = collect(range(0, $months - 1))
            ->map(fn (int $offset): Carbon => now()->copy()->subMonthsNoOverflow($offset)->startOfMonth())
            ->values();

        $payrollRows = 0;
        foreach ($employees as $employeeIndex => $employee) {
            $structure = PayrollStructure::query()->firstWhere('user_id', $employee->id);
            if (! $structure) {
                continue;
            }

            $basic = (float) $structure->basic_salary;
            $hra = (float) $structure->hra;
            $specialAllowance = (float) $structure->special_allowance;
            $bonus = (float) $structure->bonus;
            $otherAllowance = (float) $structure->other_allowance;
            $gross = $basic + $hra + $specialAllowance + $bonus + $otherAllowance;
            $deductions = (float) $structure->pf_deduction
                + (float) $structure->tax_deduction
                + (float) $structure->other_deduction;

            foreach ($targetMonths as $monthIndex => $monthStart) {
                $workingDays = 22.0;
                $lopDays = (($employeeIndex + $monthIndex) % 6 === 0)
                    ? 1.0
                    : ((($employeeIndex + $monthIndex) % 9 === 0) ? 0.5 : 0.0);
                $payableDays = max(0.0, $workingDays - $lopDays);
                $net = round((($gross / $workingDays) * $payableDays) - $deductions, 2);
                if ($net < 0) {
                    $net = 0.0;
                }

                $isCurrentMonth = $monthStart->isSameMonth(now());
                $status = $isCurrentMonth ? Payroll::STATUS_PROCESSED : Payroll::STATUS_PAID;
                $paidAt = $status === Payroll::STATUS_PAID
                    ? $monthStart->copy()->endOfMonth()->setTime(18, 0)
                    : null;

                Payroll::query()->updateOrCreate(
                    [
                        'user_id' => $employee->id,
                        'payroll_month' => $monthStart->toDateString(),
                    ],
                    [
                        'working_days' => $workingDays,
                        'attendance_lop_days' => $lopDays,
                        'unpaid_leave_days' => 0,
                        'lop_days' => $lopDays,
                        'payable_days' => $payableDays,
                        'basic_pay' => round($basic, 2),
                        'hra' => round($hra, 2),
                        'special_allowance' => round($specialAllowance, 2),
                        'bonus' => round($bonus, 2),
                        'other_allowance' => round($otherAllowance, 2),
                        'gross_earnings' => round($gross, 2),
                        'pf_deduction' => round((float) $structure->pf_deduction, 2),
                        'tax_deduction' => round((float) $structure->tax_deduction, 2),
                        'other_deduction' => round((float) $structure->other_deduction, 2),
                        'total_deductions' => round($deductions, 2),
                        'net_salary' => $net,
                        'status' => $status,
                        'notes' => 'Generated by demo:data command.',
                        'generated_by_user_id' => $admin->id,
                        'paid_by_user_id' => $status === Payroll::STATUS_PAID ? $hr->id : null,
                        'paid_at' => $paidAt,
                        'payment_method' => $status === Payroll::STATUS_PAID ? Payroll::PAYMENT_BANK_TRANSFER : null,
                        'payment_reference' => $status === Payroll::STATUS_PAID
                            ? 'PAY-' . strtoupper(Str::random(8))
                            : null,
                    ]
                );

                $payrollRows++;
            }
        }

        $holidayRows = 0;
        $holidayTemplates = [
            ['name' => "New Year's Day", 'month' => 1, 'day' => 1, 'optional' => false],
            ['name' => 'Labor Day', 'month' => 5, 'day' => 1, 'optional' => false],
            ['name' => 'Independence Day', 'month' => 7, 'day' => 4, 'optional' => false],
            ['name' => 'Thanksgiving', 'month' => 11, 'day' => 27, 'optional' => true],
            ['name' => 'Christmas Day', 'month' => 12, 'day' => 25, 'optional' => false],
        ];

        $years = [now()->year, now()->year + 1];
        foreach ($years as $year) {
            foreach ($holidayTemplates as $holidayTemplate) {
                Holiday::query()->updateOrCreate(
                    [
                        'name' => $holidayTemplate['name'],
                        'holiday_date' => Carbon::create(
                            $year,
                            (int) $holidayTemplate['month'],
                            (int) $holidayTemplate['day']
                        )->toDateString(),
                        'branch_id' => null,
                    ],
                    [
                        'is_optional' => (bool) $holidayTemplate['optional'],
                        'holiday_type' => (bool) $holidayTemplate['optional'] ? 'optional' : 'public',
                        'is_active' => true,
                        'end_date' => null,
                        'description' => "Demo holiday for {$year}",
                        'created_by_user_id' => $admin->id,
                        'updated_by_user_id' => $admin->id,
                    ]
                );
                $holidayRows++;
            }
        }

        foreach ($branches as $branchIndex => $branch) {
            $foundationDate = Carbon::create(now()->year, 2 + $branchIndex, 10 + $branchIndex)->toDateString();

            Holiday::query()->updateOrCreate(
                [
                    'name' => "{$branch->name} Foundation Day",
                    'holiday_date' => $foundationDate,
                    'branch_id' => $branch->id,
                ],
                [
                    'is_optional' => true,
                    'holiday_type' => 'optional',
                    'is_active' => true,
                    'end_date' => null,
                    'description' => "Branch specific holiday for {$branch->name}",
                    'created_by_user_id' => $admin->id,
                    'updated_by_user_id' => $admin->id,
                ]
            );
            $holidayRows++;
        }

        Activity::query()->where('event_key', 'like', 'demo.%')->delete();

        $activityRows = 0;
        $activityActors = $employees->take(10)->push($admin)->push($hr)->values();
        for ($i = 0; $i < 40; $i++) {
            $actor = ($i % 6 === 0) ? null : $activityActors->get($i % max(1, $activityActors->count()));
            $occurredAt = now()->copy()->subHours(($i * 4) + 1);
            $eventKey = $i % 2 === 0 ? 'demo.seeded' : 'demo.sync';

            Activity::query()->create([
                'actor_user_id' => $actor?->id,
                'event_key' => $eventKey,
                'title' => $eventKey === 'demo.seeded' ? 'Demo data seeded' : 'Demo sync completed',
                'meta' => $actor ? "{$actor->name} triggered demo event" : 'System generated demo event',
                'tone' => '#7c3aed',
                'subject_type' => null,
                'subject_id' => null,
                'payload' => [
                    'source' => 'demo:data',
                    'index' => $i + 1,
                ],
                'occurred_at' => $occurredAt,
            ]);

            $activityRows++;
        }

        $this->newLine();
        $this->info('Demo data generation complete.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Employees (role=employee)', (string) $employees->count()],
                ['Attendance rows upserted', (string) $attendanceRows],
                ['Leave requests upserted', (string) $leaveRows],
                ['Payroll structures upserted', (string) $structureRows],
                ['Payroll rows upserted', (string) $payrollRows],
                ['Holiday rows upserted', (string) $holidayRows],
                ['Activity rows inserted', (string) $activityRows],
            ]
        );

        $this->newLine();
        $this->line('Default login credentials:');
        $this->line('- admin@hrm.test / Password@123');
        $this->line('- hr@hrm.test / Password@123');
        $this->line('- employee@hrm.test / Password@123');

        return self::SUCCESS;
    }
)->purpose('Generate demo HRM data with optional database cleanup');
