<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_report_counts_only_overlapping_days_for_cross_month_leave(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $this->createProfile($employee);

        LeaveRequest::query()->create([
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => '2026-01-28',
            'end_date' => '2026-02-02',
            'total_days' => 6.0,
            'reason' => 'Cross month leave',
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin)->get(route('modules.reports.index', [
            'month' => '2026-02',
            'report_type' => 'leave_monthly',
        ]));

        $response->assertOk();

        $stats = $response->viewData('stats');
        $rows = $response->viewData('rows');

        $this->assertSame(1, (int) $stats['leaveRequests']);
        $this->assertSame(2.0, (float) $stats['leaveApprovedDays']);
        $this->assertSame(2.0, (float) $rows->first()['approved_leave_days']);
    }

    public function test_csv_export_escapes_formula_like_values(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);
        $employee = User::factory()->create([
            'name' => '=SUM(1,1)',
            'email' => '+danger@example.com',
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $this->createProfile($employee);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-02-05',
            'status' => Attendance::STATUS_PRESENT,
            'work_minutes' => 480,
        ]);

        $response = $this->actingAs($admin)->get(route('modules.reports.index', [
            'month' => '2026-02',
            'report_type' => 'comprehensive',
            'export' => 'csv',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString("'=SUM(1,1)", $csv);
        $this->assertStringContainsString("'+danger@example.com", $csv);
    }

    public function test_employee_report_scope_cannot_be_expanded_by_employee_id_filter(): void
    {
        $employeeA = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $employeeB = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $this->createProfile($employeeA);
        $this->createProfile($employeeB);

        Attendance::query()->create([
            'user_id' => $employeeA->id,
            'attendance_date' => '2026-02-10',
            'status' => Attendance::STATUS_PRESENT,
            'work_minutes' => 480,
        ]);
        Attendance::query()->create([
            'user_id' => $employeeB->id,
            'attendance_date' => '2026-02-10',
            'status' => Attendance::STATUS_PRESENT,
            'work_minutes' => 480,
        ]);

        $response = $this->actingAs($employeeA)->get(route('modules.reports.index', [
            'month' => '2026-02',
            'employee_id' => $employeeB->id,
        ]));

        $response->assertOk();

        $stats = $response->viewData('stats');
        $rows = $response->viewData('rows');

        $this->assertSame(1, (int) $stats['employeeCount']);
        $this->assertCount(1, $rows);
        $this->assertSame($employeeA->name, $rows->first()['name']);
    }

    public function test_employee_cannot_open_other_users_activity_detail(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $otherEmployee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $ownActivity = Activity::query()->create([
            'actor_user_id' => $employee->id,
            'event_key' => 'reports.exported',
            'title' => 'Own activity',
            'occurred_at' => now(),
        ]);
        $otherActivity = Activity::query()->create([
            'actor_user_id' => $otherEmployee->id,
            'event_key' => 'reports.exported',
            'title' => 'Other activity',
            'occurred_at' => now(),
        ]);

        $this->actingAs($employee)
            ->get(route('modules.reports.activity.show', ['activity' => $ownActivity->id]))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route('modules.reports.activity.show', ['activity' => $otherActivity->id]))
            ->assertForbidden();
    }

    public function test_activity_payload_is_redacted_in_activity_detail_view(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $activity = Activity::query()->create([
            'actor_user_id' => $admin->id,
            'event_key' => 'reports.exported',
            'title' => 'Sensitive payload',
            'payload' => [
                'password' => 'my-password',
                'nested' => [
                    'access_token' => 'abc-123',
                    'note' => 'safe-note',
                ],
            ],
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('modules.reports.activity.show', ['activity' => $activity->id]))
            ->assertOk()
            ->assertDontSee('my-password')
            ->assertDontSee('abc-123')
            ->assertSee('[REDACTED]');
    }

    public function test_activity_detail_returns_404_when_activity_table_is_unavailable(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        Schema::drop('activities');

        $this->actingAs($admin)
            ->get(route('modules.reports.activity.show', ['activity' => 1]))
            ->assertNotFound();
    }

    private function createProfile(User $user): void
    {
        UserProfile::query()->create([
            'user_id' => $user->id,
            'employment_type' => 'full_time',
            'status' => 'active',
            'is_employee' => true,
        ]);
    }
}
