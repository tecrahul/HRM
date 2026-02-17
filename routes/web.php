<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\SyncRoleNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return auth()->check()
        ? redirect()->route(auth()->user()?->dashboardRouteName() ?? 'login')
        : redirect()->route('login');
});

Route::get('/branding/company-logo', [SettingsController::class, 'companyLogo'])
    ->name('branding.company.logo');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/two-factor-challenge', [AuthController::class, 'showTwoFactorChallengeForm'])
        ->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [AuthController::class, 'completeTwoFactorChallenge'])
        ->name('two-factor.challenge.attempt');

    Route::middleware('auth-feature:signup')->group(function (): void {
        Route::get('/signup', [AuthController::class, 'showSignupForm'])->name('register');
        Route::post('/signup', [AuthController::class, 'register'])->name('register.attempt');
    });

    Route::middleware('auth-feature:password-reset')->group(function (): void {
        Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    });
});

Route::middleware(['auth', SyncRoleNotifications::class])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function (): RedirectResponse {
        return redirect()->route(auth()->user()?->dashboardRouteName() ?? 'login');
    })->name('dashboard');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/two-factor/enable', [ProfileController::class, 'enableTwoFactor'])
        ->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/disable', [ProfileController::class, 'disableTwoFactor'])
        ->name('profile.two-factor.disable');
    Route::post('/profile/two-factor/recovery-codes/regenerate', [ProfileController::class, 'regenerateTwoFactorRecoveryCodes'])
        ->name('profile.two-factor.recovery-codes.regenerate');
    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('role:admin,hr')
        ->name('settings.index');
    Route::post('/settings/company-details', [SettingsController::class, 'updateCompanyDetails'])
        ->middleware('role:admin')
        ->name('settings.company.update');
    Route::get('/settings/company-logo', [SettingsController::class, 'companyLogo'])
        ->name('settings.company.logo');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::put('/notifications/{notification}/unread', [NotificationController::class, 'markUnread'])->name('notifications.unread');

    Route::prefix('modules')->name('modules.')->middleware('role:admin,hr,employee')->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/departments', [DepartmentController::class, 'index'])
            ->middleware('role:admin,hr')
            ->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])
            ->middleware('role:admin,hr')
            ->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->middleware('role:admin,hr')
            ->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])
            ->middleware('role:admin,hr')
            ->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('role:admin,hr')
            ->name('departments.destroy');
        Route::get('/branches', [BranchController::class, 'index'])
            ->middleware('role:admin')
            ->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])
            ->middleware('role:admin')
            ->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])
            ->middleware('role:admin')
            ->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])
            ->middleware('role:admin')
            ->name('branches.update');
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('branches.destroy');
        Route::get('/holidays', [HolidayController::class, 'index'])
            ->name('holidays.index');
        Route::post('/holidays', [HolidayController::class, 'store'])
            ->middleware('role:admin,hr')
            ->name('holidays.store');
        Route::get('/holidays/{holiday}/edit', [HolidayController::class, 'edit'])
            ->middleware('role:admin,hr')
            ->name('holidays.edit');
        Route::put('/holidays/{holiday}', [HolidayController::class, 'update'])
            ->middleware('role:admin,hr')
            ->name('holidays.update');
        Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])
            ->middleware('role:admin,hr')
            ->name('holidays.destroy');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])
            ->middleware('role:admin,hr')
            ->name('attendance.store');
        Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])
            ->middleware('role:admin,hr')
            ->name('attendance.edit');
        Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])
            ->middleware('role:admin,hr')
            ->name('attendance.update');
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
            ->middleware('role:employee')
            ->name('attendance.check-in');
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
            ->middleware('role:employee')
            ->name('attendance.check-out');
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/activity', [ReportController::class, 'activity'])->name('reports.activity');
        Route::get('/reports/activity/{activity}', [ReportController::class, 'activityShow'])->name('reports.activity.show');
        Route::post('/payroll/structure', [PayrollController::class, 'storeStructure'])
            ->middleware('role:admin,hr')
            ->name('payroll.structure.store');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])
            ->middleware('role:admin,hr')
            ->name('payroll.generate');
        Route::post('/payroll/generate-bulk', [PayrollController::class, 'generateBulk'])
            ->middleware('role:admin,hr')
            ->name('payroll.generate-bulk');
        Route::put('/payroll/{payroll}/status', [PayrollController::class, 'updateStatus'])
            ->middleware('role:admin,hr')
            ->name('payroll.status.update');
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::get('/leave/{leaveRequest}/review', [LeaveController::class, 'reviewPage'])
            ->middleware('role:admin,hr')
            ->name('leave.review.form');
        Route::put('/leave/{leaveRequest}/review', [LeaveController::class, 'review'])
            ->middleware('role:admin,hr')
            ->name('leave.review');
        Route::delete('/leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])
            ->middleware('role:employee')
            ->name('leave.cancel');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin,hr')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::resource('users', UserManagementController::class)->except(['show']);
    });

    Route::prefix('hr')->name('hr.')->middleware('role:hr')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'hr'])->name('dashboard');
    });

    Route::prefix('employee')->name('employee.')->middleware('role:employee')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'employee'])->name('dashboard');
    });
});
