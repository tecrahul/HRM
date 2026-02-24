<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Mail\EmployeeLoginCredentialsMail;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollStructure;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EmployeeOnboardingController extends Controller
{
    public function overview(Request $request, User $user): View
    {
        $employee = $this->resolveEmployee($user);

        $payload = [
            'employee' => $this->employeePayload($employee),
            'setupStatus' => $this->buildSetupStatus($employee),
            'urls' => [
                'setupStatusApi' => route('api.employees.setup-status', $employee),
                'sendLoginApi' => route('api.employees.send-login', $employee),
                'editDetails' => route('admin.users.edit', $employee),
                'viewFullProfile' => route('modules.employees.index', ['selected' => $employee->id]),
                'salaryStructure' => route('modules.payroll.salary-structures', ['employee_id' => $employee->id]),
                'payrollProcessing' => route('modules.payroll.processing', ['employee_id' => $employee->id]),
                'leavePolicy' => route('modules.leave.index', ['employee_id' => $employee->id]),
                'attendancePolicy' => route('modules.attendance.overview', ['employee_id' => $employee->id]),
                'bankDetails' => route('admin.users.edit', $employee),
                'documents' => route('admin.users.edit', $employee).'#documents',
            ],
            'meta' => [
                'successTitle' => 'Employee Created Successfully',
                'successSubtitle' => 'Complete remaining setup steps below',
            ],
            'ui' => [
                'showSuccessHeader' => (bool) $request->session()->get('show_employee_created_banner', false),
            ],
        ];

        return view('modules.employees.onboarding-overview', [
            'overviewPayload' => $payload,
        ]);
    }

    public function setupStatus(User $user): JsonResponse
    {
        $employee = $this->resolveEmployee($user);

        return response()->json($this->buildSetupStatus($employee));
    }

    public function sendLogin(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user();
        $employee = $this->resolveEmployee($user);

        if (! $viewer instanceof User || ! $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
        ])) {
            abort(403, 'You are not authorized to send login credentials.');
        }

        $hadLoginSent = AuditLog::query()
            ->where('entity_type', 'employee')
            ->where('entity_id', $employee->id)
            ->where('action', 'employee.login_credentials.sent')
            ->exists();

        $temporaryPassword = Str::password(12, letters: true, numbers: true, symbols: true, spaces: false);

        try {
            DB::transaction(function () use ($employee, $viewer, $temporaryPassword, $hadLoginSent): void {
                $employee->forceFill([
                    'password' => $temporaryPassword,
                    'email_verified_at' => $employee->email_verified_at ?? now(),
                ]);
                $employee->save();

                Mail::to($employee->email)->send(new EmployeeLoginCredentialsMail(
                    employee: $employee,
                    temporaryPassword: $temporaryPassword,
                    sentBy: $viewer
                ));

                AuditLog::query()->create([
                    'entity_type' => 'employee',
                    'entity_id' => $employee->id,
                    'action' => 'employee.login_credentials.sent',
                    'performed_by_user_id' => $viewer->id,
                    'old_values' => ['loginSent' => $hadLoginSent],
                    'new_values' => ['loginSent' => true],
                    'metadata' => [
                        'employee_email' => $employee->email,
                        'password_rotated' => true,
                    ],
                    'performed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to send login credentials right now. Please try again.',
            ], 500);
        }

        ActivityLogger::log(
            $viewer,
            'employee.login_credentials.sent',
            'Employee login credentials sent',
            "{$employee->name} ({$employee->email})",
            '#10b981',
            $employee,
            ['password_rotated' => true]
        );

        return response()->json([
            'message' => 'Login credentials sent successfully.',
            'loginSent' => true,
            'sentAt' => now()->toIso8601String(),
            'passwordRotated' => true,
        ]);
    }

    private function resolveEmployee(User $user): User
    {
        $user->loadMissing('profile');

        if (! $user->isEmployeeRecord()) {
            abort(404);
        }

        return $user;
    }

    /**
     * @return array{salaryConfigured: bool, payrollAssigned: bool, leavePolicyAssigned: bool, attendancePolicyAssigned: bool, bankDetailsAdded: bool, documentsUploaded: bool, loginSent: bool}
     */
    private function buildSetupStatus(User $employee): array
    {
        $employee->loadMissing('profile');
        $profile = $employee->profile;

        $salaryConfigured = PayrollStructure::query()
            ->where('user_id', $employee->id)
            ->exists();

        $payrollAssigned = Payroll::query()
            ->where('user_id', $employee->id)
            ->whereYear('payroll_month', now()->year)
            ->whereMonth('payroll_month', now()->month)
            ->exists();

        // Current module has no dedicated policy tables yet; policy assignment is inferred from profile completeness.
        $leavePolicyAssigned = filled($profile?->employment_type) && filled($profile?->department);
        $attendancePolicyAssigned = filled($profile?->branch) || filled($profile?->work_location);

        $bankDetailsAdded = filled($profile?->bank_account_name)
            && filled($profile?->bank_account_number)
            && filled($profile?->bank_ifsc);

        // Document management module is not yet modeled; keep false until that table is introduced.
        $documentsUploaded = false;

        $loginSent = AuditLog::query()
            ->where('entity_type', 'employee')
            ->where('entity_id', $employee->id)
            ->where('action', 'employee.login_credentials.sent')
            ->exists();

        return [
            'salaryConfigured' => $salaryConfigured,
            'payrollAssigned' => $payrollAssigned,
            'leavePolicyAssigned' => $leavePolicyAssigned,
            'attendancePolicyAssigned' => $attendancePolicyAssigned,
            'bankDetailsAdded' => $bankDetailsAdded,
            'documentsUploaded' => $documentsUploaded,
            'loginSent' => $loginSent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employeePayload(User $employee): array
    {
        $employee->loadMissing('profile');
        $profile = $employee->profile;

        $leavesToday = LeaveRequest::query()
            ->where('user_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        return [
            'id' => $employee->id,
            'employeeCode' => $profile?->employee_code ?: User::makeEmployeeCode($employee->id),
            'name' => $employee->name,
            'email' => $employee->email,
            'avatarUrl' => $this->resolveAvatarUrl($profile?->avatar_url),
            'department' => $profile?->department,
            'branch' => $profile?->branch,
            'designation' => $profile?->job_title,
            'joiningDate' => $profile?->joined_on?->toDateString(),
            'employmentType' => $profile?->employment_type,
            'status' => $profile?->status ?? 'pending',
            'isOnLeaveToday' => $leavesToday,
        ];
    }

    private function resolveAvatarUrl(?string $avatarUrl): string
    {
        $raw = trim((string) $avatarUrl);
        if ($raw === '') {
            return asset('images/user-avatar.svg');
        }

        if (
            str_starts_with($raw, 'http://')
            || str_starts_with($raw, 'https://')
            || str_starts_with($raw, 'data:')
            || str_starts_with($raw, '/')
        ) {
            return $raw;
        }

        if (str_starts_with($raw, 'storage/')) {
            return asset($raw);
        }

        if (str_starts_with($raw, 'public/')) {
            return asset('storage/'.ltrim(substr($raw, 7), '/'));
        }

        return asset('storage/'.$raw);
    }
}
