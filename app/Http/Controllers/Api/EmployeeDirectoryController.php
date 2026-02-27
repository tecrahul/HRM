<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeDirectoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();

        $search = (string) $request->query('search', '');
        $departmentId = $request->integer('department_id') ?: null;
        $branchId = $request->integer('branch_id') ?: null;
        $status = (string) $request->query('status', '');
        $designationId = $request->integer('designation_id') ?: null;
        $sortBy = (string) $request->query('sort_by', 'id');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 12);
        $perPage = max(5, min(50, $perPage));

        $departmentName = null;
        if ($departmentId) {
            $departmentName = Department::query()->whereKey($departmentId)->value('name');
        }
        $branchName = null;
        if ($branchId) {
            $branchName = Branch::query()->whereKey($branchId)->value('name');
        }

        $query = User::query()
            ->workforce()
            ->with(['profile', 'designation'])
            // Access control (role-based)
            ->when($viewer && $viewer->hasAnyRole([
                UserRole::SUPER_ADMIN->value,
                UserRole::ADMIN->value,
                UserRole::HR->value,
                UserRole::FINANCE->value,
            ]) === false, function (Builder $q) use ($viewer): void {
                // Employees: limit strictly. If supervisor, show department; else only self
                $viewerDepartment = trim((string) ($viewer?->profile?->department ?? ''));
                if ($viewer && method_exists($viewer, 'isSupervisor') && $viewer->isSupervisor() && $viewerDepartment !== '') {
                    $q->whereHas('profile', function (Builder $p) use ($viewerDepartment): void {
                        $p->where('department', $viewerDepartment);
                    });
                } else {
                    $q->whereKey(optional($viewer)->id ?? 0);
                }
            })
            // Search across name, email, employee id (numeric id), designation
            ->when($search !== '', function (Builder $q) use ($search): void {
                $plain = trim($search);
                $digits = preg_replace('/\D+/', '', $plain) ?: null;
                $q->where(function (Builder $inner) use ($plain, $digits): void {
                    $inner->where(function (Builder $nameEmail): void {}) // noop for grouping
                        ->where(function (Builder $nameEmail) use ($plain): void {
                            $nameEmail
                                ->where('first_name', 'like', "%{$plain}%")
                                ->orWhere('last_name', 'like', "%{$plain}%")
                                ->orWhere('email', 'like', "%{$plain}%")
                                ->orWhereHas('profile', function (Builder $p) use ($plain): void {
                                    $p->where('job_title', 'like', "%{$plain}%")
                                        ->orWhere('department', 'like', "%{$plain}%")
                                        ->orWhere('branch', 'like', "%{$plain}%");
                                })
                                ->orWhereHas('designation', function (Builder $d) use ($plain): void {
                                    $d->where('name', 'like', "%{$plain}%");
                                });
                        });

                    if ($digits !== null && $digits !== '') {
                        $inner->orWhere('id', (int) $digits);
                    }
                });
            })
            // Filters
            ->when($designationId !== null, function (Builder $q) use ($designationId): void {
                $q->where('designation_id', $designationId);
            })
            ->when($departmentName !== null && $departmentName !== '', function (Builder $q) use ($departmentName): void {
                $q->whereHas('profile', function (Builder $p) use ($departmentName): void {
                    $p->where('department', $departmentName);
                });
            })
            ->when($branchName !== null && $branchName !== '', function (Builder $q) use ($branchName): void {
                $q->whereHas('profile', function (Builder $p) use ($branchName): void {
                    $p->where('branch', $branchName);
                });
            })
            ->when($status !== '', function (Builder $q) use ($status): void {
                $q->whereHas('profile', function (Builder $p) use ($status): void {
                    $p->where('status', $status);
                });
            });

        // Sorting (whitelist)
        $sortBy = match ($sortBy) {
            'full_name' => 'full_name',
            'joined_date' => 'joined_on',
            'designation' => 'designation_name',
            'department' => 'department',
            'branch' => 'branch',
            'status' => 'status',
            default => 'id',
        };

        // For sorts other than id/full_name, join profile/designation for stable ordering
        if (in_array($sortBy, ['joined_on', 'department', 'branch', 'status'], true)) {
            $query->leftJoin('user_profiles as up', 'users.id', '=', 'up.user_id')
                ->select('users.*');
            $query->orderBy("up.{$sortBy}", $sortDirection)->orderBy('users.id', 'desc');
        } elseif ($sortBy === 'designation_name') {
            $query->leftJoin('designations as ds', 'users.designation_id', '=', 'ds.id')
                ->select('users.*')
                ->orderBy('ds.name', $sortDirection)
                ->orderBy('users.id', 'desc');
        } elseif ($sortBy === 'full_name') {
            $query->orderBy('first_name', $sortDirection)->orderBy('last_name', $sortDirection);
        } else {
            $query->orderBy('id', $sortDirection);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function (User $user): array {
            $profile = $user->profile;
            $designation = $user->designation;
            $joined = $profile?->joined_on;
            $employeeCode = $profile?->employee_code ?: \App\Models\User::makeEmployeeCode((int) $user->id);
            $avatar = $profile?->avatar_url;
            if (blank($avatar)) {
                $avatar = asset('images/user-avatar.svg');
            } elseif (! str_starts_with((string) $avatar, 'http')) {
                $avatar = asset((string) $avatar);
            }

            return [
                'id' => (int) $user->id,
                'full_name' => $user->full_name,
                'employee_id' => $employeeCode,
                'email' => (string) $user->email,
                'designation' => (string) ($designation?->name ?? ''),
                'department' => (string) ($profile?->department ?? ''),
                'branch' => (string) ($profile?->branch ?? ''),
                'status' => (string) ($profile?->status ?? ''),
                'joined_date' => $joined ? $joined->format('Y-m-d') : null,
                'avatar' => $avatar,
            ];
        })->values()->all();

        // Options for filters (scoped to RBAC view)
        $options = [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name'])->map(fn ($d) => ['id' => (int) $d->id, 'name' => (string) $d->name])->all(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name'])->map(fn ($b) => ['id' => (int) $b->id, 'name' => (string) $b->name])->all(),
            'designations' => Designation::query()->orderBy('name')->get(['id', 'name'])->map(fn ($d) => ['id' => (int) $d->id, 'name' => (string) $d->name])->all(),
            'statuses' => ['active', 'inactive', 'suspended'],
        ];

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'options' => $options,
            ],
        ]);
    }
}

