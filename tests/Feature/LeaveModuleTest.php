<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_leave_module(): void
    {
        $this->get(route('modules.leave.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_and_hr_can_access_leave_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($admin)
            ->get(route('modules.leave.index'))
            ->assertOk()
            ->assertSee('Leave Management');

        $this->actingAs($hr)
            ->get(route('modules.leave.index'))
            ->assertOk()
            ->assertSee('Leave Management');
    }

    public function test_employee_sees_leave_self_service_page(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('modules.leave.index'))
            ->assertOk()
            ->assertSee('Leave Management')
            ->assertSee('leave-management-root');
    }

    public function test_leave_filters_default_to_current_financial_year(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $range = FinancialYear::rangeForStartYear(FinancialYear::currentStartYear());
        $dateFrom = $range['start']->toDateString();
        $dateTo = $range['end']->toDateString();

        LeaveRequest::query()->create([
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $range['start']->copy()->subDay()->toDateString(),
            'end_date' => $range['start']->copy()->subDay()->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Before FY window',
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $insideLeave = LeaveRequest::query()->create([
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $range['start']->copy()->addDay()->toDateString(),
            'end_date' => $range['start']->copy()->addDay()->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Within FY window',
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin)->getJson(route('modules.leave.index'));

        $response
            ->assertOk()
            ->assertJsonPath('filters.date_from', $dateFrom)
            ->assertJsonPath('filters.date_to', $dateTo)
            ->assertJsonPath('meta.total', 1);

        $this->assertSame([$insideLeave->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_management_roles_can_filter_by_specific_employee(): void
    {
        $employeeA = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $employeeB = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $range = FinancialYear::rangeForStartYear(FinancialYear::currentStartYear());
        $baseDate = $range['start']->copy()->addDays(5)->toDateString();

        $leaveA = LeaveRequest::query()->create([
            'user_id' => $employeeA->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $baseDate,
            'end_date' => $baseDate,
            'total_days' => 1.0,
            'reason' => 'Employee A leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        LeaveRequest::query()->create([
            'user_id' => $employeeB->id,
            'leave_type' => LeaveRequest::TYPE_SICK,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $range['start']->copy()->addDays(6)->toDateString(),
            'end_date' => $range['start']->copy()->addDays(6)->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Employee B leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        foreach ([UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::HR] as $role) {
            $manager = User::factory()->create([
                'role' => $role->value,
            ]);

            $response = $this->actingAs($manager)->getJson(route('modules.leave.index', [
                'employee_id' => $employeeA->id,
            ]));

            $response
                ->assertOk()
                ->assertJsonPath('filters.employee_id', (string) $employeeA->id)
                ->assertJsonPath('meta.total', 1);

            $this->assertSame([$leaveA->id], collect($response->json('data'))->pluck('id')->all());
        }
    }

    public function test_finance_cannot_filter_by_specific_employee(): void
    {
        $finance = User::factory()->create([
            'role' => UserRole::FINANCE->value,
        ]);
        $employeeA = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $employeeB = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $range = FinancialYear::rangeForStartYear(FinancialYear::currentStartYear());
        $leaveA = LeaveRequest::query()->create([
            'user_id' => $employeeA->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $range['start']->copy()->addDays(7)->toDateString(),
            'end_date' => $range['start']->copy()->addDays(7)->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Finance view leave A',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
        $leaveB = LeaveRequest::query()->create([
            'user_id' => $employeeB->id,
            'leave_type' => LeaveRequest::TYPE_EARNED,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $range['start']->copy()->addDays(8)->toDateString(),
            'end_date' => $range['start']->copy()->addDays(8)->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Finance view leave B',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($finance)->getJson(route('modules.leave.index', [
            'employee_id' => $employeeA->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('filters.employee_id', '')
            ->assertJsonPath('meta.total', 2);

        $this->assertEqualsCanonicalizing(
            [$leaveA->id, $leaveB->id],
            collect($response->json('data'))->pluck('id')->all()
        );
    }

    public function test_employee_cannot_filter_by_other_employee_id(): void
    {
        $employeeA = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $employeeB = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $range = FinancialYear::rangeForStartYear(FinancialYear::currentStartYear());
        $leaveA = LeaveRequest::query()->create([
            'user_id' => $employeeA->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $range['start']->copy()->addDays(9)->toDateString(),
            'end_date' => $range['start']->copy()->addDays(9)->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Own leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
        LeaveRequest::query()->create([
            'user_id' => $employeeB->id,
            'leave_type' => LeaveRequest::TYPE_SICK,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => $range['start']->copy()->addDays(10)->toDateString(),
            'end_date' => $range['start']->copy()->addDays(10)->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Other employee leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($employeeA)->getJson(route('modules.leave.index', [
            'employee_id' => $employeeB->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('filters.employee_id', '')
            ->assertJsonPath('meta.total', 1);

        $this->assertSame([$leaveA->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_employee_can_submit_leave_request(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->post(route('modules.leave.store'), [
                'leave_type' => LeaveRequest::TYPE_CASUAL,
                'day_type' => LeaveRequest::DAY_TYPE_FULL,
                'start_date' => '2026-03-10',
                'end_date' => '2026-03-12',
                'reason' => 'Family event',
            ])
            ->assertRedirect(route('modules.leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'status' => LeaveRequest::STATUS_PENDING,
            'total_days' => 3.00,
        ]);
    }

    public function test_admin_can_assign_leave_to_employee(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($admin)
            ->post(route('modules.leave.store'), [
                'user_id' => $employee->id,
                'leave_type' => LeaveRequest::TYPE_EARNED,
                'day_type' => LeaveRequest::DAY_TYPE_FULL,
                'start_date' => '2026-03-20',
                'end_date' => '2026-03-21',
                'reason' => 'Assigned annual leave',
                'assign_note' => 'Assigned by admin',
            ])
            ->assertRedirect(route('modules.leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_EARNED,
            'status' => LeaveRequest::STATUS_APPROVED,
            'reviewer_id' => $admin->id,
            'review_note' => 'Assigned by admin',
            'total_days' => 2.00,
        ]);
    }

    public function test_hr_can_assign_half_day_leave_to_employee(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($hr)
            ->post(route('modules.leave.store'), [
                'user_id' => $employee->id,
                'leave_type' => LeaveRequest::TYPE_CASUAL,
                'day_type' => LeaveRequest::DAY_TYPE_HALF,
                'start_date' => '2026-03-25',
                'end_date' => '2026-03-25',
                'half_day_session' => LeaveRequest::HALF_DAY_SECOND,
                'reason' => 'Medical visit',
            ])
            ->assertRedirect(route('modules.leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'day_type' => LeaveRequest::DAY_TYPE_HALF,
            'half_day_session' => LeaveRequest::HALF_DAY_SECOND,
            'status' => LeaveRequest::STATUS_APPROVED,
            'reviewer_id' => $hr->id,
            'total_days' => 0.50,
        ]);
    }

    public function test_employee_can_submit_half_day_leave_with_slot(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->post(route('modules.leave.store'), [
                'leave_type' => LeaveRequest::TYPE_SICK,
                'day_type' => LeaveRequest::DAY_TYPE_HALF,
                'start_date' => '2026-03-18',
                'end_date' => '2026-03-18',
                'half_day_session' => LeaveRequest::HALF_DAY_FIRST,
                'reason' => 'Doctor appointment',
            ])
            ->assertRedirect(route('modules.leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_SICK,
            'day_type' => LeaveRequest::DAY_TYPE_HALF,
            'half_day_session' => LeaveRequest::HALF_DAY_FIRST,
            'total_days' => 0.50,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }

    public function test_half_day_leave_requires_slot_selection(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->from(route('modules.leave.index'))
            ->post(route('modules.leave.store'), [
                'leave_type' => LeaveRequest::TYPE_EARNED,
                'day_type' => LeaveRequest::DAY_TYPE_HALF,
                'start_date' => '2026-03-18',
                'end_date' => '2026-03-18',
                'reason' => 'Personal errand',
            ])
            ->assertRedirect(route('modules.leave.index'))
            ->assertSessionHasErrors(['half_day_session']);
    }

    public function test_employee_cannot_assign_leave_to_other_employee(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $otherEmployee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->from(route('modules.leave.index'))
            ->post(route('modules.leave.store'), [
                'user_id' => $otherEmployee->id,
                'leave_type' => LeaveRequest::TYPE_CASUAL,
                'day_type' => LeaveRequest::DAY_TYPE_FULL,
                'start_date' => '2026-03-10',
                'end_date' => '2026-03-10',
                'reason' => 'Invalid assignment attempt',
            ])
            ->assertRedirect(route('modules.leave.index'))
            ->assertSessionHasErrors(['user_id']);

        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $otherEmployee->id,
            'reason' => 'Invalid assignment attempt',
        ]);
    }

    public function test_employee_cannot_review_leave_request(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $otherEmployee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $otherEmployee->id,
            'leave_type' => LeaveRequest::TYPE_SICK,
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-10',
            'total_days' => 1.0,
            'reason' => 'Fever',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($employee)
            ->put(route('modules.leave.review', $leaveRequest), [
                'status' => LeaveRequest::STATUS_APPROVED,
            ])
            ->assertForbidden();
    }

    public function test_hr_can_approve_pending_leave_request(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_EARNED,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-03',
            'total_days' => 3.0,
            'reason' => 'Personal travel',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($hr)
            ->put(route('modules.leave.review', $leaveRequest), [
                'status' => LeaveRequest::STATUS_APPROVED,
                'review_note' => 'Approved as per policy',
            ])
            ->assertRedirect(route('modules.leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequest::STATUS_APPROVED,
            'reviewer_id' => $hr->id,
            'review_note' => 'Approved as per policy',
        ]);
    }

    public function test_employee_can_cancel_own_pending_request(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'start_date' => '2026-05-15',
            'end_date' => '2026-05-16',
            'total_days' => 2.0,
            'reason' => 'Personal work',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($employee)
            ->delete(route('modules.leave.cancel', $leaveRequest))
            ->assertRedirect(route('modules.leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequest::STATUS_CANCELLED,
        ]);
    }

    public function test_employee_cannot_cancel_other_employee_request(): void
    {
        $employeeA = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $employeeB = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $employeeB->id,
            'leave_type' => LeaveRequest::TYPE_CASUAL,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'total_days' => 1.0,
            'reason' => 'One-day leave',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($employeeA)
            ->delete(route('modules.leave.cancel', $leaveRequest))
            ->assertForbidden();
    }
}
