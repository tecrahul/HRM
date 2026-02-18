<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $viewer = $request->user();
        if (! $viewer instanceof User || ! $viewer->hasAnyRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ])) {
            abort(403, 'You do not have access to employee search.');
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        if ($keyword === '') {
            return response()->json([]);
        }
        $keywordLower = mb_strtolower($keyword);

        $branchName = isset($validated['branch_id'])
            ? Branch::query()->whereKey((int) $validated['branch_id'])->value('name')
            : null;
        $departmentName = isset($validated['department_id'])
            ? Department::query()->whereKey((int) $validated['department_id'])->value('name')
            : null;

        $records = User::query()
            ->leftJoin('user_profiles as profile', 'profile.user_id', '=', 'users.id')
            ->where('users.role', UserRole::EMPLOYEE->value)
            ->when(is_string($branchName) && trim($branchName) !== '', function ($query) use ($branchName): void {
                $query->whereRaw('LOWER(TRIM(COALESCE(profile.branch, \"\"))) = ?', [mb_strtolower(trim((string) $branchName))]);
            })
            ->when(is_string($departmentName) && trim($departmentName) !== '', function ($query) use ($departmentName): void {
                $query->whereRaw('LOWER(TRIM(COALESCE(profile.department, \"\"))) = ?', [mb_strtolower(trim((string) $departmentName))]);
            })
            ->where(function ($query) use ($keyword): void {
                $query
                    ->whereRaw('LOWER(users.name) like ?', ['%' . mb_strtolower($keyword) . '%'])
                    ->orWhereRaw('LOWER(users.email) like ?', ['%' . mb_strtolower($keyword) . '%']);
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                DB::raw("COALESCE(profile.department, '') as department"),
            ])
            ->orderByRaw(
                'CASE WHEN LOWER(users.name) LIKE ? THEN 0 WHEN LOWER(users.email) LIKE ? THEN 1 ELSE 2 END, users.name ASC',
                [$keywordLower . '%', $keywordLower . '%']
            )
            ->limit(15)
            ->get()
            ->map(static function ($record): array {
                return [
                    'id' => (int) $record->id,
                    'name' => (string) $record->name,
                    'email' => (string) $record->email,
                    'department' => (string) ($record->department ?? ''),
                ];
            })
            ->values()
            ->all();

        return response()->json($records);
    }
}
