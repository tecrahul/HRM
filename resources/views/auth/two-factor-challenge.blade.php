<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification | {{ config('app.name') }}</title>
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
            padding: 24px;
            display: grid;
            place-items: center;
        }

        .card {
            width: min(520px, 100%);
            border-radius: 24px;
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: 0 28px 56px -34px rgb(57 26 94 / 0.42);
            padding: 28px;
        }

        .brand-lockup {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            max-width: 100%;
        }

        .brand-logo-shell {
            min-width: 48px;
            height: 48px;
            border-radius: 14px;
            border: 1px solid rgb(124 58 237 / 0.18);
            background: rgb(124 58 237 / 0.08);
            padding: 6px 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .brand-logo {
            display: block;
            max-width: 120px;
            max-height: 34px;
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
            background: linear-gradient(120deg, var(--primary-a), var(--primary-b));
        }

        .brand-name {
            margin: 0;
            font-size: 0.97rem;
            line-height: 1.25;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        h1 {
            margin: 8px 0 6px;
            font-size: clamp(1.5rem, 2.5vw, 1.85rem);
            line-height: 1.15;
            letter-spacing: -0.01em;
        }

        .subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 0.93rem;
            line-height: 1.6;
        }

        .field {
            margin-top: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #5f547b;
        }

        input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fcfbff;
            padding: 12px 14px;
            font-size: 0.95rem;
            color: var(--text);
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
        }

        input:focus {
            border-color: #b49cf7;
            box-shadow: var(--focus);
            background: #fff;
        }

        .error-banner {
            margin-top: 14px;
            padding: 11px 12px;
            border-radius: 12px;
            border: 1px solid #ffd2e6;
            background: #fff2f8;
            color: #a82766;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .error {
            margin-top: 7px;
            font-size: 0.8rem;
            color: #d0266f;
            font-weight: 600;
        }

        .verify-btn {
            margin-top: 16px;
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
        }

        .meta {
            margin-top: 12px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .meta a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 600;
        }

        .meta a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="card" role="main">
        @include('auth.partials.brand-lockup')

        <h1>Two-Factor Verification</h1>
        <p class="subtitle">
            Enter the 6-digit code from your authenticator app for <strong>{{ $email }}</strong>.
            You can also enter a recovery code.
        </p>

        <form method="POST" action="{{ route('two-factor.challenge.attempt') }}">
            @csrf

            @if ($errors->any())
                <div class="error-banner">Verification failed. Check the code and try again.</div>
            @endif

            <div class="field">
                <label for="code">Authentication Code</label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    value="{{ old('code') }}"
                    placeholder="123456 or ABCD-EFGH"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    inputmode="numeric"
                >
                @error('code')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="verify-btn">Verify and Sign In</button>

            <p class="meta">
                Need to switch account? <a href="{{ route('login') }}">Go back to login</a>
            </p>
        </form>
    </main>
</body>
</html>
