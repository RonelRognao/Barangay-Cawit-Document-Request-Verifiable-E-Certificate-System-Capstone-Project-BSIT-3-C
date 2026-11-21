<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('assets/barangay_cawit_logo.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Resident Portal') | Barangay Cawit</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f4fbff, #e4f5ff, #ffffff);
            color: #263b4a;
        }

        /* HEADER */
        .portal-header {
            position: relative;
            overflow: hidden;
            background: linear-gradient(110deg, #123b5d, #176b83, #25866b);
            color: white;
            padding: 22px 6% 18px;
            box-shadow: 0 4px 16px rgba(18, 59, 93, 0.16);
        }

        .portal-header .christmas-lights {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 18px;
            margin: 0;
            border-top: 3px solid #b7d9e8;
            background:
                radial-gradient(circle, #ff5964 4px, transparent 5px) 0 0 / 55px 15px repeat-x,
                radial-gradient(circle, #ffe17a 4px, transparent 5px) 18px 0 / 55px 15px repeat-x,
                radial-gradient(circle, #55d6a1 4px, transparent 5px) 36px 0 / 55px 15px repeat-x;
            pointer-events: none;
        }

        .header-top {
            max-width: 1150px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            position: relative;
            z-index: 2;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            background: white;
            border-radius: 50%;
            padding: 3px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        }

        .brand h2 {
            margin: 0;
            font-size: 19px;
            color: white;
            letter-spacing: 0.5px;
        }

        .brand p {
            margin: 3px 0 0;
            font-size: 12px;
            color: #dbeafe;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-pill {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .logout-btn {
            background: #c9364b;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: .25s;
        }

        .logout-btn:hover {
            background: #a9273a;
            transform: translateY(-2px);
        }

        /* FROSTED NAV BAR */
        .subnav {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #cbe7f6;
            padding: 10px 6%;
            box-shadow: 0 2px 10px rgba(63, 133, 170, 0.06);
        }

        .subnav-inner {
            max-width: 1150px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 14px;
            border-radius: 9px;
            text-decoration: none;
            color: #315b75;
            font-size: 13px;
            font-weight: bold;
            transition: .2s;
        }

        .nav-link:hover {
            background: #e2f4ff;
            color: #174b70;
        }

        .nav-link.active {
            background: linear-gradient(120deg, #2586a8, #174b70);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 134, 168, 0.22);
        }

        .nav-badge {
            background: #c9364b;
            color: white;
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 12px;
        }

        /* MAIN AREA */
        .portal-main {
            flex: 1;
            width: 100%;
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* PAGE BANNER */
        .page-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            background: linear-gradient(120deg, #dff4ff, #ffffff 55%, #c9ebff);
            border: 1px solid #c6e7f7;
            border-radius: 16px;
            padding: 26px 30px;
            margin-bottom: 24px;
            box-shadow: 0 6px 20px rgba(75, 151, 190, 0.12);
        }

        .page-banner .eyebrow {
            color: #2586a8;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .page-banner h1 {
            margin: 0 0 6px;
            font-size: 25px;
            color: #174b70;
        }

        .page-banner p {
            margin: 0;
            color: #486b82;
            font-size: 14px;
            line-height: 1.5;
        }

        .banner-icon {
            font-size: 52px;
            animation: tree-sway 3s ease-in-out infinite;
            filter: drop-shadow(0 4px 6px rgba(23, 75, 112, 0.15));
        }

        /* FROSTED CARDS & CONTROLS */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #d8edf8;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 22px rgba(63, 133, 170, 0.10);
        }

        .btn-winter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 22px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(120deg, #2586a8, #174b70);
            color: white;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: .25s;
            animation: winter-glow 3s ease-in-out infinite;
        }

        .btn-winter:hover {
            background: linear-gradient(120deg, #174b70, #25866b);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(37, 134, 168, 0.25);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border: 1px solid #bcdcf0;
            border-radius: 9px;
            background: white;
            color: #174b70;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            transition: .2s;
        }

        .btn-outline:hover {
            background: #eaf7ff;
            border-color: #65bce8;
        }

        /* ALERTS */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* FORMS */
        .form-label {
            display: block;
            margin: 16px 0 8px;
            font-weight: bold;
            color: #315b75;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #c9dfea;
            border-radius: 10px;
            font-size: 15px;
            background: #f8fcff;
            color: #1e293b;
            outline: none;
            transition: .25s;
        }

        .form-control:focus {
            border-color: #65bce8;
            background: white;
            box-shadow: 0 0 0 3px rgba(101, 188, 232, 0.16);
        }

        /* TABLES */
        .table-responsive {
            overflow-x: auto;
        }

        .winter-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }

        .winter-table th {
            background: #dff3ff;
            color: #174b70;
            padding: 14px 15px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .winter-table td {
            padding: 15px;
            border-bottom: 1px solid #e6f2f8;
            font-size: 14px;
            vertical-align: middle;
        }

        .winter-table tbody tr:hover td {
            background: #f6fbff;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 45px 20px;
            color: #647f91;
        }

        .empty-state .empty-icon {
            font-size: 46px;
            margin-bottom: 10px;
        }

        .empty-state h3 {
            margin: 0 0 8px;
            color: #174b70;
        }

        /* FOOTER */
        .portal-footer {
            text-align: center;
            padding: 26px 15px;
            color: #647f91;
            font-size: 13px;
            background: rgba(223, 243, 255, 0.65);
            border-top: 1px solid #d2eaf7;
            margin-top: 40px;
            line-height: 1.8;
        }

        @media (max-width: 640px) {
            .portal-header {
                padding: 20px 15px 15px;
            }
            .user-pill {
                display: none;
            }
            .portal-main {
                padding: 0 12px;
                margin-top: 20px;
            }
            .page-banner {
                padding: 20px;
            }
            .glass-card {
                padding: 18px;
            }
        }
    </style>

    @if ($activeTheme === 'christmas')
        <link rel="stylesheet" href="{{ asset('assets/christmas-theme.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/modern-theme.css') }}">
    @endif

    @stack('styles')
</head>

<body>

@php
    $navUnread = \App\Models\Notification::where('user_id', auth()->id())
        ->where('is_read', false)
        ->count();
@endphp

<header class="portal-header">
    <div class="christmas-lights" aria-hidden="true"></div>

    <div class="header-top">
        <a href="{{ route('resident.dashboard') }}" class="brand">
            <img src="{{ asset('assets/barangay_cawit_logo.png') }}" alt="Barangay Cawit Logo">
            <div>
                <h2>BARANGAY CAWIT</h2>
                <p>Manito, Albay | Resident Portal</p>
            </div>
        </a>

        <div class="header-actions">
            <span class="user-pill">
                {{ $activeTheme === 'christmas' ? '❄️' : '👤' }} {{ auth()->user()->full_name }}
            </span>

            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>
</header>

<nav class="subnav" aria-label="Resident Navigation">
    <div class="subnav-inner">
        <a href="{{ route('resident.dashboard') }}"
           class="nav-link {{ request()->routeIs('resident.dashboard') ? 'active' : '' }}">
            🏠 Dashboard
        </a>

        <a href="{{ route('resident.request.create') }}"
           class="nav-link {{ request()->routeIs('resident.request.*') ? 'active' : '' }}">
            📄 Request Document
        </a>

        <a href="{{ route('resident.requests') }}"
           class="nav-link {{ request()->routeIs('resident.requests') ? 'active' : '' }}">
            📋 My Requests
        </a>

        <a href="{{ route('resident.certificates') }}"
           class="nav-link {{ request()->routeIs('resident.certificates') ? 'active' : '' }}">
            🏅 My E-Certificates
        </a>

        <a href="{{ route('resident.notifications') }}"
           class="nav-link {{ request()->routeIs('resident.notifications') ? 'active' : '' }}">
            🔔 Notifications
            @if ($navUnread > 0)
                <span class="nav-badge">{{ $navUnread > 99 ? '99+' : $navUnread }}</span>
            @endif
        </a>

        <a href="{{ route('resident.profile') }}"
           class="nav-link {{ request()->routeIs('resident.profile*') ? 'active' : '' }}">
            👤 My Profile
        </a>
    </div>
</nav>

<main class="portal-main">
    @yield('content')
</main>

<footer class="portal-footer">
    <strong>{{ $activeTheme === 'christmas' ? '🎄 BARANGAY CAWIT 🎄' : 'BARANGAY CAWIT' }}</strong><br>
    Municipality of Manito, Province of Albay<br>
    Barangay Document Request and Verifiable E-Certificate System<br>
    {{ $activeTheme === 'christmas' ? '❄️ Merry Christmas and Happy Holidays! ❄️' : 'Official Electronic Document & Verification Portal' }}
</footer>

@if ($activeTheme === 'christmas')
    <script>
        (function () {
            const symbols = ["❄", "❅", "❆"];
            const snowCount = 20;

            for (let i = 0; i < snowCount; i++) {
                const snow = document.createElement("span");
                snow.className = "snowflake";
                snow.textContent = symbols[Math.floor(Math.random() * symbols.length)];
                snow.style.left = Math.random() * 100 + "vw";
                snow.style.fontSize = (10 + Math.random() * 13) + "px";
                snow.style.animationDuration = (9 + Math.random() * 12) + "s";
                snow.style.animationDelay = (-Math.random() * 15) + "s";
                document.body.appendChild(snow);
            }
        })();
    </script>
@endif

</body>
</html>