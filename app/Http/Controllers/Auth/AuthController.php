<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\NotificationCenter;
use App\Support\TwoFactorAuthenticator;
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
    private const TWO_FACTOR_SESSION_USER_ID = 'auth.two_factor.user_id';
    private const TWO_FACTOR_SESSION_REMEMBER = 'auth.two_factor.remember';

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

    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->dashboardRouteName() ?? 'login');
        }

        $this->clearPendingTwoFactorSession($request);

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

        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid login credentials.',
            ]);
        }

        $remember = $request->boolean('remember');
        $user = User::query()->firstWhere('email', $credentials['email']);
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'email' => 'Invalid login credentials.',
            ]);
        }

        $loginBlockedMessage = $this->loginBlockedMessage($user);
        if ($loginBlockedMessage !== null) {
            throw ValidationException::withMessages([
                'email' => $loginBlockedMessage,
            ]);
        }

        if (CompanySetting::twoFactorEnabled() && $user->hasTwoFactorEnabled()) {
            $this->clearPendingTwoFactorSession($request);
            $request->session()->put(self::TWO_FACTOR_SESSION_USER_ID, $user->id);
            $request->session()->put(self::TWO_FACTOR_SESSION_REMEMBER, $remember);
            $request->session()->regenerate();

            ActivityLogger::log(
                $user,
                'auth.two_factor.challenge_requested',
                'Two-factor challenge requested',
                $user->email,
                '#f59e0b',
                $user
            );

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $remember);
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

    public function showTwoFactorChallengeForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->dashboardRouteName() ?? 'login');
        }

        if (! CompanySetting::twoFactorEnabled()) {
            $this->clearPendingTwoFactorSession($request);

            return redirect()->route('login');
        }

        if (! $this->hasPendingTwoFactorSession($request)) {
            return redirect()->route('login');
        }

        $user = $this->pendingTwoFactorUser($request);
        if (! $user instanceof User || ! $user->hasTwoFactorEnabled()) {
            $this->clearPendingTwoFactorSession($request);

            return redirect()->route('login');
        }

        $loginBlockedMessage = $this->loginBlockedMessage($user);
        if ($loginBlockedMessage !== null) {
            $this->clearPendingTwoFactorSession($request);

            return redirect()
                ->route('login')
                ->withErrors(['email' => $loginBlockedMessage]);
        }

        return view('auth.two-factor-challenge', array_merge([
            'email' => $user->email,
        ], $this->brandingPayload()));
    }

    public function completeTwoFactorChallenge(
        Request $request,
        TwoFactorAuthenticator $twoFactorAuthenticator
    ): RedirectResponse {
        if (! $this->hasPendingTwoFactorSession($request)) {
            return redirect()->route('login');
        }

        if (! CompanySetting::twoFactorEnabled()) {
            $this->clearPendingTwoFactorSession($request);

            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = $this->pendingTwoFactorUser($request);
        if (! $user instanceof User || ! $user->hasTwoFactorEnabled()) {
            $this->clearPendingTwoFactorSession($request);

            return redirect()->route('login');
        }

        $loginBlockedMessage = $this->loginBlockedMessage($user);
        if ($loginBlockedMessage !== null) {
            $this->clearPendingTwoFactorSession($request);

            return redirect()
                ->route('login')
                ->withErrors(['email' => $loginBlockedMessage]);
        }

        $authenticatedWithRecoveryCode = false;
        $secret = (string) $user->two_factor_secret;
        $code = (string) $validated['code'];

        if (! $twoFactorAuthenticator->verifyCode($secret, $code)) {
            $authenticatedWithRecoveryCode = $user->consumeTwoFactorRecoveryCode($code);

            if (! $authenticatedWithRecoveryCode) {
                throw ValidationException::withMessages([
                    'code' => 'Invalid authentication code.',
                ]);
            }

            $user->save();
        }

        $remember = (bool) $request->session()->pull(self::TWO_FACTOR_SESSION_REMEMBER, false);
        $this->clearPendingTwoFactorSession($request);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        ActivityLogger::log(
            $user,
            $authenticatedWithRecoveryCode ? 'auth.two_factor.login_recovery_code' : 'auth.two_factor.login_verified',
            $authenticatedWithRecoveryCode
                ? 'User completed login with a recovery code'
                : 'User completed login with two-factor code',
            $user->email,
            $authenticatedWithRecoveryCode ? '#f59e0b' : '#10b981',
            $user
        );

        return redirect()->intended(route($user->dashboardRouteName()));
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
                'status' => 'inactive',
            ]);

            return $newUser;
        });

        NotificationCenter::notifyRoles(
            [UserRole::ADMIN->value, UserRole::HR->value],
            "auth.signup.pending.{$user->id}",
            'New account awaiting approval',
            "{$user->name} ({$user->email}) signed up and is waiting for activation.",
            route('admin.users.edit', $user),
            'warning',
            0
        );

        ActivityLogger::log(
            null,
            'auth.signup.pending_approval',
            'New user signed up and is pending approval',
            $user->email,
            '#f59e0b',
            $user
        );

        return redirect()
            ->route('login')
            ->with('status', 'Signup successful. Your account is pending Admin/HR approval. You can sign in after activation.');
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

    private function hasPendingTwoFactorSession(Request $request): bool
    {
        return $request->session()->has(self::TWO_FACTOR_SESSION_USER_ID);
    }

    private function pendingTwoFactorUser(Request $request): ?User
    {
        $pendingUserId = $request->session()->get(self::TWO_FACTOR_SESSION_USER_ID);

        return is_numeric($pendingUserId)
            ? User::query()->find((int) $pendingUserId)
            : null;
    }

    private function clearPendingTwoFactorSession(Request $request): void
    {
        $request->session()->forget([
            self::TWO_FACTOR_SESSION_USER_ID,
            self::TWO_FACTOR_SESSION_REMEMBER,
        ]);
    }

    private function loginBlockedMessage(User $user): ?string
    {
        $user->loadMissing('profile');
        $profileStatus = strtolower((string) ($user->profile?->status ?? 'active'));

        if ($profileStatus === 'inactive') {
            return 'Your account is pending approval from Admin/HR.';
        }

        if ($profileStatus === 'suspended') {
            return 'Your account is suspended. Please contact Admin/HR.';
        }

        return null;
    }
}
