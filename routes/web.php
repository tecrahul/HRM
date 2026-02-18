<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\EmployeeSearchController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeOnboardingController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollModuleController;
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
    Route::middleware('auth-feature:two-factor')->group(function (): void {
        Route::get('/two-factor-challenge', [AuthController::class, 'showTwoFactorChallengeForm'])
            ->name('two-factor.challenge');
        Route::post('/two-factor-challenge', [AuthController::class, 'completeTwoFactorChallenge'])
            ->name('two-factor.challenge.attempt');
    });

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
        ->middleware('role:super_admin,admin,hr')
        ->name('settings.index');
    Route::post('/settings/company-details', [SettingsController::class, 'updateCompanyDetails'])
        ->middleware('role:super_admin,admin')
        ->name('settings.company.update');
    Route::get('/settings/company-logo', [SettingsController::class, 'companyLogo'])
        ->name('settings.company.logo');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::put('/notifications/{notification}/unread', [NotificationController::class, 'markUnread'])->name('notifications.unread');
    Route::get('/api/dashboard/admin/summary', [DashboardController::class, 'adminSummary'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('api.dashboard.admin.summary');
    Route::get('/api/employees/search', [EmployeeSearchController::class, 'search'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('api.employees.search');
    Route::get('/api/employees/{user}/setup-status', [EmployeeOnboardingController::class, 'setupStatus'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('api.employees.setup-status');
    Route::post('/api/employees/{user}/send-login', [EmployeeOnboardingController::class, 'sendLogin'])
        ->middleware('role:super_admin,admin,hr')
        ->name('api.employees.send-login');
    Route::get('/api/branches', [PayrollModuleController::class, 'branchesApi'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('api.branches.list');
    Route::get('/api/departments', [PayrollModuleController::class, 'departmentsApi'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('api.departments.list');
    Route::prefix('api/payroll')->name('api.payroll.')->middleware('role:super_admin,admin,hr,finance')->group(function (): void {
        Route::get('/dashboard/summary', [PayrollModuleController::class, 'dashboardSummaryApi'])->name('dashboard.summary');
        Route::get('/dashboard/alerts', [PayrollModuleController::class, 'dashboardAlertsApi'])->name('dashboard.alerts');
        Route::get('/dashboard/activity', [PayrollModuleController::class, 'dashboardActivityApi'])->name('dashboard.activity');
        Route::get('/salary-structures', [PayrollModuleController::class, 'salaryStructuresApi'])->name('salary-structures');
        Route::get('/history', [PayrollModuleController::class, 'payrollHistoryApi'])->name('history');
    });
    Route::get('/api/dashboard/admin/attendance-overview', [DashboardController::class, 'adminAttendanceOverview'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('api.dashboard.admin.attendance-overview');
    Route::get('/api/dashboard/admin/leave-overview', [DashboardController::class, 'adminLeaveOverview'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('api.dashboard.admin.leave-overview');

    Route::prefix('modules')->name('modules.')->middleware('role:super_admin,admin,hr,employee,finance')->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/departments', [DepartmentController::class, 'index'])
            ->middleware('role:super_admin,admin,hr')
            ->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])
            ->middleware('role:super_admin,admin,hr')
            ->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->middleware('role:super_admin,admin,hr')
            ->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])
            ->middleware('role:super_admin,admin,hr')
            ->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('role:super_admin,admin,hr')
            ->name('departments.destroy');
        Route::get('/branches', [BranchController::class, 'index'])
            ->middleware('role:super_admin,admin')
            ->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])
            ->middleware('role:super_admin,admin')
            ->name('branches.store');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])
            ->middleware('role:super_admin,admin')
            ->name('branches.edit');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])
            ->middleware('role:super_admin,admin')
            ->name('branches.update');
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])
            ->middleware('role:super_admin,admin')
            ->name('branches.destroy');
        Route::get('/holidays', [HolidayController::class, 'index'])
            ->name('holidays.index');
        Route::post('/holidays', [HolidayController::class, 'store'])
            ->middleware('role:super_admin,admin,hr')
            ->name('holidays.store');
        Route::get('/holidays/{holiday}/edit', [HolidayController::class, 'edit'])
            ->middleware('role:super_admin,admin,hr')
            ->name('holidays.edit');
        Route::put('/holidays/{holiday}', [HolidayController::class, 'update'])
            ->middleware('role:super_admin,admin,hr')
            ->name('holidays.update');
        Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])
            ->middleware('role:super_admin,admin,hr')
            ->name('holidays.destroy');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])
            ->middleware('role:super_admin,admin,hr')
            ->name('attendance.store');
        Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])
            ->middleware('role:super_admin,admin,hr')
            ->name('attendance.edit');
        Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])
            ->middleware('role:super_admin,admin,hr')
            ->name('attendance.update');
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
            ->middleware('role:employee')
            ->name('attendance.check-in');
        Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
            ->middleware('role:employee')
            ->name('attendance.check-out');
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::prefix('/payroll')->name('payroll.')->middleware('role:super_admin,admin,hr,finance')->group(function (): void {
            Route::get('/dashboard', [PayrollModuleController::class, 'dashboard'])->name('dashboard');
            Route::get('/salary-structures', [PayrollModuleController::class, 'salaryStructures'])->name('salary-structures');
            Route::get('/processing', [PayrollModuleController::class, 'processing'])->name('processing');
            Route::get('/history', [PayrollModuleController::class, 'history'])->name('history');
            Route::get('/payslips', [PayrollModuleController::class, 'payslips'])->name('payslips');
            Route::get('/reports', [PayrollModuleController::class, 'reports'])->name('reports');
            Route::get('/settings', [PayrollModuleController::class, 'settings'])->name('settings');
        });
        Route::get('/communication', [CommunicationController::class, 'index'])->name('communication.index');
        Route::post('/communication/direct', [CommunicationController::class, 'sendDirectMessage'])
            ->name('communication.direct.send');
        Route::post('/communication/broadcast', [CommunicationController::class, 'sendBroadcast'])
            ->middleware('role:super_admin,admin,hr')
            ->name('communication.broadcast.send');
        Route::post('/communication/broadcast/all', [CommunicationController::class, 'sendBroadcastAll'])
            ->middleware('role:super_admin,admin,hr')
            ->name('communication.broadcast.all');
        Route::post('/communication/broadcast/team', [CommunicationController::class, 'sendBroadcastTeam'])
            ->middleware('role:employee')
            ->name('communication.broadcast.team');
        Route::post('/communication/broadcast/targeted', [CommunicationController::class, 'sendTargetedBroadcast'])
            ->middleware('role:super_admin,admin')
            ->name('communication.broadcast.targeted');
        Route::put('/communication/messages/{message}/read', [CommunicationController::class, 'markRead'])
            ->name('communication.messages.read');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/activity', [ReportController::class, 'activity'])->name('reports.activity');
        Route::get('/reports/activity/{activity}', [ReportController::class, 'activityShow'])->name('reports.activity.show');
        Route::post('/payroll/structure', [PayrollController::class, 'storeStructure'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.structure.store');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.generate');
        Route::post('/payroll/workflow/preview', [PayrollController::class, 'previewWorkflow'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.workflow.preview');
        Route::post('/payroll/workflow/generate', [PayrollController::class, 'generateWorkflow'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.workflow.generate');
        Route::put('/payroll/workflow/{payroll}/approve', [PayrollController::class, 'approveWorkflow'])
            ->middleware('role:super_admin,admin')
            ->name('payroll.workflow.approve');
        Route::put('/payroll/workflow/{payroll}/pay', [PayrollController::class, 'markPaidWorkflow'])
            ->middleware('role:super_admin,admin,finance')
            ->name('payroll.workflow.pay');
        Route::put('/payroll/structure/{user}', [PayrollController::class, 'upsertStructureApi'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.structure.upsert');
        Route::get('/payroll/structure/{user}/history', [PayrollController::class, 'structureHistoryApi'])
            ->middleware('role:super_admin,admin,hr,finance')
            ->name('payroll.structure.history');
        Route::post('/payroll/directory/bulk-action', [PayrollController::class, 'bulkDirectoryAction'])
            ->middleware('role:super_admin,admin,hr,finance')
            ->name('payroll.directory.bulk-action');
        Route::get('/payroll/directory/export-csv', [PayrollController::class, 'exportDirectoryCsv'])
            ->middleware('role:super_admin,admin,hr,finance')
            ->name('payroll.directory.export-csv');
        Route::get('/payroll/workflow/overview', [PayrollController::class, 'workflowOverviewApi'])
            ->middleware('role:super_admin,admin,hr,finance')
            ->name('payroll.workflow.overview');
        Route::post('/payroll/workflow/preview-batch', [PayrollController::class, 'workflowPreviewBatchApi'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.workflow.preview-batch');
        Route::post('/payroll/workflow/generate-batch', [PayrollController::class, 'workflowGenerateBatchApi'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.workflow.generate-batch');
        Route::post('/payroll/workflow/approve-batch', [PayrollController::class, 'workflowApproveBatchApi'])
            ->middleware('role:super_admin,admin')
            ->name('payroll.workflow.approve-batch');
        Route::post('/payroll/workflow/pay-close', [PayrollController::class, 'workflowPayCloseApi'])
            ->middleware('role:super_admin,admin,finance')
            ->name('payroll.workflow.pay-close');
        Route::post('/payroll/workflow/unlock', [PayrollController::class, 'workflowUnlockApi'])
            ->middleware('role:super_admin')
            ->name('payroll.workflow.unlock');
        Route::post('/payroll/generate-bulk', [PayrollController::class, 'generateBulk'])
            ->middleware('role:super_admin,admin,hr')
            ->name('payroll.generate-bulk');
        Route::put('/payroll/{payroll}/status', [PayrollController::class, 'updateStatus'])
            ->middleware('role:super_admin,admin,hr,finance')
            ->name('payroll.status.update');
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::get('/leave/{leaveRequest}/review', [LeaveController::class, 'reviewPage'])
            ->middleware('role:super_admin,admin,hr')
            ->name('leave.review.form');
        Route::put('/leave/{leaveRequest}/review', [LeaveController::class, 'review'])
            ->middleware('role:super_admin,admin,hr')
            ->name('leave.review');
        Route::delete('/leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])
            ->middleware('role:employee')
            ->name('leave.cancel');
    });

    Route::get('/employees/{user}/overview', [EmployeeOnboardingController::class, 'overview'])
        ->middleware('role:super_admin,admin,hr,finance')
        ->name('employees.overview');

    Route::prefix('admin')->name('admin.')->middleware('role:super_admin,admin,hr')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::resource('users', UserManagementController::class)->except(['show']);
    });

    Route::prefix('hr')->name('hr.')->middleware('role:hr')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'hr'])->name('dashboard');
    });

    Route::prefix('finance')->name('finance.')->middleware('role:finance')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    });

    Route::prefix('employee')->name('employee.')->middleware('role:employee')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'employee'])->name('dashboard');
    });
});
