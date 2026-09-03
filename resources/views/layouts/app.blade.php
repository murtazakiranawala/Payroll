<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Payroll') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* ============================================================
           DESIGN TOKENS
           A single navy + green system (trust + "paid/approved") in
           place of the old default-Bootstrap teal/blue mix. Re-pointing
           Bootstrap's own CSS variables here re-skins every stock
           .btn-primary / .text-bg-success / focus ring / link across
           the app without touching each view individually.
           ============================================================ */
        :root {
            --ink-900: #0A1526;
            --ink-800: #101F35;
            --ink-700: #16283F;
            --brand: #1E3A5F;
            --brand-rgb: 30, 58, 95;
            --brand-dark: #14293F;
            --brand-light: #EEF3F9;
            --accent: #059669;
            --accent-rgb: 5, 150, 105;
            --accent-dark: #047857;
            --accent-light: #ECFDF5;

            --bg: #F6F8FB;
            --surface: #FFFFFF;
            --surface-alt: #F3F6FA;
            --border: #E4E9F1;
            --border-strong: #D3DCE7;
            --text: #101828;
            --text-muted: #5B6B84;
            --text-subtle: #94A3B8;
            --danger: #DC2626;
            --danger-rgb: 220, 38, 38;
            --warning: #D97706;
            --warning-rgb: 217, 119, 6;
            --info: #2563EB;
            --info-rgb: 37, 99, 235;

            --radius-sm: .5rem;
            --radius: .75rem;
            --radius-lg: 1rem;
            --shadow-xs: 0 1px 2px rgba(16, 24, 40, .04);
            --shadow-sm: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04);
            --shadow-md: 0 4px 14px rgba(16, 24, 40, .08);
            --shadow-lg: 0 16px 40px rgba(16, 24, 40, .14);

            /* Re-point Bootstrap 5.3's own variables so stock components
               (.btn-primary, .text-bg-success, .form-control:focus,
               pagination, alerts, links) inherit the new palette. */
            --bs-body-font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --bs-body-bg: var(--bg);
            --bs-body-color: var(--text);
            --bs-border-color: var(--border);
            --bs-border-radius: var(--radius-sm);
            --bs-border-radius-sm: .4375rem;
            --bs-border-radius-lg: var(--radius);
            --bs-border-radius-xl: var(--radius-lg);
            --bs-primary: var(--brand);
            --bs-primary-rgb: var(--brand-rgb);
            --bs-secondary: var(--text-muted);
            --bs-secondary-rgb: 91, 107, 132;
            --bs-success: var(--accent);
            --bs-success-rgb: var(--accent-rgb);
            --bs-info: var(--info);
            --bs-info-rgb: var(--info-rgb);
            --bs-warning: var(--warning);
            --bs-warning-rgb: var(--warning-rgb);
            --bs-danger: var(--danger);
            --bs-danger-rgb: var(--danger-rgb);
            --bs-light: var(--surface-alt);
            --bs-light-rgb: 243, 246, 250;
            --bs-dark: var(--ink-900);
            --bs-link-color: var(--brand);
            --bs-link-hover-color: var(--brand-dark);
            --bs-link-color-rgb: var(--brand-rgb);
            --bs-emphasis-color: var(--text);
            --bs-focus-ring-color: rgba(var(--brand-rgb), .25);
        }

        * { letter-spacing: -0.005em; }
        html { -webkit-font-smoothing: antialiased; }
        body { background-color: var(--bg); color: var(--text); font-size: .9375rem; }
        h1, h2, h3, h4, h5, h6 { font-weight: 700; color: var(--text); letter-spacing: -0.015em; }
        a { text-decoration: none; }
        small, .small { font-size: .8125rem; }
        ::selection { background: rgba(var(--brand-rgb), .18); }

        /* Consistent, visible keyboard focus everywhere (WCAG) instead of
           relying on Bootstrap's default per-component focus styling. */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible,
        textarea:focus-visible, [tabindex]:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
            border-radius: 4px;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; }
        }

        /* ---------- Layout shell ---------- */
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, var(--ink-900), var(--ink-800)); }
        .sidebar, .offcanvas.text-bg-dark { background: linear-gradient(180deg, var(--ink-900), var(--ink-800)) !important; }
        .sidebar .nav-link, .offcanvas .nav-link {
            color: #AEBBD1; font-weight: 500; font-size: .875rem; border-radius: var(--radius-sm);
            padding: .55rem .75rem; transition: background-color .15s ease, color .15s ease;
        }
        .sidebar .nav-link i, .offcanvas .nav-link i { font-size: 1rem; width: 1.1rem; text-align: center; color: #7E8FAC; transition: color .15s ease; }
        .sidebar .nav-link:hover, .offcanvas .nav-link:hover { color: #fff; background: rgba(255, 255, 255, .06); }
        .sidebar .nav-link:hover i, .offcanvas .nav-link:hover i { color: #fff; }
        .sidebar .nav-link.active, .offcanvas .nav-link.active {
            color: #fff; background: rgba(var(--accent-rgb), .18); font-weight: 600;
            box-shadow: inset 2.5px 0 0 var(--accent);
        }
        .sidebar .nav-link.active i, .offcanvas .nav-link.active i { color: #34D399; }
        .brand-mark { color: var(--accent); }
        .card-metric { border: none; border-radius: var(--radius); }
        .card-metric .value { font-size: 1.7rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .badge-status { text-transform: capitalize; font-weight: 600; letter-spacing: -.005em; }
        .money { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .money-symbol { opacity: .65; }
        table.table .money { display: inline-block; }
        /* Column-group shading for wide financial tables (earnings vs statutory deductions) */
        .table-group-earnings { background-color: rgba(var(--accent-rgb), .045); }
        .table-group-deductions { background-color: rgba(220, 38, 38, .04); }
        .table .net-pay-cell { background-color: rgba(var(--brand-rgb), .05); font-weight: 700; }
        /* Disabled-with-spinner submit buttons should still read clearly */
        button[disabled] .spinner-border { vertical-align: -0.15em; }

        /* ---------- Cards ---------- */
        .card { border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-xs); }
        .card.shadow-sm { box-shadow: var(--shadow-sm) !important; }
        .card-header { background: transparent; border-bottom: 1px solid var(--border); font-weight: 600; padding: .95rem 1.15rem; }
        .card-body { padding: 1.15rem; }

        /* ---------- Buttons ----------
           Bootstrap 5.3 bakes each variant's colors into its own local
           --bs-btn-* custom properties at build time (literal hex, not a
           var() reference to --bs-primary/--bs-success/etc), so re-pointing
           the theme-level tokens above doesn't reach buttons - every variant
           actually used in the app needs its --bs-btn-* set explicitly. */
        .btn { font-weight: 600; font-size: .875rem; border-radius: var(--radius-sm); padding: .5rem 1rem; transition: all .15s ease; }
        .btn-sm { padding: .3rem .7rem; font-size: .8125rem; }

        .btn-primary {
            --bs-btn-bg: var(--brand); --bs-btn-border-color: var(--brand);
            --bs-btn-hover-bg: var(--brand-dark); --bs-btn-hover-border-color: var(--brand-dark);
            --bs-btn-active-bg: var(--brand-dark); --bs-btn-active-border-color: var(--brand-dark);
            --bs-btn-disabled-bg: var(--brand); --bs-btn-disabled-border-color: var(--brand);
            --bs-btn-focus-shadow-rgb: var(--brand-rgb);
            box-shadow: 0 1px 2px rgba(var(--brand-rgb), .3);
        }
        .btn-primary:hover, .btn-primary:focus { box-shadow: 0 4px 10px rgba(var(--brand-rgb), .28); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        .btn-success {
            --bs-btn-bg: var(--accent); --bs-btn-border-color: var(--accent);
            --bs-btn-hover-bg: var(--accent-dark); --bs-btn-hover-border-color: var(--accent-dark);
            --bs-btn-active-bg: var(--accent-dark); --bs-btn-active-border-color: var(--accent-dark);
            --bs-btn-disabled-bg: var(--accent); --bs-btn-disabled-border-color: var(--accent);
            --bs-btn-focus-shadow-rgb: var(--accent-rgb);
        }
        .btn-danger {
            --bs-btn-bg: var(--danger); --bs-btn-border-color: var(--danger);
            --bs-btn-hover-bg: #B91C1C; --bs-btn-hover-border-color: #B91C1C;
            --bs-btn-active-bg: #B91C1C; --bs-btn-active-border-color: #B91C1C;
            --bs-btn-disabled-bg: var(--danger); --bs-btn-disabled-border-color: var(--danger);
            --bs-btn-focus-shadow-rgb: var(--danger-rgb);
        }
        .btn-outline-primary {
            --bs-btn-color: var(--brand); --bs-btn-border-color: var(--border-strong);
            --bs-btn-hover-bg: var(--brand); --bs-btn-hover-border-color: var(--brand);
            --bs-btn-active-bg: var(--brand); --bs-btn-active-border-color: var(--brand);
            --bs-btn-focus-shadow-rgb: var(--brand-rgb);
        }
        .btn-outline-secondary {
            --bs-btn-color: var(--text-muted); --bs-btn-border-color: var(--border-strong);
            --bs-btn-hover-bg: var(--surface-alt); --bs-btn-hover-color: var(--text); --bs-btn-hover-border-color: var(--border-strong);
            --bs-btn-active-bg: var(--surface-alt); --bs-btn-active-color: var(--text); --bs-btn-active-border-color: var(--border-strong);
        }
        .btn-outline-success {
            --bs-btn-color: var(--accent-dark); --bs-btn-border-color: #A7F3D0;
            --bs-btn-hover-bg: var(--accent); --bs-btn-hover-border-color: var(--accent);
            --bs-btn-active-bg: var(--accent); --bs-btn-active-border-color: var(--accent);
            --bs-btn-focus-shadow-rgb: var(--accent-rgb);
        }
        .btn-outline-danger {
            --bs-btn-color: #B91C1C; --bs-btn-border-color: #FECACA;
            --bs-btn-hover-bg: var(--danger); --bs-btn-hover-border-color: var(--danger);
            --bs-btn-active-bg: var(--danger); --bs-btn-active-border-color: var(--danger);
            --bs-btn-focus-shadow-rgb: var(--danger-rgb);
        }
        .btn-link { color: var(--text-muted); font-weight: 600; }
        .btn-link:hover { color: var(--brand); }

        /* ---------- Forms ---------- */
        .form-label { font-weight: 600; font-size: .8125rem; color: var(--text); margin-bottom: .35rem; }
        .form-control, .form-select { border-color: var(--border-strong); border-radius: var(--radius-sm); padding: .5rem .75rem; font-size: .875rem; }
        .form-control:focus, .form-select:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12); }
        .form-control::placeholder { color: var(--text-subtle); }
        .form-control-sm, .form-select-sm { border-radius: .4375rem; }
        .form-text { color: var(--text-subtle); font-size: .78rem; }
        .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }
        .form-check-input:focus { box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .15); }
        .input-group-text { border-color: var(--border-strong); }

        /* ---------- Tables ---------- */
        .table { --bs-table-bg: transparent; color: var(--text); }
        .table thead th {
            text-transform: uppercase; font-size: .68rem; font-weight: 700; letter-spacing: .05em;
            color: var(--text-subtle); border-bottom: 1px solid var(--border-strong); padding: .7rem 1rem;
            background: var(--surface-alt);
        }
        .table td { padding: .7rem 1rem; vertical-align: middle; border-bottom: 1px solid var(--border); font-size: .875rem; }
        .table > :not(caption) > * > * { box-shadow: none; }
        .table-hover > tbody > tr:hover > * { background-color: var(--surface-alt); }
        .table-sm td, .table-sm th { padding: .5rem .75rem; }

        /* ---------- Badges / status pills (soft-tint SaaS style) ---------- */
        .badge { font-weight: 600; letter-spacing: -.005em; }
        .badge.rounded-pill.badge-status { padding: .32rem .7rem; font-size: .74rem; }
        .badge-status.text-bg-success { background: var(--accent-light) !important; color: var(--accent-dark) !important; }
        .badge-status.text-bg-danger { background: #FEF2F2 !important; color: #B91C1C !important; }
        .badge-status.text-bg-warning { background: #FFFBEB !important; color: #B45309 !important; }
        .badge-status.text-bg-info { background: #EFF6FF !important; color: #1D4ED8 !important; }
        .badge-status.text-bg-secondary { background: var(--surface-alt) !important; color: var(--text-muted) !important; }
        .badge-status.text-bg-dark { background: var(--brand-light) !important; color: var(--brand) !important; }

        /* ---------- Icon-badge KPI cards (colored circle icon + number) ---------- */
        .icon-badge { width: 46px; height: 46px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .icon-badge-teal { background: var(--accent-light); color: var(--accent-dark); }
        .icon-badge-blue { background: #EFF6FF; color: var(--info); }
        .icon-badge-purple { background: #F5F3FF; color: #7C3AED; }
        .icon-badge-orange { background: #FFF7ED; color: #EA580C; }
        .icon-badge-pink { background: #FDF2F8; color: #DB2777; }

        /* ---------- Initials avatar (App\Views\Components\Avatar) ---------- */
        .avatar-circle { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: .78rem; flex-shrink: 0; line-height: 1; box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .35); }
        .avatar-circle-sm { width: 27px; height: 27px; font-size: .65rem; }
        .avatar-circle-lg { width: 64px; height: 64px; font-size: 1.3rem; }

        /* ---------- Dashboard KPI cards (x-kpi-card) ---------- */
        .kpi-card { border: 1px solid var(--border); border-radius: var(--radius); transition: box-shadow .18s ease, transform .18s ease; }
        .kpi-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .kpi-label { text-transform: uppercase; color: var(--text-subtle); font-size: .68rem; font-weight: 700; letter-spacing: .05em; }
        .kpi-value { font-size: 1.65rem; font-weight: 800; font-variant-numeric: tabular-nums; margin-top: .15rem; line-height: 1.2; color: var(--text); }
        .kpi-caption { font-size: .75rem; color: var(--text-muted) !important; }

        .brand-logo { width: 32px; height: 32px; object-fit: contain; border-radius: 50%; background: #fff; padding: 2px; flex-shrink: 0; }

        /* Sidebar section group labels (OVERVIEW / PAYROLL / ...) */
        .sidebar-group-label { color: #62759A; font-size: .66rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; padding: 0 .75rem; margin-top: 1.35rem; margin-bottom: .35rem; }
        .sidebar-group-label:first-child { margin-top: 0; }
        .sidebar .nav-badge, .offcanvas .nav-badge { font-size: .64rem; font-weight: 700; }

        /* ---------- Approval workflow horizontal stepper (payroll-cycles.show) ---------- */
        .approval-stepper { display: flex; align-items: flex-start; }
        .approval-step { flex: 1; text-align: center; position: relative; }
        .approval-step:not(:last-child)::after { content: ''; position: absolute; top: 19px; left: 50%; width: 100%; height: 2px; background: var(--border-strong); z-index: 0; }
        .approval-step.is-done:not(:last-child)::after { background: var(--accent); }
        .approval-step .step-icon { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; position: relative; z-index: 1; font-size: 1.1rem; background: #fff; border: 2px solid var(--border-strong); color: var(--text-subtle); box-shadow: var(--shadow-xs); }
        .approval-step.is-done .step-icon { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
        .approval-step.is-current .step-icon { border-color: var(--warning); color: var(--warning); background: #FFFBEB; box-shadow: 0 0 0 4px rgba(var(--warning-rgb), .12); }
        .approval-step .step-title { font-size: .82rem; font-weight: 700; margin-top: .5rem; color: var(--text); }
        .approval-step .step-meta { font-size: .72rem; color: var(--text-muted); }

        /* ---------- Empty states ---------- */
        .empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--text-subtle); }
        .empty-state i { font-size: 1.8rem; color: var(--border-strong); display: block; margin-bottom: .5rem; }
        .empty-state .empty-title { color: var(--text-muted); font-weight: 600; font-size: .875rem; }

        /* ---------- Topbar ---------- */
        .app-topbar { background: var(--surface); border-bottom: 1px solid var(--border); }
        .topbar-search { background: var(--surface-alt); border-radius: 999px; border: 1px solid transparent; transition: border-color .15s ease, background-color .15s ease; }
        .topbar-search:focus-within { background: var(--surface); border-color: var(--border-strong); }
        .topbar-search input { background: transparent; border: none; box-shadow: none !important; }
        .topbar-search .input-group-text { background: transparent; border: none; }

        /* ---------- Nav pills used outside the sidebar (e.g. filter tabs) ---------- */
        .nav-pills .nav-link.active { background-color: var(--brand); }

        /* ---------- Pagination ---------- */
        .page-link { color: var(--brand); border-color: var(--border); }
        .page-item.active .page-link { background-color: var(--brand); border-color: var(--brand); }

        /* ---------- Alerts (flash messages) ---------- */
        .alert { border-radius: var(--radius); border: 1px solid transparent; font-size: .875rem; }
        .alert-success { background: var(--accent-light); border-color: #A7F3D0; color: #065F46; }
        .alert-warning { background: #FFFBEB; border-color: #FDE68A; color: #92400E; }
        .alert-danger { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }
        .alert-info { background: #EFF6FF; border-color: #BFDBFE; color: #1E40AF; }

        @media (max-width: 991.98px) {
            main { padding: 1rem !important; }
            .kpi-value { font-size: 1.4rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
@auth
<div class="d-flex">
    {{-- Desktop sidebar (lg and up) --}}
    <nav class="sidebar p-3 d-none d-lg-flex flex-column" style="width: 248px; flex-shrink: 0;">
        <div class="text-white mb-3 pb-3 d-flex align-items-center gap-2" style="border-bottom: 1px solid rgba(255,255,255,.08);">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="brand-logo">
            <div style="min-width: 0;">
                <div class="fw-bold" style="font-size: .9rem; line-height: 1.2;">Payroll</div>
                <div style="font-size: .68rem; color: #7E8FAC; line-height: 1.2;" class="text-truncate">{{ config('app.name') }}</div>
            </div>
        </div>
        @include('layouts.partials.nav-links')
    </nav>

    {{-- Mobile off-canvas sidebar (below lg) --}}
    <div class="offcanvas offcanvas-start text-bg-dark" style="width: 248px;" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header" style="border-bottom: 1px solid rgba(255,255,255,.08);">
            <span class="mb-0 d-flex align-items-center gap-2" id="sidebarOffcanvasLabel">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="brand-logo">
                <span class="fw-bold" style="font-size: .9rem;">Payroll</span>
            </span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-3 pt-3">
            @include('layouts.partials.nav-links')
        </div>
    </div>

    <div class="flex-grow-1" style="min-width: 0;">
        <nav class="app-topbar navbar navbar-expand px-3 px-md-4 py-2">
            <button class="btn btn-outline-secondary d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-text fw-bold" style="font-size: 1.05rem; color: var(--text);">@yield('title', 'Dashboard')</span>
            <form method="GET" action="{{ route('employees.index') }}" class="ms-4 d-none d-md-block" style="width: 280px;">
                <div class="input-group input-group-sm topbar-search">
                    <span class="input-group-text"><i class="bi bi-search" style="color: var(--text-subtle);"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search employees...">
                </div>
            </form>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="d-none d-sm-flex align-items-center gap-2">
                    <x-avatar :name="auth()->user()->name" size="sm" />
                    <div class="lh-sm">
                        <div class="fw-semibold" style="font-size: .8rem;">{{ auth()->user()->name }}</div>
                        <div class="text-muted" style="font-size: .7rem;">{{ auth()->user()->roleLabel() }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit" title="Logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>
        </nav>
        <main class="p-4">
            @if (session('status'))
                <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle"></i> <div>{{ session('status') }}</div></div>
            @endif
            @if (session('policy_warning'))
                <div class="alert alert-warning d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle"></i> <div><strong>Compensation Policy:</strong> {{ session('policy_warning') }}</div></div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-exclamation-triangle"></i> <strong>Please fix the following:</strong></div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@else
    @yield('content')
@endauth
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    $(function () {
        // Tables that have no server-side pagination/search of their own get the
        // full DataTables experience (search box, sorting, client-side paging).
        $('.data-table').DataTable();

        // Tables that already have a Laravel paginator and/or a custom filter
        // form above them only get column sorting — no duplicate search box,
        // no duplicate pagination controls fighting the server-side ones.
        $('.data-table-nopage').DataTable({
            paging: false,
            info: false,
            searching: false,
            lengthChange: false,
        });
    });

    // Global "don't let people double-click their way into double-posting
    // payroll" handling: every state-changing (POST/PUT/DELETE-via-POST) form
    // gets a confirmation (if it opts in via data-confirm) and its submit
    // button disabled + spinner while the request is in flight.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post') {
                return;
            }

            form.addEventListener('submit', function (event) {
                var confirmMessage = form.dataset.confirm;
                if (confirmMessage && !window.confirm(confirmMessage)) {
                    event.preventDefault();
                    return;
                }

                var submitButton = form.querySelector('button[type="submit"], button:not([type])');
                if (submitButton && !submitButton.disabled) {
                    var label = submitButton.innerHTML;
                    submitButton.setAttribute('data-original-html', label);
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Please wait&hellip;';
                }
            });
        });
    });
</script>
@stack('scripts')
</body>
</html>
