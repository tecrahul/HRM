<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_communication_page(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
        ]);
        $this->createProfile($employee);

        $this->actingAs($employee)
            ->get(route('modules.communication.index'))
            ->assertOk();
    }

    public function test_message_box_inbox_supports_all_read_and_unread_filters(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $sender = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $this->createProfile($viewer);
        $this->createProfile($sender);

        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_DIRECT,
            'created_by_user_id' => $sender->id,
            'direct_user_low_id' => min($sender->id, $viewer->id),
            'direct_user_high_id' => max($sender->id, $viewer->id),
            'last_message_at' => now(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $viewer->id,
            'message' => 'INBOX_READ_MESSAGE',
            'is_broadcast' => false,
            'read_status' => true,
            'read_at' => now(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $viewer->id,
            'message' => 'INBOX_UNREAD_MESSAGE',
            'is_broadcast' => false,
            'read_status' => false,
            'read_at' => null,
        ]);

        $this->actingAs($viewer)
            ->get(route('modules.communication.index', ['tab' => 'inbox', 'status' => 'all']))
            ->assertOk()
            ->assertSeeText('INBOX_READ_MESSAGE')
            ->assertSeeText('INBOX_UNREAD_MESSAGE');

        $this->actingAs($viewer)
            ->get(route('modules.communication.index', ['tab' => 'inbox', 'status' => 'read']))
            ->assertOk()
            ->assertSeeText('INBOX_READ_MESSAGE')
            ->assertDontSeeText('INBOX_UNREAD_MESSAGE');

        $this->actingAs($viewer)
            ->get(route('modules.communication.index', ['tab' => 'inbox', 'status' => 'unread']))
            ->assertOk()
            ->assertSeeText('INBOX_UNREAD_MESSAGE')
            ->assertDontSeeText('INBOX_READ_MESSAGE');
    }

    public function test_message_box_sent_supports_all_read_and_unread_filters(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $recipient = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $this->createProfile($viewer);
        $this->createProfile($recipient);

        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_DIRECT,
            'created_by_user_id' => $viewer->id,
            'direct_user_low_id' => min($viewer->id, $recipient->id),
            'direct_user_high_id' => max($viewer->id, $recipient->id),
            'last_message_at' => now(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $viewer->id,
            'receiver_id' => $recipient->id,
            'message' => 'SENT_READ_MESSAGE',
            'is_broadcast' => false,
            'read_status' => true,
            'read_at' => now(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $viewer->id,
            'receiver_id' => $recipient->id,
            'message' => 'SENT_UNREAD_MESSAGE',
            'is_broadcast' => false,
            'read_status' => false,
            'read_at' => null,
        ]);

        $this->actingAs($viewer)
            ->get(route('modules.communication.index', ['tab' => 'sent', 'status' => 'all']))
            ->assertOk()
            ->assertSeeText('SENT_READ_MESSAGE')
            ->assertSeeText('SENT_UNREAD_MESSAGE');

        $this->actingAs($viewer)
            ->get(route('modules.communication.index', ['tab' => 'sent', 'status' => 'read']))
            ->assertOk()
            ->assertSeeText('SENT_READ_MESSAGE')
            ->assertDontSeeText('SENT_UNREAD_MESSAGE');

        $this->actingAs($viewer)
            ->get(route('modules.communication.index', ['tab' => 'sent', 'status' => 'unread']))
            ->assertOk()
            ->assertSeeText('SENT_UNREAD_MESSAGE')
            ->assertDontSeeText('SENT_READ_MESSAGE');
    }

    public function test_admin_can_send_direct_message_to_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $this->createProfile($admin);
        $this->createProfile($employee);

        $this->actingAs($admin)
            ->post(route('modules.communication.direct.send'), [
                'receiver_id' => $employee->id,
                'message' => 'Admin note',
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $employee->id,
            'message' => 'Admin note',
        ]);
    }

    public function test_admin_cannot_send_direct_message_to_hr(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $hr = User::factory()->create(['role' => UserRole::HR->value]);
        $this->createProfile($admin);
        $this->createProfile($hr);

        $this->actingAs($admin)
            ->post(route('modules.communication.direct.send'), [
                'receiver_id' => $hr->id,
                'message' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_supervisor_can_message_direct_report_only(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $directReport = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $otherEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($supervisor);
        $this->createProfile($directReport, $supervisor->id);
        $this->createProfile($otherEmployee);

        $this->actingAs($supervisor)
            ->post(route('modules.communication.direct.send'), [
                'receiver_id' => $directReport->id,
                'message' => 'Team update',
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->actingAs($supervisor)
            ->post(route('modules.communication.direct.send'), [
                'receiver_id' => $otherEmployee->id,
                'message' => 'Unauthorized',
            ])
            ->assertForbidden();
    }

    public function test_employee_can_message_hr_admin_and_direct_supervisor_but_not_peer(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $hr = User::factory()->create(['role' => UserRole::HR->value]);
        $supervisor = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $peer = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($admin);
        $this->createProfile($hr);
        $this->createProfile($supervisor);
        $this->createProfile($employee, $supervisor->id);
        $this->createProfile($peer);

        $this->actingAs($employee)->post(route('modules.communication.direct.send'), [
            'receiver_id' => $admin->id,
            'message' => 'To admin',
        ])->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->actingAs($employee)->post(route('modules.communication.direct.send'), [
            'receiver_id' => $hr->id,
            'message' => 'To hr',
        ])->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->actingAs($employee)->post(route('modules.communication.direct.send'), [
            'receiver_id' => $supervisor->id,
            'message' => 'To supervisor',
        ])->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->actingAs($employee)->post(route('modules.communication.direct.send'), [
            'receiver_id' => $peer->id,
            'message' => 'To peer',
        ])->assertForbidden();
    }

    public function test_only_admin_or_hr_can_broadcast_all(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $anotherEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $this->createProfile($admin);
        $this->createProfile($employee);
        $this->createProfile($anotherEmployee);

        $this->actingAs($employee)
            ->post(route('modules.communication.broadcast.all'), [
                'subject' => 'Bad',
                'message' => 'Not allowed',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('modules.communication.broadcast.all'), [
                'subject' => 'Announcement',
                'message' => 'Hello team',
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertSame(2, Message::query()->where('sender_id', $admin->id)->count());
    }

    public function test_admin_unified_broadcast_defaults_to_all_targets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $employeeA = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employeeB = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($admin);
        $this->createProfile($employeeA, branch: 'New York', department: 'Engineering');
        $this->createProfile($employeeB, branch: 'Chicago', department: 'Operations');

        $this->actingAs($admin)
            ->post(route('modules.communication.broadcast.send'), [
                'subject' => 'Company Update',
                'message' => 'Unified broadcast to all',
                'target_branch' => 'all',
                'target_team' => 'all',
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $employeeA->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $employeeB->id,
            'is_broadcast' => 1,
        ]);
    }

    public function test_admin_unified_broadcast_filters_by_branch_team_and_selected_employees(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $branchTeamEmployeeA = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $branchTeamEmployeeB = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $sameBranchDifferentTeam = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $differentBranchEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($admin);
        $this->createProfile($branchTeamEmployeeA, branch: 'New York', department: 'Engineering');
        $this->createProfile($branchTeamEmployeeB, branch: 'New York', department: 'Engineering');
        $this->createProfile($sameBranchDifferentTeam, branch: 'New York', department: 'Sales');
        $this->createProfile($differentBranchEmployee, branch: 'Chicago', department: 'Engineering');

        $this->actingAs($admin)
            ->post(route('modules.communication.broadcast.send'), [
                'subject' => 'Engineering NY',
                'message' => 'Selected broadcast',
                'target_branch' => 'New York',
                'target_team' => 'Engineering',
                'employee_ids' => [$branchTeamEmployeeB->id],
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseMissing('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $branchTeamEmployeeA->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $branchTeamEmployeeB->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $sameBranchDifferentTeam->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $differentBranchEmployee->id,
            'is_broadcast' => 1,
        ]);
    }

    public function test_hr_can_use_unified_broadcast_with_manual_selection(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR->value]);
        $employeeA = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employeeB = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($hr);
        $this->createProfile($employeeA, branch: 'Dallas', department: 'HR Ops');
        $this->createProfile($employeeB, branch: 'Dallas', department: 'HR Ops');

        $this->actingAs($hr)
            ->post(route('modules.communication.broadcast.send'), [
                'subject' => 'HR Update',
                'message' => 'Selected employee only',
                'target_branch' => 'Dallas',
                'target_team' => 'HR Ops',
                'employee_ids' => [$employeeA->id],
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $hr->id,
            'receiver_id' => $employeeA->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $hr->id,
            'receiver_id' => $employeeB->id,
            'is_broadcast' => 1,
        ]);
    }

    public function test_supervisor_team_broadcast_only_reaches_direct_reports(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $directReportA = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $directReportB = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $otherEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($supervisor);
        $this->createProfile($directReportA, $supervisor->id);
        $this->createProfile($directReportB, $supervisor->id);
        $this->createProfile($otherEmployee);

        $this->actingAs($supervisor)
            ->post(route('modules.communication.broadcast.team'), [
                'subject' => 'Team',
                'message' => 'Standup at 10',
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $supervisor->id,
            'receiver_id' => $directReportA->id,
            'is_broadcast' => 1,
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $supervisor->id,
            'receiver_id' => $directReportB->id,
            'is_broadcast' => 1,
        ]);

        $this->assertDatabaseMissing('messages', [
            'sender_id' => $supervisor->id,
            'receiver_id' => $otherEmployee->id,
            'is_broadcast' => 1,
        ]);
    }

    public function test_admin_can_send_targeted_broadcast_by_branch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $nyEmployeeA = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $nyEmployeeB = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $laEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($admin);
        $this->createProfile($nyEmployeeA, branch: 'New York');
        $this->createProfile($nyEmployeeB, branch: 'New York');
        $this->createProfile($laEmployee, branch: 'Los Angeles');

        $this->actingAs($admin)
            ->post(route('modules.communication.broadcast.targeted'), [
                'subject' => 'NY Branch Update',
                'message' => 'Message for NY only',
                'target_branch' => 'New York',
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $nyEmployeeA->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $nyEmployeeB->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $laEmployee->id,
            'is_broadcast' => 1,
        ]);
    }

    public function test_admin_can_send_targeted_broadcast_by_team_department(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $engineeringEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $productEmployee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($admin);
        $this->createProfile($engineeringEmployee, department: 'Engineering');
        $this->createProfile($productEmployee, department: 'Product');

        $this->actingAs($admin)
            ->post(route('modules.communication.broadcast.targeted'), [
                'subject' => 'Engineering Update',
                'message' => 'Message for Engineering',
                'target_team' => 'Engineering',
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $engineeringEmployee->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $productEmployee->id,
            'is_broadcast' => 1,
        ]);
    }

    public function test_admin_can_send_targeted_broadcast_to_multiple_selected_employees(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $employeeA = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employeeB = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $employeeC = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);

        $this->createProfile($admin);
        $this->createProfile($employeeA);
        $this->createProfile($employeeB);
        $this->createProfile($employeeC);

        $this->actingAs($admin)
            ->post(route('modules.communication.broadcast.targeted'), [
                'subject' => 'Selected Users',
                'message' => 'Message for selected users',
                'employee_ids' => [$employeeA->id, $employeeC->id],
            ])
            ->assertRedirect(route('modules.communication.index', ['tab' => 'sent']));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $employeeA->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $employeeC->id,
            'is_broadcast' => 1,
        ]);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $admin->id,
            'receiver_id' => $employeeB->id,
            'is_broadcast' => 1,
        ]);
    }

    public function test_non_admin_cannot_send_targeted_broadcast(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR->value]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE->value]);
        $this->createProfile($hr);
        $this->createProfile($employee);

        $this->actingAs($hr)
            ->post(route('modules.communication.broadcast.targeted'), [
                'subject' => 'Not allowed',
                'message' => 'Should fail',
                'employee_ids' => [$employee->id],
            ])
            ->assertForbidden();
    }

    private function createProfile(
        User $user,
        ?int $supervisorUserId = null,
        ?string $branch = null,
        ?string $department = null
    ): void
    {
        UserProfile::query()->create([
            'user_id' => $user->id,
            'employment_type' => 'full_time',
            'status' => 'active',
            'supervisor_user_id' => $supervisorUserId,
            'manager_name' => $supervisorUserId ? User::query()->whereKey($supervisorUserId)->value('name') : null,
            'branch' => $branch,
            'department' => $department,
        ]);
    }
}
