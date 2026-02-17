<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $viewer = $request->user();

        if ($viewer?->hasRole(UserRole::EMPLOYEE)) {
            return $this->employeePage($viewer);
        }

        return $this->managementPage($request);
    }

    private function managementPage(Request $request): View
    {
        $search = (string) $request->string('q');
        $department = (string) $request->string('department');
        $branch = (string) $request->string('branch');
        $status = (string) $request->string('status');

        $employees = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->with('profile')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('profile', function ($profileQuery) use ($search): void {
                            $profileQuery
                                ->where('department', 'like', "%{$search}%")
                                ->orWhere('branch', 'like', "%{$search}%")
                                ->orWhere('job_title', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when($department !== '', function ($query) use ($department): void {
                $query->whereHas('profile', function ($profileQuery) use ($department): void {
                    $profileQuery->where('department', $department);
                });
            })
            ->when($branch !== '', function ($query) use ($branch): void {
                $query->whereHas('profile', function ($profileQuery) use ($branch): void {
                    $profileQuery->where('branch', $branch);
                });
            })
            ->when($status !== '', function ($query) use ($status): void {
                $query->whereHas('profile', function ($profileQuery) use ($status): void {
                    $profileQuery->where('status', $status);
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $employeeIds = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->pluck('id');

        $now = now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonthNoOverflow();
        $previousMonthEnd = $currentMonthStart->copy()->subSecond();

        $todayOnLeaveUserIds = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $yesterdayOnLeaveCount = LeaveRequest::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $yesterday)
            ->whereDate('end_date', '>=', $yesterday)
            ->distinct('user_id')
            ->count('user_id');

        $newJoinersThisMonth = UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('joined_on', [$currentMonthStart->toDateString(), $today])
            ->count();

        $newJoinersLastMonth = UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('joined_on', [$previousMonthStart->toDateString(), $previousMonthEnd->toDateString()])
            ->count();

        $headcountAddedThisMonth = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->whereBetween('created_at', [$currentMonthStart, $now])
            ->count();

        $headcountAddedLastMonth = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $stats = [
            'total' => $employeeIds->count(),
            'active' => UserProfile::query()
                ->whereIn('user_id', $employeeIds)
                ->where('status', 'active')
                ->count(),
            'inactive' => UserProfile::query()
                ->whereIn('user_id', $employeeIds)
                ->where('status', 'inactive')
                ->count(),
            'newJoiners' => $newJoinersThisMonth,
            'onLeaveToday' => count($todayOnLeaveUserIds),
        ];

        $statTrends = [
            'headcountAddedThisMonth' => $headcountAddedThisMonth,
            'headcountAddedLastMonth' => $headcountAddedLastMonth,
            'headcountDeltaThisMonth' => $headcountAddedThisMonth - $headcountAddedLastMonth,
            'activeRate' => $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100, 1) : 0.0,
            'newJoinersLastMonth' => $newJoinersLastMonth,
            'newJoinersDelta' => $newJoinersThisMonth - $newJoinersLastMonth,
            'onLeaveYesterday' => $yesterdayOnLeaveCount,
            'onLeaveDelta' => count($todayOnLeaveUserIds) - $yesterdayOnLeaveCount,
        ];

        $departmentOptions = UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->pluck('department')
            ->merge(
                Department::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->pluck('name')
            )
            ->filter(fn ($department): bool => ! blank($department))
            ->map(fn ($department): string => trim((string) $department))
            ->unique()
            ->sort()
            ->values();

        $branchOptions = UserProfile::query()
            ->whereIn('user_id', $employeeIds)
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->pluck('branch')
            ->merge(
                Branch::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->pluck('name')
            )
            ->filter(fn ($branch): bool => ! blank($branch))
            ->map(fn ($branch): string => trim((string) $branch))
            ->unique()
            ->sort()
            ->values();

        $departmentBreakdown = UserProfile::query()
            ->selectRaw('COALESCE(NULLIF(department, ""), "Unassigned") as department_label')
            ->selectRaw('COUNT(*) as employee_count')
            ->whereIn('user_id', $employeeIds)
            ->groupBy('department_label')
            ->orderByDesc('employee_count')
            ->limit(5)
            ->get();

        $onLeaveUserIds = $todayOnLeaveUserIds;

        return view('modules.employees.admin', [
            'employees' => $employees,
            'stats' => $stats,
            'departmentOptions' => $departmentOptions,
            'branchOptions' => $branchOptions,
            'departmentBreakdown' => $departmentBreakdown,
            'statusOptions' => ['active', 'inactive', 'suspended'],
            'filters' => [
                'q' => $search,
                'department' => $department,
                'branch' => $branch,
                'status' => $status,
            ],
            'onLeaveUserIds' => $onLeaveUserIds,
            'statTrends' => $statTrends,
            'selectedEmployeeId' => (int) $request->integer('selected'),
            'canManageUsers' => $request->user()?->hasAnyRole([UserRole::ADMIN->value, UserRole::HR->value]) ?? false,
        ]);
    }

    private function employeePage(User $viewer): View
    {
        $viewer->loadMissing('profile');

        $profile = $viewer->profile;
        $department = $profile?->department;

        $teamMates = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->with('profile')
            ->when($department !== null && $department !== '', function ($query) use ($department, $viewer): void {
                $query->whereHas('profile', function ($profileQuery) use ($department): void {
                    $profileQuery->where('department', $department);
                })->whereKeyNot($viewer->id);
            }, function ($query) use ($viewer): void {
                $query->whereKeyNot($viewer->id);
            })
            ->latest()
            ->limit(6)
            ->get();

        $profileFields = [
            $profile?->phone,
            $profile?->alternate_phone,
            $profile?->department,
            $profile?->branch,
            $profile?->job_title,
            $profile?->employment_type,
            $profile?->joined_on,
            $profile?->date_of_birth,
            $profile?->gender,
            $profile?->marital_status,
            $profile?->nationality,
            $profile?->national_id,
            $profile?->work_location,
            $profile?->manager_name,
            $profile?->linkedin_url,
            $profile?->address,
            $profile?->emergency_contact_name,
            $profile?->emergency_contact_phone,
        ];

        $profileCompletion = (int) round(
            collect($profileFields)->filter(fn ($value): bool => ! blank($value))->count() / count($profileFields) * 100
        );

        return view('modules.employees.employee', [
            'viewer' => $viewer,
            'profile' => $profile,
            'teamMates' => $teamMates,
            'profileCompletion' => $profileCompletion,
        ]);
    }
}
