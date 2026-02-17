<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8f6ff;
            --surface: #ffffff;
            --line: #e7e2f4;
            --text: #1f1a2e;
            --muted: #6f668a;
            --primary-a: #7c3aed;
            --primary-b: #ec4899;
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
            background: radial-gradient(860px 480px at -10% -10%, #e8dbff 0%, transparent 60%),
                        radial-gradient(780px 520px at 110% 0%, #ffd9eb 0%, transparent 62%),
                        var(--bg);
            display: grid;
            place-items: center;
            padding: 22px;
        }

        .auth-card {
            width: min(540px, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 30px 56px -40px rgb(57 26 94 / 0.45);
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
            line-height: 1.6;
        }

        .status {
            margin-top: 14px;
            border-radius: 12px;
            border: 1px solid #b8e6d1;
            background: #effcf5;
            color: #0f766e;
            padding: 10px 11px;
            font-size: 0.86rem;
            font-weight: 600;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #5f547b;
        }

        input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 11px 12px;
            background: #fcfbff;
            font-size: 0.95rem;
            outline: none;
        }

        input:focus {
            border-color: #b49cf7;
            box-shadow: var(--focus);
            background: #fff;
        }

        .error {
            margin-top: 6px;
            font-size: 0.8rem;
            color: #d0266f;
            font-weight: 600;
        }

        .btn {
            margin-top: 14px;
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

        .links {
            margin-top: 12px;
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .links a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<main class="auth-card">
    <h1>Forgot your password?</h1>
    <p class="muted">Enter your email address and we will send you a verification link to reset your password.</p>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" data-inline-validation>
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror

        <button type="submit" class="btn">Send Reset Link</button>

        <p class="links"><a href="{{ route('login') }}">Back to login</a></p>
    </form>
</main>
@include('auth.partials.inline-validation-script')
</body>
</html>
