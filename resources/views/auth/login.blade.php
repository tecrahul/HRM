<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ config('app.name') }}</title>
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

        .form-meta {
            margin: 4px 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #534b6c;
            user-select: none;
        }

        .remember input {
            width: 15px;
            height: 15px;
            accent-color: var(--primary-a);
        }

        .forgot {
            font-size: 0.9rem;
            text-decoration: none;
            color: #7c3aed;
            font-weight: 600;
        }

        .forgot:hover {
            text-decoration: underline;
        }

        .forgot.is-disabled {
            color: #9c94b3;
            pointer-events: none;
            text-decoration: none;
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

            .hero-title {
                font-size: 1.8rem;
            }

            .auth-form-wrap {
                padding: 24px;
            }

            .form-meta {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
<main class="auth-shell" role="main">
    <section class="auth-hero">
        <span class="shape shape-one" aria-hidden="true"></span>
        <span class="shape shape-two" aria-hidden="true"></span>

        <div class="hero-copy">
            <p class="hero-kicker">Welcome Back</p>
            <h1 class="hero-title">Manage your workforce with confidence.</h1>
            <p class="hero-text">Sign in to access your HR dashboard, keep team operations in sync, and stay on top of daily people workflows.</p>
        </div>
    </section>

    <section class="auth-form-wrap">
        <header class="form-header">
            <h2>Sign in to {{ config('app.name') }}</h2>
            <p>Enter your email and password to continue.</p>
        </header>

        <form method="POST" action="{{ route('login.attempt') }}" class="auth-form">
            @csrf

            @if ($errors->any())
                <div class="error-banner">Please check your credentials and try again.</div>
            @endif

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
                        autofocus
                    >
                </div>
                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                    </svg>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>
                @error('password')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-meta">
                <label for="remember" class="remember">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Remember me
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot">Forgot password?</a>
                @else
                    <a href="#" class="forgot is-disabled" aria-disabled="true">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>
    </section>
</main>
</body>
</html>
