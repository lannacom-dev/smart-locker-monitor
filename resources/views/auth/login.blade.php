<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Login | Smart Locker Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #111827, #1e3a8a);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
        }

        .logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
            color: #111827;
            font-size: 26px;
        }

        .subtitle {
            margin-top: 8px;
            margin-bottom: 26px;
            color: #6b7280;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: bold;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            height: 46px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 0 14px;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #4b5563;
            font-size: 14px;
        }

        .btn-login {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 12px;
            background: #2563eb;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #1d4ed8;
        }

        .error-box {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .footer-text {
            margin-top: 20px;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo">SL</div>

        <h1>Smart Locker Monitor</h1>
        <div class="subtitle">
            เข้าสู่ระบบเพื่อดูสถานะ Smart Locker ของลูกค้า
        </div>

        @if ($errors->any())
        <div class="error-box">
            @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="form-group">
                <label for="email">อีเมล</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@example.com"
                    autocomplete="email"
                    autofocus>
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="********"
                    autocomplete="current-password">
            </div>

            <label class="remember-row">
                <input type="checkbox" name="remember" value="1">
                จำการเข้าสู่ระบบ
            </label>

            <button type="submit" class="btn-login">
                เข้าสู่ระบบ
            </button>
        </form>

        <div class="footer-text">
            Smart Locker Monitoring System
        </div>
    </div>

</body>

</html>