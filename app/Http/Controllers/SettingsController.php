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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    /**
     * @return list<string>
     */
    private function fields(): array
    {
        return [
            'company_name',
            'company_code',
            'company_email',
            'company_phone',
            'company_website',
            'tax_id',
            'timezone',
            'currency',
            'financial_year_start_month',
            'company_address',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'company_name' => config('app.name'),
            'company_code' => null,
            'company_email' => config('mail.from.address'),
            'company_phone' => null,
            'company_website' => config('app.url'),
            'tax_id' => null,
            'timezone' => (string) config('app.timezone', 'UTC'),
            'currency' => 'USD',
            'financial_year_start_month' => 4,
            'company_address' => null,
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
        ]);

        $validated['currency'] = strtoupper($validated['currency']);

        $settings = CompanySetting::query()->firstOrNew([]);
        $settings->fill($validated);
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
}
