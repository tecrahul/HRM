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

        $payload = $this->baseCompanySettingsPayload();

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
        $this->assertSame('Northwind HR LLC', $settings->legal_entity_name);
        $this->assertSame('en_US', $settings->locale);
        $this->assertIsArray($settings->branch_directory);
        $this->assertCount(1, $settings->branch_directory);
        $this->assertSame('HQ', $settings->branch_directory[0]['label']);
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
                'financial_year_start_month' => 4,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_toggle_signup_and_password_reset(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('settings.company.update'), [
                ...$this->baseCompanySettingsPayload(),
                'company_name' => 'Security Config Inc',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'financial_year_start_month' => 4,
                'financial_year_start_day' => 1,
                'financial_year_end_month' => 3,
                'financial_year_end_day' => 31,
                'signup_enabled' => '1',
                'two_factor_enabled' => '1',
            ]);

        $response
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('status', 'Company details updated successfully.');

        $this->assertDatabaseHas('company_settings', [
            'company_name' => 'Security Config Inc',
            'signup_enabled' => 1,
            'password_reset_enabled' => 0,
            'two_factor_enabled' => 1,
        ]);
    }

    public function test_admin_can_update_system_settings_from_system_section(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->actingAs($user)->post(route('settings.company.update'), $this->baseCompanySettingsPayload());

        $response = $this
            ->actingAs($user)
            ->post(route('settings.company.update'), [
                'settings_section' => 'system',
                'timezone' => 'Europe/London',
                'locale' => 'en_GB',
                'default_country' => 'GB',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'currency' => 'gbp',
                'financial_year_start_month' => 1,
                'financial_year_start_day' => 1,
                'financial_year_end_month' => 12,
                'financial_year_end_day' => 31,
                'signup_enabled' => '1',
                'password_reset_enabled' => '1',
            ]);

        $response
            ->assertRedirect(route('settings.index', ['section' => 'system']))
            ->assertSessionHas('status', 'System settings updated successfully.');

        $this->assertDatabaseHas('company_settings', [
            'company_name' => 'Northwind HR',
            'timezone' => 'Europe/London',
            'locale' => 'en_GB',
            'default_country' => 'GB',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'currency' => 'GBP',
            'signup_enabled' => 1,
            'password_reset_enabled' => 1,
            'two_factor_enabled' => 0,
        ]);
    }

    public function test_admin_can_update_company_settings_from_company_section_without_overwriting_system_settings(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->actingAs($user)->post(route('settings.company.update'), $this->baseCompanySettingsPayload());

        $response = $this
            ->actingAs($user)
            ->post(route('settings.company.update'), [
                'settings_section' => 'company',
                'company_name' => 'Contoso People Ops',
                'brand_primary_color' => '#112233',
                'brand_secondary_color' => '#445566',
                'brand_font_family' => 'manrope',
                'company_code' => 'CONTOSO',
            ]);

        $response
            ->assertRedirect(route('settings.index', ['section' => 'company']))
            ->assertSessionHas('status', 'Company details updated successfully.');

        $this->assertDatabaseHas('company_settings', [
            'company_name' => 'Contoso People Ops',
            'company_code' => 'CONTOSO',
            'brand_primary_color' => '#112233',
            'brand_secondary_color' => '#445566',
            'timezone' => 'America/Chicago',
            'currency' => 'USD',
            'signup_enabled' => 0,
            'password_reset_enabled' => 0,
            'two_factor_enabled' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseCompanySettingsPayload(): array
    {
        return [
            'company_name' => 'Northwind HR',
            'legal_entity_name' => 'Northwind HR LLC',
            'legal_entity_type' => 'llc',
            'registration_number' => 'NW-001',
            'incorporation_country' => 'US',
            'brand_tagline' => 'People First Ops',
            'brand_primary_color' => '#7C3AED',
            'brand_secondary_color' => '#5EEAD4',
            'brand_font_family' => 'manrope',
            'company_code' => 'NORTH-001',
            'company_email' => 'hello@northwind.test',
            'company_phone' => '+1 (222) 333-4444',
            'company_website' => 'https://northwind.test',
            'tax_id' => '11-1111111',
            'timezone' => 'America/Chicago',
            'locale' => 'en_US',
            'default_country' => 'US',
            'date_format' => 'M j, Y',
            'time_format' => 'h:i A',
            'currency' => 'usd',
            'financial_year_start_month' => 4,
            'financial_year_start_day' => 1,
            'financial_year_end_month' => 3,
            'financial_year_end_day' => 31,
            'company_address' => '42 Lake Avenue, Chicago, IL',
            'branch_directory' => [
                [
                    'label' => 'HQ',
                    'code' => 'HQ',
                    'phone' => '+1 (222) 333-4444',
                    'timezone' => 'America/Chicago',
                    'address' => '42 Lake Avenue, Chicago, IL',
                    'is_primary' => true,
                ],
            ],
        ];
    }
}
