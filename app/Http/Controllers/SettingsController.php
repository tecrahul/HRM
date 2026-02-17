<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingsController extends Controller
{
    /**
     * @return list<string>
     */
    private function fields(): array
    {
        return [
            'company_name',
            'company_logo_path',
            'company_code',
            'company_email',
            'company_phone',
            'company_website',
            'tax_id',
            'timezone',
            'currency',
            'financial_year_start_month',
            'company_address',
            'signup_enabled',
            'password_reset_enabled',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'company_name' => config('app.name'),
            'company_logo_path' => null,
            'company_code' => null,
            'company_email' => config('mail.from.address'),
            'company_phone' => null,
            'company_website' => config('app.url'),
            'tax_id' => null,
            'timezone' => (string) config('app.timezone', 'UTC'),
            'currency' => 'USD',
            'financial_year_start_month' => 4,
            'company_address' => null,
            'signup_enabled' => CompanySetting::DEFAULT_SIGNUP_ENABLED,
            'password_reset_enabled' => CompanySetting::DEFAULT_PASSWORD_RESET_ENABLED,
        ];
    }

    public function index(): View
    {
        $record = CompanySetting::query()->first();
        $companySettings = array_merge($this->defaults(), $record?->only($this->fields()) ?? []);
        $employeeIds = User::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->pluck('id');

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $systemSnapshot = [
            'usersTotal' => User::query()->count(),
            'employeesTotal' => $employeeIds->count(),
            'departmentsTotal' => Department::query()->count(),
            'branchesTotal' => Branch::query()->count(),
            'attendanceMarkedToday' => Attendance::query()
                ->whereIn('user_id', $employeeIds)
                ->whereDate('attendance_date', $today)
                ->count(),
            'leavePending' => LeaveRequest::query()
                ->whereIn('user_id', $employeeIds)
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->count(),
            'payrollGeneratedMonth' => 0,
        ];

        if (Schema::hasTable('payrolls')) {
            $systemSnapshot['payrollGeneratedMonth'] = Payroll::query()
                ->whereIn('user_id', $employeeIds)
                ->whereBetween('payroll_month', [$monthStart, $monthEnd])
                ->count();
        }

        return view('settings.index', [
            'companySettings' => $companySettings,
            'financialYearMonthOptions' => collect(range(1, 12))
                ->mapWithKeys(fn (int $month): array => [$month => Carbon::create(2024, $month, 1)->format('F')])
                ->all(),
            'systemSnapshot' => $systemSnapshot,
            'appMeta' => [
                'appName' => config('app.name'),
                'appUrl' => config('app.url'),
                'appTimezone' => config('app.timezone'),
                'phpVersion' => PHP_VERSION,
                'laravelVersion' => app()->version(),
            ],
        ]);
    }

    public function updateCompanyDetails(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_code' => ['nullable', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:60'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'string', 'size:3'],
            'financial_year_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_company_logo' => ['nullable', 'boolean'],
            'signup_enabled' => ['nullable', 'boolean'],
            'password_reset_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['signup_enabled'] = $request->boolean('signup_enabled');
        $validated['password_reset_enabled'] = $request->boolean('password_reset_enabled');
        $validated['currency'] = strtoupper($validated['currency']);
        unset($validated['company_logo'], $validated['remove_company_logo']);

        $settings = CompanySetting::query()->firstOrNew([]);
        $settings->fill($validated);

        if ($request->boolean('remove_company_logo') && filled($settings->company_logo_path)) {
            Storage::disk('public')->delete((string) $settings->company_logo_path);
            $settings->company_logo_path = null;
        }

        if ($request->hasFile('company_logo')) {
            $newLogoPath = $request->file('company_logo')->store('company-logos', 'public');

            if (filled($settings->company_logo_path) && $settings->company_logo_path !== $newLogoPath) {
                Storage::disk('public')->delete((string) $settings->company_logo_path);
            }

            $settings->company_logo_path = $newLogoPath;
        }

        $settings->save();

        ActivityLogger::log(
            $request->user(),
            'settings.company_updated',
            'Company settings updated',
            (string) ($validated['company_name'] ?? config('app.name')),
            '#7c3aed',
            $settings
        );

        return redirect()
            ->route('settings.index')
            ->with('status', 'Company details updated successfully.');
    }

    public function companyLogo(): BinaryFileResponse|Response
    {
        $logoPath = (string) CompanySetting::query()->value('company_logo_path');

        if ($logoPath === '' || ! str_starts_with($logoPath, 'company-logos/')) {
            return response('', 404);
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return response('', 404);
        }

        return response()->file(
            Storage::disk('public')->path($logoPath),
            ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0']
        );
    }
}
