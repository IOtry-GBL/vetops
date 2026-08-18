<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'VetCare Clinic')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    <style>
        :root {
            --shell-bg: #f3efe7;
            --shell-ink: #17313d;
            --shell-muted: #60707d;
            --shell-line: rgba(23, 49, 61, 0.12);
            --shell-panel: rgba(255, 255, 255, 0.84);
            --shell-panel-strong: #ffffff;
            --shell-accent: #0f8b8d;
            --shell-accent-deep: #0d6475;
            --shell-warm: #d67c4a;
            --shell-success: #198754;
            --shell-warning: #b7791f;
            --shell-danger: #c44f43;
            --shell-shadow: 0 20px 55px rgba(23, 49, 61, 0.12);
            --shell-radius-xl: 28px;
            --shell-radius-lg: 22px;
            --shell-radius-md: 16px;
            --shell-radius-sm: 12px;
            --font-body: "Aptos", "Segoe UI", "Trebuchet MS", sans-serif;
            --font-heading: "Aptos Display", "Trebuchet MS", "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(15, 139, 141, 0.16), transparent 26rem),
                radial-gradient(circle at right center, rgba(214, 124, 74, 0.14), transparent 28rem),
                linear-gradient(180deg, #f8f4ed 0%, #eef3f5 100%);
            color: var(--shell-ink);
            font-family: var(--font-body);
            line-height: 1.55;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .app-shell {
            display: grid;
            grid-template-columns: 290px minmax(0, 1fr);
            gap: 1.5rem;
            padding: 1.5rem;
            min-height: 100vh;
        }

        .app-sidebar {
            position: sticky;
            top: 1.5rem;
            align-self: start;
            min-height: calc(100vh - 3rem);
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.42);
            border-radius: calc(var(--shell-radius-xl) + 6px);
            background:
                linear-gradient(180deg, rgba(19, 49, 61, 0.96) 0%, rgba(15, 68, 84, 0.96) 100%);
            color: rgba(255, 255, 255, 0.88);
            box-shadow: var(--shell-shadow);
            overflow: hidden;
        }

        .app-sidebar::before {
            content: "";
            position: absolute;
            inset: auto -3rem -6rem auto;
            width: 14rem;
            height: 14rem;
            border-radius: 50%;
            background: rgba(214, 124, 74, 0.16);
            filter: blur(20px);
            pointer-events: none;
        }

        .sidebar-brand {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 0.45rem;
            padding-bottom: 1.35rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-brand strong {
            font: 700 1.2rem/1.1 var(--font-heading);
            letter-spacing: 0.03em;
        }

        .sidebar-brand span {
            color: rgba(255, 255, 255, 0.66);
            font-size: 0.92rem;
        }

        .sidebar-nav {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 0.5rem;
            padding-top: 1.25rem;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.95rem 1rem;
            border-radius: 999px;
            color: rgba(255, 255, 255, 0.8);
            transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            transform: translateX(2px);
        }

        .sidebar-item.active {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.14), rgba(214, 124, 74, 0.2));
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
        }

        .sidebar-foot {
            position: relative;
            z-index: 1;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.66);
            font-size: 0.9rem;
        }

        .app-main {
            min-width: 0;
        }

        .topbar {
            position: sticky;
            top: 1.5rem;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.15rem 1rem 1.4rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.52);
            border-radius: calc(var(--shell-radius-xl) + 2px);
            background: rgba(255, 255, 255, 0.76);
            backdrop-filter: blur(18px);
            box-shadow: 0 16px 38px rgba(23, 49, 61, 0.09);
        }

        .topbar-copy {
            display: grid;
            gap: 0.18rem;
        }

        .topbar-eyebrow {
            color: var(--shell-muted);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .topbar-title {
            font: 700 1.32rem/1.1 var(--font-heading);
            color: var(--shell-ink);
        }

        .profile-menu {
            position: relative;
        }

        .profile-menu summary {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.55rem 0.75rem 0.55rem 0.55rem;
            border-radius: 999px;
            background: rgba(15, 139, 141, 0.08);
            cursor: pointer;
            user-select: none;
        }

        .profile-menu summary::-webkit-details-marker {
            display: none;
        }

        .profile-avatar {
            width: 2.55rem;
            height: 2.55rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--shell-accent), var(--shell-accent-deep));
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .profile-meta {
            display: grid;
            text-align: left;
        }

        .profile-meta strong {
            font-size: 0.96rem;
            color: var(--shell-ink);
        }

        .profile-meta span {
            color: var(--shell-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 0.7rem);
            right: 0;
            min-width: 230px;
            padding: 0.6rem;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-md);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 22px 48px rgba(23, 49, 61, 0.16);
        }

        .dropdown-link,
        .dropdown-button {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 0.95rem;
            border: none;
            border-radius: 14px;
            background: transparent;
            color: var(--shell-ink);
            cursor: pointer;
            text-align: left;
            transition: background 0.18s ease, color 0.18s ease;
        }

        .dropdown-link:hover,
        .dropdown-button:hover {
            background: rgba(15, 139, 141, 0.08);
        }

        .dropdown-divider {
            height: 1px;
            margin: 0.45rem 0;
            background: var(--shell-line);
        }

        .page-content {
            display: grid;
            gap: 1.4rem;
        }

        .hero-card,
        .card,
        .widget,
        .surface {
            position: relative;
            background: var(--shell-panel);
            border: 1px solid rgba(255, 255, 255, 0.54);
            border-radius: var(--shell-radius-xl);
            box-shadow: var(--shell-shadow);
            backdrop-filter: blur(14px);
        }

        .hero-card,
        .card {
            padding: 1.55rem;
        }

        .hero-card {
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(214, 124, 74, 0.16), transparent 18rem),
                radial-gradient(circle at left bottom, rgba(15, 139, 141, 0.14), transparent 16rem),
                rgba(255, 255, 255, 0.82);
        }

        .hero-card::after {
            content: "";
            position: absolute;
            inset: auto -4rem -4rem auto;
            width: 11rem;
            height: 11rem;
            border-radius: 50%;
            background: rgba(23, 49, 61, 0.05);
        }

        .hero-row,
        .section-head,
        .split-head,
        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-copy,
        .section-copy,
        .muted-copy {
            color: var(--shell-muted);
        }

        .hero-title,
        h1,
        h2,
        h3,
        h4 {
            font-family: var(--font-heading);
            color: var(--shell-ink);
        }

        .hero-title {
            font-size: clamp(2rem, 3vw, 2.7rem);
            line-height: 1.02;
            letter-spacing: -0.03em;
        }

        .hero-copy {
            max-width: 48rem;
            margin-top: 0.7rem;
            font-size: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            min-height: 46px;
            padding: 0.82rem 1.2rem;
            border: none;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--shell-accent), var(--shell-accent-deep));
            color: #fff;
            box-shadow: 0 14px 28px rgba(15, 100, 117, 0.24);
        }

        .btn-secondary {
            background: rgba(23, 49, 61, 0.08);
            color: var(--shell-ink);
        }

        .btn-danger {
            background: linear-gradient(135deg, #d56557, var(--shell-danger));
            color: #fff;
            box-shadow: 0 14px 28px rgba(196, 79, 67, 0.22);
        }

        .grid,
        .stat-grid,
        .metric-grid,
        .info-grid,
        .form-grid {
            display: grid;
            gap: 1rem;
        }

        .grid,
        .stat-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .metric-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .info-grid {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .form-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .widget,
        .metric-card,
        .stat-card,
        .detail-card,
        .detail-panel,
        .summary-card {
            padding: 1.15rem;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(255, 255, 255, 0.88);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.58);
        }

        .widget-title,
        .metric-label,
        .detail-label,
        .summary-label,
        .eyebrow {
            display: block;
            color: var(--shell-muted);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 0.45rem;
        }

        .widget-value,
        .metric-value {
            font: 700 clamp(1.7rem, 2.4vw, 2.4rem)/1 var(--font-heading);
            color: var(--shell-ink);
            letter-spacing: -0.03em;
        }

        .stack {
            display: grid;
            gap: 1rem;
        }

        .list-grid {
            display: grid;
            gap: 1rem;
        }

        .list-card,
        .record-card,
        .session-card,
        .appointment-card {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: start;
            padding: 1.2rem;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: var(--shell-panel-strong);
            box-shadow: 0 10px 28px rgba(23, 49, 61, 0.06);
        }

        .item-title {
            font-size: 1.02rem;
            font-weight: 700;
        }

        .item-copy,
        .item-meta,
        .detail-copy {
            color: var(--shell-muted);
        }

        .item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-top: 0.65rem;
        }

        .pill,
        .badge,
        .count-chip,
        .mini-chip,
        .status-badge,
        .status-pill,
        .issued-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .pill,
        .badge,
        .count-chip,
        .mini-chip,
        .issued-chip {
            background: rgba(15, 139, 141, 0.1);
            color: var(--shell-accent-deep);
        }

        .pill-success,
        .badge-monitor,
        .status-complete {
            background: rgba(25, 135, 84, 0.14);
            color: #116d44;
        }

        .pill-warning,
        .badge-visit,
        .status-pending {
            background: rgba(183, 121, 31, 0.14);
            color: #8d5e12;
        }

        .pill-danger,
        .badge-emergency,
        .status-danger {
            background: rgba(196, 79, 67, 0.14);
            color: #9f372d;
        }

        .pill-neutral,
        .status-open,
        .status-approved {
            background: rgba(13, 100, 117, 0.14);
            color: var(--shell-accent-deep);
        }

        .surface-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .empty-state {
            padding: 1.35rem;
            border: 1px dashed rgba(23, 49, 61, 0.18);
            border-radius: var(--shell-radius-lg);
            color: var(--shell-muted);
            text-align: center;
            background: rgba(255, 255, 255, 0.54);
        }

        .field {
            display: grid;
            gap: 0.48rem;
        }

        .field-label,
        .form-label {
            font-weight: 700;
            color: var(--shell-ink);
        }

        .field-control,
        .form-field {
            width: 100%;
            min-height: 50px;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(23, 49, 61, 0.16);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.94);
            color: var(--shell-ink);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        textarea.field-control,
        textarea.form-field {
            min-height: 140px;
            resize: vertical;
        }

        .field-control:focus,
        .form-field:focus {
            outline: none;
            border-color: rgba(15, 139, 141, 0.55);
            box-shadow: 0 0 0 4px rgba(15, 139, 141, 0.12);
        }

        .field-error {
            color: var(--shell-danger);
            font-size: 0.84rem;
        }

        .banner,
        .alert {
            padding: 1rem 1.05rem;
            border-radius: 18px;
            border: 1px solid var(--shell-line);
            background: rgba(255, 255, 255, 0.86);
        }

        .alert-info,
        .banner-info {
            border-color: rgba(15, 139, 141, 0.18);
            background: rgba(15, 139, 141, 0.1);
            color: var(--shell-accent-deep);
        }

        .banner-danger {
            border-color: rgba(196, 79, 67, 0.18);
            background: rgba(196, 79, 67, 0.1);
            color: #8d3026;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(255, 255, 255, 0.88);
        }

        .workspace-grid {
            display: grid;
            grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .thread-list {
            display: grid;
            gap: 0.85rem;
        }

        .thread-link {
            display: grid;
            gap: 0.65rem;
            padding: 1rem;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(255, 255, 255, 0.9);
            color: var(--shell-ink);
            transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .thread-link:hover {
            transform: translateY(-1px);
            border-color: rgba(15, 139, 141, 0.25);
            box-shadow: 0 14px 30px rgba(23, 49, 61, 0.08);
        }

        .thread-link.active {
            border-color: rgba(15, 139, 141, 0.34);
            background: linear-gradient(180deg, rgba(15, 139, 141, 0.1), rgba(255, 255, 255, 0.95));
            box-shadow: 0 18px 34px rgba(15, 100, 117, 0.1);
        }

        .thread-link-head,
        .thread-link-foot {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .thread-link-copy {
            display: grid;
            gap: 0.2rem;
            min-width: 0;
        }

        .thread-link-title {
            font-weight: 700;
        }

        .thread-link-subtitle,
        .thread-link-preview {
            color: var(--shell-muted);
            font-size: 0.92rem;
        }

        .thread-link-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .detail-grid-compact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem;
        }

        .chat-shell {
            display: grid;
            gap: 1rem;
        }

        .chat-window {
            display: grid;
            gap: 1rem;
        }

        .message-stream {
            display: grid;
            gap: 0.85rem;
            max-height: 620px;
            padding-right: 0.25rem;
            overflow-y: auto;
        }

        .message-row {
            display: flex;
            justify-content: flex-start;
        }

        .message-row.outgoing {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: min(40rem, 100%);
            padding: 0.95rem 1rem;
            border: 1px solid rgba(23, 49, 61, 0.1);
            border-radius: 22px 22px 22px 10px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 12px 24px rgba(23, 49, 61, 0.06);
        }

        .message-row.outgoing .message-bubble {
            border-radius: 22px 22px 10px 22px;
            background: linear-gradient(135deg, rgba(15, 139, 141, 0.15), rgba(13, 100, 117, 0.2));
            border-color: rgba(13, 100, 117, 0.12);
        }

        .message-bubble.staff {
            background: linear-gradient(135deg, rgba(214, 124, 74, 0.12), rgba(255, 255, 255, 0.95));
        }

        .message-bubble.veterinarian {
            background: linear-gradient(135deg, rgba(15, 139, 141, 0.12), rgba(255, 255, 255, 0.95));
        }

        .message-bubble.pet-owner {
            background: rgba(255, 255, 255, 0.96);
        }

        .message-row.outgoing .message-bubble,
        .message-row.outgoing .message-bubble.staff,
        .message-row.outgoing .message-bubble.veterinarian,
        .message-row.outgoing .message-bubble.pet-owner {
            background: linear-gradient(135deg, rgba(15, 139, 141, 0.15), rgba(13, 100, 117, 0.2));
            border-color: rgba(13, 100, 117, 0.12);
        }

        .message-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.45rem;
            color: var(--shell-muted);
            font-size: 0.78rem;
            letter-spacing: 0.02em;
        }

        .message-body {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .composer-card {
            padding: 1rem;
            border: 1px solid var(--shell-line);
            border-radius: var(--shell-radius-lg);
            background: rgba(255, 255, 255, 0.92);
        }

        .summary-panel {
            padding: 1rem 1.1rem;
            border: 1px solid rgba(15, 139, 141, 0.12);
            border-radius: var(--shell-radius-lg);
            background: rgba(15, 139, 141, 0.08);
        }

        .summary-panel.warning {
            border-color: rgba(183, 121, 31, 0.14);
            background: rgba(183, 121, 31, 0.08);
        }

        .app-table {
            width: 100%;
            border-collapse: collapse;
        }

        .app-table th,
        .app-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(23, 49, 61, 0.08);
        }

        .app-table th {
            color: var(--shell-muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: rgba(23, 49, 61, 0.03);
        }

        .app-table tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 1100px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .app-sidebar {
                position: static;
                min-height: auto;
            }

            .workspace-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .app-shell {
                padding: 1rem;
                gap: 1rem;
            }

            .topbar,
            .hero-row,
            .section-head,
            .split-head,
            .action-row,
            .list-card,
            .record-card,
            .session-card,
            .appointment-card {
                flex-direction: column;
            }

            .profile-meta {
                display: none;
            }

            .app-sidebar {
                padding: 1rem;
            }

            .sidebar-nav {
                grid-template-columns: 1fr;
            }

            .hero-card,
            .card {
                padding: 1.2rem;
            }
        }
    </style>
    @yield('styles')
    @stack('appointment-picker-styles')
</head>
<body>
    @php
        $currentUser = auth()->user();
        $roleLabel = $currentUser?->role ?? 'User';
        $initials = collect(explode(' ', trim((string) ($currentUser?->name ?? 'User'))))
            ->filter()
            ->take(2)
            ->map(fn ($segment) => strtoupper(substr($segment, 0, 1)))
            ->implode('');
    @endphp

    <div class="app-shell">
        @auth
            <aside class="app-sidebar">
                <div class="sidebar-brand">
                    <strong>VetCare Clinic</strong>
                    <span>Clinical workspace for appointments, records, and prescriptions.</span>
                </div>

                <nav class="sidebar-nav">
                    @yield('sidebar')
                </nav>

                <div class="sidebar-foot">
                    Signed in as {{ $roleLabel }}.
                </div>
            </aside>
        @endauth

        <div class="app-main">
            <header class="topbar">
                <div class="topbar-copy">
                    <span class="topbar-eyebrow">Care Operations</span>
                    <strong class="topbar-title">@yield('title', 'VetCare Clinic')</strong>
                </div>

                @auth
                    <details class="profile-menu">
                        <summary>
                            <span class="profile-avatar">{{ $initials ?: 'VC' }}</span>
                            <span class="profile-meta">
                                <strong>{{ $currentUser->name }}</strong>
                                <span>{{ $roleLabel }}</span>
                            </span>
                        </summary>

                        <div class="profile-dropdown">
                            <a href="{{ route('profile.settings') }}" class="dropdown-link">Profile Settings</a>
                            <div class="dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-button">Logout</button>
                            </form>
                        </div>
                    </details>
                @endauth
            </header>

            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>
     @livewireScripts

    <script>
        function openPetCareAI(event) {
            event.preventDefault();
            
            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            
            // Create modal container
            const modal = document.createElement('div');
            modal.style.cssText = `
                background: white;
                border-radius: 12px;
                width: 90%;
                max-width: 600px;
                max-height: 80vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 55px rgba(23, 49, 61, 0.3);
            `;
            
            // Create header
            const header = document.createElement('div');
            header.style.cssText = `
                padding: 1.5rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            `;
            header.innerHTML = `
                <h2 style="margin: 0; font-size: 1.25rem; color: #17313d;">Ask Pet Care AI</h2>
                <button onclick="this.closest('[data-modal]').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            `;
            
            // Create chat container
            const chatContainer = document.createElement('div');
            chatContainer.style.cssText = `
                flex: 1;
                overflow-y: auto;
                padding: 1.5rem;
                background: #f9fafb;
                display: flex;
                flex-direction: column;
                gap: 1rem;
            `;
            chatContainer.innerHTML = `
                <div style="padding: 1rem; background: #e0f7ff; border-radius: 8px; border-left: 4px solid #0f8b8d;">
                    <p style="margin: 0; color: #0d6475; font-weight: 500;">Welcome to Pet Care AI Assistant</p>
                    <p style="margin: 0.5rem 0 0 0; color: #0d6475; font-size: 0.9rem;">Ask me anything about pet health, medications, appointments, or general care tips!</p>
                </div>
                <div style="padding: 1rem; background: white; border-radius: 8px;">
                    <p style="margin: 0; color: #6b7280; font-size: 0.9rem;">I'm here to help with:</p>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; color: #6b7280; font-size: 0.9rem;">
                        <li>Pet health questions</li>
                        <li>Medication information</li>
                        <li>Appointment guidance</li>
                        <li>General pet care advice</li>
                    </ul>
                </div>
            `;
            
            // Create input area
            const inputArea = document.createElement('div');
            inputArea.style.cssText = `
                padding: 1rem;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 0.5rem;
            `;
            inputArea.innerHTML = `
                <input type="text" placeholder="Type your question..." style="
                    flex: 1;
                    padding: 0.75rem;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    font-family: inherit;
                    font-size: 0.95rem;
                " id="aiInputField">
                <button style="
                    padding: 0.75rem 1rem;
                    background: linear-gradient(135deg, #0f8b8d, #0d6475);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: opacity 0.2s;
                " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Send
                </button>
            `;
            
            // Assemble modal
            modal.appendChild(header);
            modal.appendChild(chatContainer);
            modal.appendChild(inputArea);
            
            overlay.appendChild(modal);
            overlay.setAttribute('data-modal', 'ai-chat');
            document.body.appendChild(overlay);
            
            // Focus input
            setTimeout(() => document.getElementById('aiInputField')?.focus(), 100);
            
            // Close on overlay click
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.remove();
            });
            
            // Close on ESC key
            const handleEsc = (e) => {
                if (e.key === 'Escape') {
                    overlay.remove();
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);
        }
    </script>
    @stack('appointment-picker-scripts')
    @yield('scripts')
</body>
</html>
