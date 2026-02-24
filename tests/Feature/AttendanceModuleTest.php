<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_access_attendance_module(): void
    {
        $this->get(route('modules.attendance.overview'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_and_hr_can_access_attendance_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $this->actingAs($admin)
            ->get(route('modules.attendance.overview'))
            ->assertOk()
            ->assertSee('Mark Attendance');

        $this->actingAs($hr)
            ->get(route('modules.attendance.overview'))
            ->assertOk()
            ->assertSee('Attendance Directory');
    }

    public function test_employee_sees_self_attendance_page(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->get(route('modules.attendance.overview'))
            ->assertOk()
            ->assertSee('My Attendance')
            ->assertDontSee('Mark Attendance');
    }

    public function test_hr_can_store_attendance_for_employee(): void
    {
        $hr = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($hr)
            ->post(route('modules.attendance.store'), [
                'user_id' => $employee->id,
                'attendance_date' => '2026-02-15',
                'status' => Attendance::STATUS_PRESENT,
                'check_in_time' => '09:00',
                'check_out_time' => '18:00',
                'notes' => 'Onsite shift',
            ])
            ->assertRedirect(route('modules.attendance.overview'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $employee->id,
            'attendance_date' => '2026-02-15',
            'status' => Attendance::STATUS_PRESENT,
            'work_minutes' => 540,
        ]);
    }

    public function test_employee_cannot_store_attendance_via_management_endpoint(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $this->actingAs($employee)
            ->post(route('modules.attendance.store'), [
                'user_id' => $employee->id,
                'attendance_date' => '2026-02-15',
                'status' => Attendance::STATUS_PRESENT,
            ])
            ->assertForbidden();
    }

    public function test_employee_can_check_in_and_check_out(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        Carbon::setTestNow('2026-02-15 09:00:00');
        $this->actingAs($employee)
            ->post(route('modules.attendance.check-in'))
            ->assertRedirect(route('modules.attendance.overview'));

        Carbon::setTestNow('2026-02-15 17:30:00');
        $this->actingAs($employee)
            ->post(route('modules.attendance.check-out'))
            ->assertRedirect(route('modules.attendance.overview'));

        $record = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', '2026-02-15')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(510, $record->work_minutes);
        $this->assertNotNull($record->check_in_at);
        $this->assertNotNull($record->check_out_at);
    }

    public function test_admin_can_update_attendance_record(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);

        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-02-15',
            'status' => Attendance::STATUS_PRESENT,
            'check_in_at' => '2026-02-15 09:00:00',
            'check_out_at' => '2026-02-15 18:00:00',
            'work_minutes' => 540,
            'marked_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('modules.attendance.update', $attendance), [
                'user_id' => $employee->id,
                'attendance_date' => '2026-02-15',
                'status' => Attendance::STATUS_HALF_DAY,
                'check_in_time' => '10:00',
                'check_out_time' => '14:00',
                'notes' => 'Half day approved',
            ])
            ->assertRedirect(route('modules.attendance.overview'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => Attendance::STATUS_HALF_DAY,
            'work_minutes' => 240,
            'notes' => 'Half day approved',
        ]);
    }
}
