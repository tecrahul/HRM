<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed RBAC roles and permissions
        $this->seed(RbacSeeder::class);
    }

    /** @test */
    public function admin_can_view_any_attendance(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('viewAny', Attendance::class));
    }

    /** @test */
    public function employee_can_view_any_attendance(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employee->assignRole('employee');

        $this->assertTrue($employee->can('viewAny', Attendance::class));
    }

    /** @test */
    public function admin_can_view_all_attendance_records(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $admin->assignRole('admin');

        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $attendance = Attendance::factory()->create(['user_id' => $employee->id]);

        $this->assertTrue($admin->can('view', $attendance));
    }

    /** @test */
    public function employee_can_only_view_own_attendance(): void
    {
        $employee1 = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employee1->assignRole('employee');

        $employee2 = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $ownAttendance = Attendance::factory()->create(['user_id' => $employee1->id]);
        $otherAttendance = Attendance::factory()->create(['user_id' => $employee2->id]);

        $this->assertTrue($employee1->can('view', $ownAttendance));
        $this->assertFalse($employee1->can('view', $otherAttendance));
    }

    /** @test */
    public function hr_can_approve_leave_requests(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR->value]);
        $hr->assignRole('hr');

        $this->assertTrue($hr->can('approve', LeaveRequest::class));
    }

    /** @test */
    public function employee_cannot_approve_leave_requests(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employee->assignRole('employee');

        $this->assertFalse($employee->can('approve', LeaveRequest::class));
    }

    /** @test */
    public function employee_can_create_leave_request(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employee->assignRole('employee');

        $this->assertTrue($employee->can('create', LeaveRequest::class));
    }

    /** @test */
    public function finance_can_view_all_payroll(): void
    {
        $finance = User::factory()->create(['role' => UserRole::FINANCE->value]);
        $finance->assignRole('finance');

        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $payroll = Payroll::factory()->create(['user_id' => $employee->id]);

        $this->assertTrue($finance->can('view', $payroll));
    }

    /** @test */
    public function employee_can_only_view_own_payroll(): void
    {
        $employee1 = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employee1->assignRole('employee');

        $employee2 = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $ownPayroll = Payroll::factory()->create(['user_id' => $employee1->id]);
        $otherPayroll = Payroll::factory()->create(['user_id' => $employee2->id]);

        $this->assertTrue($employee1->can('view', $ownPayroll));
        $this->assertFalse($employee1->can('view', $otherPayroll));
    }

    /** @test */
    public function user_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $admin->assignRole('admin');

        $this->assertFalse($admin->can('delete', $admin));
    }

    /** @test */
    public function admin_can_delete_other_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $admin->assignRole('admin');

        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->assertTrue($admin->can('delete', $employee));
    }

    /** @test */
    public function super_admin_can_manage_roles(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $superAdmin->assignRole('super_admin');

        $this->assertTrue($superAdmin->can('manageRoles', User::class));
    }

    /** @test */
    public function employee_cannot_manage_roles(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employee->assignRole('employee');

        $this->assertFalse($employee->can('manageRoles', User::class));
    }
}
