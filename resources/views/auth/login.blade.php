<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | Smart Locker Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
        }

        .bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 10% 20%, rgba(59, 130, 246, 0.35), transparent),
                radial-gradient(ellipse 70% 50% at 90% 80%, rgba(99, 102, 241, 0.25), transparent),
                linear-gradient(160deg, #0f172a 0%, #1e293b 45%, #0f172a 100%);
        }

        .grid-overlay {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse at center, black 20%, transparent 75%);
        }

        .layout {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 960px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            align-items: center;
        }

        @media (min-width: 768px) {
            .layout { grid-template-columns: 1fr 400px; gap: 48px; }
        }

        .hero { display: none; }

        @media (min-width: 768px) {
            .hero { display: block; }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(96, 165, 250, 0.3);
            font-size: 13px;
            font-weight: 600;
            color: #93c5fd;
            margin-bottom: 24px;
        }

        .hero-badge span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #34d399;
            box-shadow: 0 0 8px #34d399;
        }

        .hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: #f8fafc;
            margin-bottom: 16px;
        }

        .hero p {
            font-size: 1.05rem;
            line-height: 1.65;
            color: #94a3b8;
            max-width: 380px;
        }

        .features {
            margin-top: 36px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .feature {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .feature-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .feature strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 2px;
        }

        .feature span {
            font-size: 13px;
            color: #64748b;
        }

        .login-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.1),
                0 24px 48px -12px rgba(0, 0, 0, 0.5);
            color: #0f172a;
        }

        .logo {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
        }

        .login-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            height: 48px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0 16px;
            font-size: 15px;
            font-family: inherit;
            background: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        input::placeholder { color: #94a3b8; }

        input:focus {
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            font-size: 14px;
            color: #475569;
            cursor: pointer;
        }

        .remember-row input { width: 16px; height: 16px; accent-color: #2563eb; }

        .btn-login {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.45);
        }

        .btn-login:active { transform: translateY(0); }

        .error-box {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .footer-text {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="bg" aria-hidden="true"></div>
    <div class="grid-overlay" aria-hidden="true"></div>

    <div class="layout">
        <section class="hero">
            <div class="hero-badge"><span></span> Smart Locker Monitoring</div>
            <h1>มอนิเตอร์ตู้ล็อกเกอร์<br>แบบเรียลไทม์</h1>
            <p>ระบบมอนิเตอร์สถานะตู้ Smart Locker ของลูกค้าแต่ละเจ้า — ดูสถานะ แจ้งปัญหา และจัดการได้ในที่เดียว</p>
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">📊</div>
                    <div>
                        <strong>แดชบอร์ดแบบเรียลไทม์</strong>
                        <span>ติดตามสถานะตู้ Available, In Use, Fault</span>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">🏢</div>
                    <div>
                        <strong>รองรับหลายบริษัท</strong>
                        <span>แยกข้อมูลตามลูกค้าและสาขา</span>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">🔔</div>
                    <div>
                        <strong>แจ้งเตือนและซ่อมบำรุง</strong>
                        <span>จัดการ Issue และ Maintenance ครบวงจร</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="login-card">
            <div class="logo">SL</div>
            <h2>เข้าสู่ระบบ</h2>
            <p class="subtitle">ใช้อีเมลและรหัสผ่านของคุณเพื่อเข้า Admin Panel</p>

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
                        required
                        autofocus>
                </div>

                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required>
                </div>

                <label class="remember-row">
                    <input type="checkbox" name="remember" value="1">
                    จำการเข้าสู่ระบบ
                </label>

                <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
            </form>

            <p class="footer-text">Smart Locker Monitor &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>
