<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_routes_are_disabled_by_default(): void
    {
        $this->get(route('register'))->assertNotFound();

        $this->post(route('register.attempt'), [
            'name' => 'New User',
            'email' => 'new.user@example.test',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertNotFound();
    }

    public function test_guest_can_signup_when_admin_enables_signup(): void
    {
        $this->createCompanySettings([
            'signup_enabled' => true,
        ]);

        $response = $this->post(route('register.attempt'), [
            'name' => 'Signup User',
            'email' => 'signup.user@example.test',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->firstWhere('email', 'signup.user@example.test');
        $this->assertNotNull($user);
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        $this->assertSame(UserRole::EMPLOYEE->value, $role);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);
    }

    public function test_guest_can_request_password_reset_email_when_enabled(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'If an account exists for that email, a reset link has been sent.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_routes_are_blocked_when_admin_disables_reset(): void
    {
        $this->createCompanySettings([
            'password_reset_enabled' => false,
        ]);

        $this->get(route('password.request'))->assertNotFound();
        $this->post(route('password.email'), ['email' => 'any@example.test'])->assertNotFound();
        $this->get(route('password.reset', ['token' => 'placeholder-token']))->assertNotFound();
        $this->post(route('password.update'), [
            'token' => 'placeholder-token',
            'email' => 'any@example.test',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertNotFound();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createCompanySettings(array $overrides = []): CompanySetting
    {
        return CompanySetting::query()->create(array_merge([
            'company_name' => 'Demo Company',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'financial_year_start_month' => 4,
            'signup_enabled' => false,
            'password_reset_enabled' => true,
        ], $overrides));
    }
}
