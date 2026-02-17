<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * @return array{brandCompanyName: string, brandLogoUrl: ?string}
     */
    private function brandingPayload(): array
    {
        $brandCompanyName = (string) (CompanySetting::query()->value('company_name') ?: config('app.name'));
        $brandLogoPath = (string) (CompanySetting::query()->value('company_logo_path') ?? '');
        $brandLogoUrl = null;

        if (
            $brandLogoPath !== ''
            && str_starts_with($brandLogoPath, 'company-logos/')
            && Storage::disk('public')->exists($brandLogoPath)
        ) {
            $brandLogoUrl = route('branding.company.logo');
        }

        return [
            'brandCompanyName' => $brandCompanyName,
            'brandLogoUrl' => $brandLogoUrl,
        ];
    }

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->dashboardRouteName() ?? 'login');
        }

        return view('auth.login', array_merge([
            'signupEnabled' => CompanySetting::signupEnabled(),
            'passwordResetEnabled' => CompanySetting::passwordResetEnabled(),
        ], $this->brandingPayload()));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Invalid login credentials.',
            ]);
        }

        $request->session()->regenerate();

        ActivityLogger::log(
            $request->user(),
            'auth.login',
            'User logged in',
            $request->user()?->email,
            '#7c3aed',
            $request->user()
        );

        return redirect()->intended(route($request->user()?->dashboardRouteName() ?? 'login'));
    }

    public function showSignupForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->dashboardRouteName() ?? 'login');
        }

        return view('auth.signup', $this->brandingPayload());
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $newUser = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => UserRole::EMPLOYEE->value,
                'password' => $validated['password'],
            ]);

            $newUser->profile()->create([
                'employment_type' => 'full_time',
                'status' => 'active',
            ]);

            return $newUser;
        });

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLogger::log(
            $user,
            'auth.signup',
            'User signed up',
            $user->email,
            '#10b981',
            $user
        );

        return redirect()->route($user->dashboardRouteName());
    }

    public function showForgotPasswordForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->dashboardRouteName() ?? 'login');
        }

        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            $email = (string) $request->input('email');

            ActivityLogger::log(
                null,
                'auth.password_reset_requested',
                'Password reset requested',
                $email,
                '#f59e0b'
            );
        }

        return back()->with(
            'status',
            'If an account exists for that email, a reset link has been sent.'
        );
    }

    public function showResetPasswordForm(Request $request, string $token): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->dashboardRouteName() ?? 'login');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->string('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $updatedUser = null;

        $status = Password::reset(
            $validated,
            function (User $user) use ($validated, &$updatedUser): void {
                $user->forceFill([
                    'password' => $validated['password'],
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $updatedUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        if ($updatedUser instanceof User) {
            ActivityLogger::log(
                $updatedUser,
                'auth.password_reset_completed',
                'Password reset completed',
                $updatedUser->email,
                '#10b981',
                $updatedUser
            );
        }

        return redirect()
            ->route('login')
            ->with('status', __($status));
    }

    public function logout(Request $request): RedirectResponse
    {
        $viewer = $request->user();

        ActivityLogger::log(
            $viewer,
            'auth.logout',
            'User logged out',
            $viewer?->email,
            '#6b7280',
            $viewer
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
