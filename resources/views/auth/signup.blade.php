<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f3ff;
            --surface: #ffffff;
            --line: #e7e2f4;
            --text: #1f1a2e;
            --muted: #6f668a;
            --primary-a: #7c3aed;
            --primary-b: #ec4899;
            --primary-c: #fb7185;
            --focus: 0 0 0 3px rgb(124 58 237 / 0.22);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", ui-sans-serif, system-ui, sans-serif;
            color: var(--text);
            background: radial-gradient(1000px 520px at -15% -10%, #e8dbff 0%, transparent 55%),
                        radial-gradient(820px 580px at 105% -5%, #ffd9eb 0%, transparent 58%),
                        var(--bg);
            padding: 28px;
            display: grid;
            place-items: center;
        }

        .auth-shell {
            width: min(1080px, 100%);
            min-height: 640px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 36px 70px -42px rgb(57 26 94 / 0.42);
        }

        .auth-hero {
            position: relative;
            overflow: hidden;
            padding: clamp(28px, 5vw, 52px);
            background: linear-gradient(145deg, var(--primary-a) 0%, var(--primary-b) 52%, var(--primary-c) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
        }

        .auth-hero::before,
        .auth-hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .auth-hero::before {
            width: 320px;
            height: 320px;
            right: -120px;
            top: -120px;
            background: radial-gradient(circle, rgb(255 255 255 / 0.3), rgb(255 255 255 / 0) 70%);
        }

        .auth-hero::after {
            width: 360px;
            height: 360px;
            left: -150px;
            bottom: -170px;
            background: radial-gradient(circle, rgb(255 255 255 / 0.2), rgb(255 255 255 / 0) 72%);
        }

        .shape {
            position: absolute;
            border-radius: 999px;
            filter: blur(0.4px);
            opacity: 0.34;
            pointer-events: none;
        }

        .shape-one {
            width: 220px;
            height: 220px;
            right: 15%;
            bottom: 12%;
            background: linear-gradient(140deg, rgb(255 255 255 / 0.55), rgb(255 255 255 / 0.1));
        }

        .shape-two {
            width: 160px;
            height: 160px;
            left: 18%;
            top: 18%;
            background: linear-gradient(150deg, rgb(255 255 255 / 0.45), rgb(255 255 255 / 0.08));
        }

        .hero-copy {
            position: relative;
            z-index: 1;
            max-width: 420px;
        }

        .brand-lockup {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            max-width: 100%;
        }

        .brand-logo-shell {
            min-width: 54px;
            height: 54px;
            border-radius: 14px;
            border: 1px solid rgb(255 255 255 / 0.28);
            background: rgb(255 255 255 / 0.16);
            padding: 6px 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .brand-logo {
            display: block;
            max-width: 132px;
            max-height: 42px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .brand-fallback-mark {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
            background: rgb(255 255 255 / 0.15);
        }

        .brand-copy {
            min-width: 0;
        }

        .brand-name {
            margin: 0;
            font-size: 0.97rem;
            line-height: 1.25;
            font-weight: 700;
            letter-spacing: 0.01em;
            text-wrap: balance;
        }

        .hero-kicker {
            margin: 0;
            font-size: 0.74rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            font-weight: 700;
            opacity: 0.84;
        }

        .hero-title {
            margin: 12px 0 0;
            font-size: clamp(1.8rem, 4vw, 2.7rem);
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .hero-text {
            margin: 14px 0 0;
            font-size: 1rem;
            line-height: 1.7;
            opacity: 0.95;
            max-width: 36ch;
        }

        .auth-form-wrap {
            padding: clamp(24px, 4vw, 46px);
            display: grid;
            align-content: center;
            background: #fff;
        }

        .form-header h2 {
            margin: 0;
            font-size: clamp(1.5rem, 2.7vw, 2rem);
            line-height: 1.15;
            letter-spacing: -0.01em;
        }

        .form-header p {
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .auth-form {
            margin-top: 28px;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #5f547b;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #9b91b8;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fcfbff;
            padding: 12px 14px 12px 44px;
            font-size: 0.95rem;
            color: var(--text);
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
        }

        .input-wrap input::placeholder {
            color: #ada5c5;
        }

        .input-wrap input:focus {
            border-color: #b49cf7;
            box-shadow: var(--focus);
            background: #fff;
        }

        .input-wrap.has-toggle input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #7f74a0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .password-toggle:hover {
            background: rgb(124 58 237 / 0.1);
            color: #6d28d9;
        }

        .password-toggle:focus-visible {
            outline: none;
            box-shadow: var(--focus);
        }

        .password-toggle svg {
            position: static;
            transform: none;
            width: 17px;
            height: 17px;
            pointer-events: none;
        }

        .password-toggle .icon-hidden {
            display: none;
        }

        .password-toggle[data-visible="true"] .icon-shown {
            display: none;
        }

        .password-toggle[data-visible="true"] .icon-hidden {
            display: block;
        }

        .login-btn {
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.97rem;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            background: linear-gradient(130deg, var(--primary-a) 0%, var(--primary-b) 55%, var(--primary-c) 100%);
            box-shadow: 0 20px 28px -20px rgb(124 58 237 / 0.65);
            transition: transform 150ms ease, box-shadow 150ms ease;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 24px 32px -20px rgb(124 58 237 / 0.72);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error {
            margin-top: 7px;
            font-size: 0.8rem;
            color: #d0266f;
            font-weight: 600;
        }

        .error-banner {
            margin-bottom: 14px;
            padding: 11px 12px;
            border-radius: 12px;
            border: 1px solid #ffd2e6;
            background: #fff2f8;
            color: #a82766;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .form-links {
            margin-top: 14px;
            font-size: 0.9rem;
            color: #534b6c;
            text-align: center;
        }

        .form-links a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 600;
        }

        .form-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 920px) {
            body {
                padding: 16px;
            }

            .auth-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .auth-hero {
                min-height: 240px;
                padding: 28px;
            }

            .brand-logo-shell {
                min-width: 48px;
                height: 48px;
            }

            .brand-logo {
                max-width: 112px;
                max-height: 34px;
            }

            .hero-title {
                font-size: 1.8rem;
            }

            .auth-form-wrap {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
<main class="auth-shell" role="main">
    <section class="auth-hero">
        <span class="shape shape-one" aria-hidden="true"></span>
        <span class="shape shape-two" aria-hidden="true"></span>
        @include('auth.partials.brand-lockup')

        <div class="hero-copy">
            <p class="hero-kicker">Join The Team</p>
            <h1 class="hero-title">Create your workspace account in minutes.</h1>
            <p class="hero-text">Set up your account to access attendance, leave, payroll, and day-to-day employee tools from one place.</p>
        </div>
    </section>

    <section class="auth-form-wrap">
        <header class="form-header">
            <h2>Create an account</h2>
            <p>Enter your details to get started.</p>
        </header>

        <form method="POST" action="{{ route('register.attempt') }}" class="auth-form" data-inline-validation>
            @csrf

            @if ($errors->any())
                <div class="error-banner">Please correct the highlighted fields and try again.</div>
            @endif

            <div class="field">
                <label for="name">Full Name</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="7" r="4"></circle>
                        <path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path>
                    </svg>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Jane Doe"
                        required
                        autofocus
                    >
                </div>
                @error('name')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="M3 8l9 6 9-6"></path>
                    </svg>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required
                    >
                </div>
                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap has-toggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                    </svg>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Create a strong password"
                        required
                    >
                    <button type="button" class="password-toggle" data-password-toggle data-target="password" data-visible="false" aria-label="Show password">
                        <svg class="icon-shown" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 3l18 18"></path>
                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                            <path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-4 4.9"></path>
                            <path d="M6.6 6.6C4 8.3 2 12 2 12s3.5 6 10 6a10.4 10.4 0 0 0 5.2-1.4"></path>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-wrap has-toggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                    </svg>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        placeholder="Re-enter your password"
                        required
                    >
                    <button type="button" class="password-toggle" data-password-toggle data-target="password_confirmation" data-visible="false" aria-label="Show password">
                        <svg class="icon-shown" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 3l18 18"></path>
                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                            <path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-4 4.9"></path>
                            <path d="M6.6 6.6C4 8.3 2 12 2 12s3.5 6 10 6a10.4 10.4 0 0 0 5.2-1.4"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">Create Account</button>

            <p class="form-links">
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </p>
        </form>
    </section>
</main>
<script>
    (() => {
        document.querySelectorAll("[data-password-toggle]").forEach((toggleButton) => {
            const targetId = toggleButton.getAttribute("data-target");
            const input = targetId ? document.getElementById(targetId) : null;
            if (!input) {
                return;
            }

            toggleButton.addEventListener("click", () => {
                const isVisible = input.type === "text";
                input.type = isVisible ? "password" : "text";
                toggleButton.setAttribute("data-visible", isVisible ? "false" : "true");
                toggleButton.setAttribute("aria-label", isVisible ? "Show password" : "Hide password");
            });
        });
    })();
</script>
@include('auth.partials.inline-validation-script')
</body>
</html>
