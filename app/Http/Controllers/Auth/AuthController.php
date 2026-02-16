<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->dashboardRouteName() ?? 'login');
        }

        return view('auth.login');
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
