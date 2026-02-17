<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f5f9ff;
            --surface: #ffffff;
            --line: #d7e4fb;
            --text: #0f172a;
            --muted: #475569;
            --primary-a: #2563eb;
            --primary-b: #0ea5e9;
            --focus: 0 0 0 3px rgb(37 99 235 / 0.22);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", ui-sans-serif, system-ui, sans-serif;
            color: var(--text);
            background: radial-gradient(820px 470px at -10% -10%, #dbeafe 0%, transparent 60%),
                        radial-gradient(720px 500px at 110% 0%, #cffafe 0%, transparent 62%),
                        var(--bg);
            display: grid;
            place-items: center;
            padding: 22px;
        }

        .auth-card {
            width: min(560px, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 30px 56px -40px rgb(15 23 42 / 0.45);
            padding: clamp(22px, 4vw, 34px);
        }

        h1 {
            margin: 0;
            font-size: clamp(1.5rem, 3vw, 1.9rem);
            letter-spacing: -0.01em;
        }

        .muted {
            margin: 10px 0 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        form {
            margin-top: 22px;
        }

        .field {
            margin-bottom: 14px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #334155;
        }

        .field input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 11px 12px;
            background: #f9fbff;
            font-size: 0.95rem;
            outline: none;
        }

        .field input:focus {
            border-color: #93c5fd;
            box-shadow: var(--focus);
            background: #fff;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap.has-toggle input {
            padding-right: 44px;
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .password-toggle:hover {
            background: rgb(37 99 235 / 0.1);
            color: #1d4ed8;
        }

        .password-toggle:focus-visible {
            outline: none;
            box-shadow: var(--focus);
        }

        .password-toggle svg {
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

        .error {
            margin-top: 6px;
            font-size: 0.8rem;
            color: #be123c;
            font-weight: 600;
        }

        .btn {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 12px 14px;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(130deg, var(--primary-a), var(--primary-b));
        }
    </style>
</head>
<body>
<main class="auth-card">
    <h1>Set a new password</h1>
    <p class="muted">Choose a new password for your account to finish verification.</p>

    <form method="POST" action="{{ route('password.update') }}" data-inline-validation>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus>
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password">New Password</label>
            <div class="input-wrap has-toggle">
                <input id="password" name="password" type="password" required>
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
                <input id="password_confirmation" name="password_confirmation" type="password" required>
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

        <button type="submit" class="btn">Reset Password</button>
    </form>
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
