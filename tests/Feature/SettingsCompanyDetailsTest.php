<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsCompanyDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_company_details(): void
    {
        $response = $this->post(route('settings.company.update'), [
            'company_name' => 'Test Company',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_company_details(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $payload = [
            'company_name' => 'Northwind HR',
            'company_code' => 'NORTH-001',
            'company_email' => 'hello@northwind.test',
            'company_phone' => '+1 (222) 333-4444',
            'company_website' => 'https://northwind.test',
            'tax_id' => '11-1111111',
            'timezone' => 'America/Chicago',
            'currency' => 'usd',
            'company_address' => '42 Lake Avenue, Chicago, IL',
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('settings.company.update'), $payload);

        $response
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('status', 'Company details updated successfully.');

        $this->assertDatabaseHas('company_settings', [
            'company_name' => 'Northwind HR',
            'company_code' => 'NORTH-001',
            'company_email' => 'hello@northwind.test',
            'timezone' => 'America/Chicago',
            'currency' => 'USD',
        ]);

        $settings = CompanySetting::query()->first();
        $this->assertNotNull($settings);
        $this->assertSame('42 Lake Avenue, Chicago, IL', $settings->company_address);
    }

    public function test_non_admin_user_cannot_update_company_details(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::HR->value,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('settings.company.update'), [
                'company_name' => 'Restricted Update',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
            ]);

        $response->assertForbidden();
    }
}
