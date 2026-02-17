<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\TwoFactorAuthenticator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_two_factor_from_profile_page(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
            'password' => Hash::make('StrongPass123!'),
        ]);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();

        $pendingSecret = (string) session('profile.two_factor.pending_secret');
        $this->assertNotSame('', $pendingSecret);

        $code = app(TwoFactorAuthenticator::class)->currentCode($pendingSecret);
        $response = $this->actingAs($user)->post(route('profile.two-factor.enable'), [
            'current_password' => 'StrongPass123!',
            'code' => $code,
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('two_factor_status', 'Two-factor authentication enabled successfully.')
            ->assertSessionHas('two_factor_recovery_codes');

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertNotEmpty($user->twoFactorRecoveryCodeHashes());
    }

    public function test_login_is_redirected_to_two_factor_challenge_for_enabled_user(): void
    {
        $twoFactorAuthenticator = app(TwoFactorAuthenticator::class);
        $secret = $twoFactorAuthenticator->generateSecret();

        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
            'email' => 'two-factor@example.test',
            'password' => Hash::make('StrongPass123!'),
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);
        $user->replaceTwoFactorRecoveryCodes(['ABCD-EFGH']);
        $user->save();

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'StrongPass123!',
            'remember' => '1',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame($user->id, session('auth.two_factor.user_id'));

        $challengeCode = $twoFactorAuthenticator->currentCode($secret);
        $this->post(route('two-factor.challenge.attempt'), [
            'code' => $challengeCode,
        ])->assertRedirect(route('employee.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_recovery_code_and_it_is_consumed(): void
    {
        $secret = app(TwoFactorAuthenticator::class)->generateSecret();
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
            'email' => 'recovery@example.test',
            'password' => Hash::make('StrongPass123!'),
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);
        $user->replaceTwoFactorRecoveryCodes(['WXYZ-1234']);
        $user->save();

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.challenge.attempt'), [
            'code' => 'WXYZ-1234',
        ])->assertRedirect(route('employee.dashboard'));

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertSame([], $user->twoFactorRecoveryCodeHashes());
    }

    public function test_invalid_two_factor_code_keeps_user_guest(): void
    {
        $secret = app(TwoFactorAuthenticator::class)->generateSecret();
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE->value,
            'email' => 'invalid-code@example.test',
            'password' => Hash::make('StrongPass123!'),
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.challenge.attempt'), [
            'code' => '999999',
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
    }
}
