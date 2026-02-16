<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LeaveRequest;
use App\Models\User;
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
            ->assertSee('Leave Directory');

        $this->actingAs($hr)
            ->get(route('modules.leave.index'))
            ->assertOk()
            ->assertSee('Pending Approvals');
    }

    public function test_employee_sees_leave_self_service_page(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('modules.leave.index'))
            ->assertOk()
            ->assertSee('Apply Leave')
            ->assertSee('Leave History')
            ->assertDontSee('Pending Approvals');
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
