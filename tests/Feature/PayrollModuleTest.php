<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_payroll_module(): void
    {
        $this->get(route('modules.payroll.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_and_hr_can_access_payroll_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($admin)
            ->get(route('modules.payroll.index'))
            ->assertOk()
            ->assertSee('Payroll Setup');

        $this->actingAs($hr)
            ->get(route('modules.payroll.index'))
            ->assertOk()
            ->assertSee('Payroll Directory');
    }

    public function test_employee_sees_self_payroll_page_only(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('modules.payroll.index'))
            ->assertOk()
            ->assertSee('My Payroll')
            ->assertDontSee('Payroll Setup');
    }

    public function test_hr_can_store_salary_structure(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($hr)
            ->post(route('modules.payroll.structure.store'), [
                'user_id' => $employee->id,
                'basic_salary' => 30000,
                'hra' => 10000,
                'special_allowance' => 5000,
                'bonus' => 2000,
                'other_allowance' => 1000,
                'pf_deduction' => 1800,
                'tax_deduction' => 1200,
                'other_deduction' => 500,
                'effective_from' => '2026-03-01',
                'notes' => 'Initial setup',
            ])
            ->assertRedirect(route('modules.payroll.index'));

        $this->assertDatabaseHas('payroll_structures', [
            'user_id' => $employee->id,
            'basic_salary' => 30000.00,
            'hra' => 10000.00,
            'pf_deduction' => 1800.00,
            'created_by_user_id' => $hr->id,
            'updated_by_user_id' => $hr->id,
        ]);
    }

    public function test_hr_can_generate_payroll_with_attendance_and_unpaid_leave_deductions(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        PayrollStructure::query()->create([
            'user_id' => $employee->id,
            'basic_salary' => 31000,
            'hra' => 10000,
            'special_allowance' => 5000,
            'bonus' => 2000,
            'other_allowance' => 2000,
            'pf_deduction' => 2000,
            'tax_deduction' => 1500,
            'other_deduction' => 500,
            'created_by_user_id' => $hr->id,
            'updated_by_user_id' => $hr->id,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-03-10',
            'status' => Attendance::STATUS_ABSENT,
            'marked_by_user_id' => $hr->id,
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-03-12',
            'status' => Attendance::STATUS_HALF_DAY,
            'marked_by_user_id' => $hr->id,
        ]);

        LeaveRequest::query()->create([
            'user_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_UNPAID,
            'day_type' => LeaveRequest::DAY_TYPE_FULL,
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-20',
            'total_days' => 1.0,
            'reason' => 'Unpaid leave',
            'status' => LeaveRequest::STATUS_APPROVED,
            'reviewer_id' => $hr->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($hr)
            ->post(route('modules.payroll.generate'), [
                'user_id' => $employee->id,
                'payroll_month' => '2026-03',
                'notes' => 'Monthly payroll run',
            ])
            ->assertRedirect(route('modules.payroll.index'));

        $this->assertDatabaseHas('payrolls', [
            'user_id' => $employee->id,
            'payroll_month' => '2026-03-01',
            'working_days' => 31.00,
            'attendance_lop_days' => 1.50,
            'unpaid_leave_days' => 1.00,
            'lop_days' => 2.50,
            'payable_days' => 28.50,
            'status' => Payroll::STATUS_DRAFT,
            'generated_by_user_id' => $hr->id,
        ]);
    }

    public function test_employee_cannot_generate_or_update_payroll_status(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->post(route('modules.payroll.generate'), [
                'user_id' => $employee->id,
                'payroll_month' => '2026-03',
            ])
            ->assertForbidden();

        $payroll = Payroll::query()->create([
            'user_id' => $employee->id,
            'payroll_month' => '2026-03-01',
            'working_days' => 31,
            'payable_days' => 31,
            'attendance_lop_days' => 0,
            'unpaid_leave_days' => 0,
            'lop_days' => 0,
            'basic_pay' => 1000,
            'hra' => 0,
            'special_allowance' => 0,
            'bonus' => 0,
            'other_allowance' => 0,
            'gross_earnings' => 1000,
            'pf_deduction' => 0,
            'tax_deduction' => 0,
            'other_deduction' => 0,
            'total_deductions' => 0,
            'net_salary' => 1000,
            'status' => Payroll::STATUS_DRAFT,
        ]);

        $this->actingAs($employee)
            ->put(route('modules.payroll.status.update', $payroll), [
                'status' => Payroll::STATUS_PAID,
                'payment_method' => Payroll::PAYMENT_BANK_TRANSFER,
            ])
            ->assertForbidden();
    }

    public function test_hr_can_mark_payroll_as_paid(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $payroll = Payroll::query()->create([
            'user_id' => $employee->id,
            'payroll_month' => '2026-03-01',
            'working_days' => 31,
            'payable_days' => 30,
            'attendance_lop_days' => 1,
            'unpaid_leave_days' => 0,
            'lop_days' => 1,
            'basic_pay' => 20000,
            'hra' => 8000,
            'special_allowance' => 3000,
            'bonus' => 2000,
            'other_allowance' => 1000,
            'gross_earnings' => 34000,
            'pf_deduction' => 1800,
            'tax_deduction' => 1200,
            'other_deduction' => 500,
            'total_deductions' => 3500,
            'net_salary' => 30500,
            'status' => Payroll::STATUS_PROCESSED,
            'generated_by_user_id' => $hr->id,
        ]);

        $this->actingAs($hr)
            ->put(route('modules.payroll.status.update', $payroll), [
                'status' => Payroll::STATUS_PAID,
                'payment_method' => Payroll::PAYMENT_BANK_TRANSFER,
                'payment_reference' => 'UTR123456',
                'notes' => 'Paid on salary day',
            ])
            ->assertRedirect(route('modules.payroll.index'));

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => Payroll::STATUS_PAID,
            'payment_method' => Payroll::PAYMENT_BANK_TRANSFER,
            'payment_reference' => 'UTR123456',
            'paid_by_user_id' => $hr->id,
        ]);
    }
}
