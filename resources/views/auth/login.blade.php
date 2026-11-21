<!DOCTYPE html>
<html lang="en">

<head>
<link rel="icon" type="image/png" href="{{ asset('assets/barangay_cawit_logo.png') }}">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Barangay Cawit</title>

<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    min-height: 100vh;
    background: linear-gradient(135deg, #dff4ff, #bce5f8, #effaff);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px 15px;
    overflow-x: hidden;
    position: relative;
}

.christmas-lights {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 28px;
    border-top: 3px solid #8aaebe;
    background:
        radial-gradient(circle, #e94c5d 5px, transparent 6px) 0 0 / 65px 20px repeat-x,
        radial-gradient(circle, #f5ca58 5px, transparent 6px) 22px 0 / 65px 20px repeat-x,
        radial-gradient(circle, #48a981 5px, transparent 6px) 44px 0 / 65px 20px repeat-x;
    z-index: 10;
    pointer-events: none;
}

.container {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 440px;
    background: rgba(255, 255, 255, .94);
    padding: 35px;
    border-radius: 22px;
    border: 1px solid #c8e8f8;
    box-shadow: 0 15px 40px rgba(45, 110, 150, .18);
    backdrop-filter: blur(12px);
    animation: appear .7s ease;
}

@keyframes appear {
    from { opacity: 0; transform: translateY(25px); }
    to { opacity: 1; transform: translateY(0); }
}

.logo-container { text-align: center; margin-bottom: 15px; }
.logo-container img {
    width: 95px;
    height: 95px;
    object-fit: contain;
    border-radius: 50%;
    background: white;
    padding: 5px;
    border: 3px solid #d8edf8;
    box-shadow: 0 5px 15px rgba(63, 133, 170, .15);
}

.brand-name {
    text-align: center;
    color: #174b70;
    font-size: 21px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.location { text-align: center; color: #71899a; font-size: 13px; margin-bottom: 22px; }
.decoration { text-align: center; font-size: 24px; margin-bottom: 12px; }

.tree-decoration {
    position: absolute;
    font-size: 36px;
    top: 15px;
    right: 18px;
    animation: sway 3s ease-in-out infinite;
}

.snow-decoration {
    position: absolute;
    font-size: 28px;
    left: 18px;
    top: 18px;
    animation: sway 4s ease-in-out infinite;
}

@keyframes sway {
    0%, 100% { transform: rotate(-5deg); }
    50% { transform: rotate(5deg); }
}

h1 { text-align: center; color: #174b70; margin: 0 0 8px; font-size: 27px; }
.subtitle { text-align: center; color: #71899a; margin-bottom: 27px; font-size: 14px; line-height: 1.5; }

label { display: block; margin: 17px 0 8px; font-weight: bold; color: #315b75; font-size: 14px; }

input {
    width: 100%;
    padding: 14px;
    border: 1px solid #c9dfea;
    border-radius: 10px;
    font-size: 15px;
    outline: none;
    background: #f8fcff;
    transition: .3s;
}

input:focus {
    border-color: #65bce8;
    background: white;
    box-shadow: 0 0 0 3px rgba(101, 188, 232, .15);
}

button {
    width: 100%;
    padding: 15px;
    margin-top: 25px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(120deg, #2586a8, #174b70);
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: .3s;
    animation: glow 3s ease-in-out infinite;
}

button:hover {
    background: linear-gradient(120deg, #174b70, #25866b);
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(37, 134, 168, .25);
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 0 rgba(101, 188, 232, 0); }
    50% { box-shadow: 0 0 13px rgba(101, 188, 232, .4); }
}

.error, .success { padding: 13px; border-radius: 9px; margin-bottom: 18px; font-size: 13px; line-height: 1.5; }
.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

.links { text-align: center; margin-top: 24px; color: #647f91; font-size: 14px; }
a { color: #2586a8; text-decoration: none; font-weight: bold; }
.links a:hover { color: #174b70; text-decoration: underline; }
.footer { text-align: center; color: #7892a3; font-size: 11px; margin-top: 24px; line-height: 1.7; }

.snowflake {
    position: fixed;
    top: -30px;
    z-index: 1;
    color: white;
    text-shadow: 0 0 6px #79bfe5;
    pointer-events: none;
    animation: snowfall linear infinite;
}

@keyframes snowfall {
    0% { transform: translateY(-10vh) rotate(0deg); opacity: .9; }
    100% { transform: translateY(110vh) rotate(360deg); opacity: .2; }
}

@media(max-width: 480px) {
    .container { padding: 28px 22px; }
    .logo-container img { width: 80px; height: 80px; }
    h1 { font-size: 24px; }
    .tree-decoration { font-size: 28px; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
}
</style>

@if ($activeTheme === 'christmas')
    <link rel="stylesheet" href="{{ asset('assets/christmas-theme.css') }}">
@else
    <link rel="stylesheet" href="{{ asset('assets/modern-theme.css') }}">
@endif

</head>

<body>

<div class="christmas-lights" aria-hidden="true"></div>

<div class="container">
    <div class="snow-decoration" aria-hidden="true">❄️</div>
    <div class="tree-decoration" aria-hidden="true">🎄</div>

    <div class="logo-container">
        <img src="{{ asset('assets/barangay_cawit_logo.png') }}" alt="Barangay Cawit Logo">
    </div>

    <div class="brand-name">BARANGAY CAWIT</div>
    <div class="location">Manito, Albay</div>
    <div class="decoration" aria-hidden="true">🎁 ❄️ 🎄 ❄️ 🎁</div>

    <h1>Welcome Back!</h1>
    <p class="subtitle">
        Barangay Document Request and<br>
        Verifiable E-Certificate System
    </p>

    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    @if (session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="Enter your email"
            autocomplete="email"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
        >

        <button type="submit" class="auth-submit-btn">{{ $activeTheme === 'christmas' ? '❄️ Login to Your Account' : 'Login to Your Account' }}</button>
    </form>

    <div class="links">
        Don't have an account?
        <a href="{{ route('register') }}">Register here</a>
    </div>

    <div class="footer">
        <strong>{{ $activeTheme === 'christmas' ? '🎄 BARANGAY CAWIT 🎄' : 'BARANGAY CAWIT' }}</strong><br>
        Municipality of Manito, Province of Albay<br>
        {{ $activeTheme === 'christmas' ? '❄️ Merry Christmas and Happy Holidays! ❄️' : 'Official Electronic Document & Verification Portal' }}
    </div>
</div>

@if ($activeTheme === 'christmas')
    <script>
        (function () {
            const symbols = ["❄", "❅", "❆"];
            const snowCount = 35;

            for (let i = 0; i < snowCount; i++) {
                const snow = document.createElement("span");
                snow.className = "snowflake";
                snow.textContent = symbols[Math.floor(Math.random() * symbols.length)];
                snow.style.left = Math.random() * 100 + "vw";
                snow.style.fontSize = (10 + Math.random() * 15) + "px";
                snow.style.animationDuration = (8 + Math.random() * 12) + "s";
                snow.style.animationDelay = (-Math.random() * 15) + "s";
                document.body.appendChild(snow);
            }
        })();
    </script>
@endif

</body>
</html>