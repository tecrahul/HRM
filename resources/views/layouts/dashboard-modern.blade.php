<!DOCTYPE html>
@php
    $companyProfile = \App\Support\CompanyProfile::get();
    $brandFontStack = \App\Support\CompanyProfile::fontStack($companyProfile['brand_font_family'] ?? null);
    $brandAccent = \App\Support\CompanyProfile::accentColor();
    $brandAccentSoft = \App\Support\CompanyProfile::accentSoftColor();
    $brandAccentBorder = \App\Support\CompanyProfile::accentBorderColor();
    $brandSecondary = \App\Support\CompanyProfile::secondaryColor();
    $brandSecondarySoft = \App\Support\CompanyProfile::secondaryAccentSoftColor();
@endphp
<html lang="{{ str_replace('_', '-', $companyProfile['locale'] ?? 'en-US') }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trim($__env->yieldContent('title', 'Dashboard')) }} | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            /* Spacing scale (8px base) */
            --sp-xs: 4px;   /* 4 */
            --sp-sm: 8px;   /* 8 */
            --sp-md: 16px;  /* 16 */
            --sp-lg: 24px;  /* 24 */
            --sp-xl: 32px;  /* 32 */
            --sp-2xl: 40px; /* 40 */
            --sp-3xl: 48px; /* 48 */
            --hr-bg-base: #f6f3ff;
            --hr-bg-grad-a: #e8dbff;
            --hr-bg-grad-b: #ffd9eb;
            --hr-surface: rgb(255 255 255 / 0.88);
            --hr-surface-strong: #fff;
            --hr-line: rgb(231 226 244 / 0.95);
            --hr-text-main: #1f1a2e;
            --hr-text-muted: #6f668a;
            --hr-accent: {{ $brandAccent }};
            --hr-accent-soft: {{ $brandAccentSoft }};
            --hr-accent-border: {{ $brandAccentBorder }};
            --hr-font-family: <?php echo $brandFontStack; ?>;
            --hr-shadow-soft: 0 24px 46px -30px rgb(57 26 94 / 0.38);
            /* Slightly darker header/sidebar in light mode for better content contrast */
            --hr-header-bg: linear-gradient(180deg, rgb(2 8 23 / 0.06), rgb(2 8 23 / 0.02)), var(--hr-surface);
            --hr-sidebar-bg: linear-gradient(180deg, rgb(2 8 23 / 0.05), rgb(2 8 23 / 0.02)), var(--hr-surface);
            /* Sidebar scrollbars */
            --hr-scrollbar-thumb: rgba(255,255,255,0.18);
            --hr-scrollbar-thumb-hover: rgba(255,255,255,0.28);
            /* Subtle edge fades over sidebar content */
            --hr-scroll-fade-color: rgba(2,8,23,0.08);
        }

        html.dark {
            --hr-bg-base: #0b1326;
            --hr-bg-grad-a: #182c46;
            --hr-bg-grad-b: #2b1f44;
            --hr-surface: rgb(17 28 48 / 0.86);
            --hr-surface-strong: #131f36;
            --hr-line: rgb(125 150 185 / 0.24);
            --hr-text-main: #d7e6ff;
            --hr-text-muted: #8ea5c7;
            --hr-accent: {{ $brandSecondary }};
            --hr-accent-soft: {{ $brandSecondarySoft }};
            --hr-shadow-soft: 0 18px 40px -24px rgb(2 8 23 / 0.78);
            /* Keep header/sidebar parity in dark mode */
            --hr-header-bg: var(--hr-surface);
            --hr-sidebar-bg: var(--hr-surface);
            /* Dark-mode scrollbar + fades */
            --hr-scrollbar-thumb: rgba(255,255,255,0.22);
            --hr-scrollbar-thumb-hover: rgba(255,255,255,0.34);
            --hr-scroll-fade-color: rgba(255,255,255,0.08);
        }

        body.hrm-modern-body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--hr-font-family, "Manrope", ui-sans-serif, system-ui, sans-serif);
            background:
                radial-gradient(1100px 600px at -10% -10%, var(--hr-bg-grad-a), transparent 55%),
                radial-gradient(900px 600px at 110% 0%, var(--hr-bg-grad-b), transparent 55%),
                var(--hr-bg-base);
            color: var(--hr-text-main);
        }

        .hrm-modern-shell {
            min-height: 100vh;
            height: 100vh;
            display: grid;
            grid-template-columns: 232px minmax(0, 1fr);
            transition: grid-template-columns 220ms ease;
            overflow: hidden;
        }

        .hrm-modern-shell.is-collapsed {
            grid-template-columns: 72px minmax(0, 1fr);
        }

        /* Hide legacy sidebar when React sidebar is mounted */
        #hrmModernSidebar.js-sidebar-mounted > :not(#sidebar-root) {
            display: none !important;
        }

        /* Minimal, hover-reveal sidebar scrollbar (scoped) */
        .hrm-sidebar-scroll {
            /* Hide by default (Firefox + WebKit), show on hover */
            scrollbar-width: none; /* Firefox */
            scrollbar-color: transparent transparent; /* Firefox */
        }
        .hrm-sidebar-scroll:hover {
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: var(--hr-scrollbar-thumb) transparent; /* Firefox */
        }
        .hrm-sidebar-scroll::-webkit-scrollbar { width: 0; height: 0; }
        .hrm-sidebar-scroll:hover::-webkit-scrollbar { width: 4px; height: 4px; }
        .hrm-sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .hrm-sidebar-scroll::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 8px;
        }
        .hrm-sidebar-scroll:hover::-webkit-scrollbar-thumb {
            background: var(--hr-scrollbar-thumb);
            transition: background 160ms ease;
        }
        .hrm-sidebar-scroll:hover::-webkit-scrollbar-thumb:hover {
            background: var(--hr-scrollbar-thumb-hover);
        }

        .hrm-modern-surface {
            background: var(--hr-surface);
            border: 1px solid var(--hr-line);
            box-shadow: var(--hr-shadow-soft);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .hrm-modern-sidebar {
            border-right: 1px solid var(--hr-line);
            height: 100vh;
            overflow: hidden;
            background: var(--hr-sidebar-bg);
        }

        .hrm-sidebar-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
            /* Smooth, modern scrolling */
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
            will-change: scroll-position;
            transform: translateZ(0);
            position: relative; /* anchor gradient fades */
        }

        /* Optional premium fades to hint more content */
        .hrm-scroll-fade-top,
        .hrm-scroll-fade-bottom {
            pointer-events: none;
            position: sticky;
            z-index: 1;
            height: 16px;
            opacity: 1;
            transition: opacity 160ms ease;
        }
        .hrm-scroll-fade-top { top: 0; margin-bottom: -16px; background: linear-gradient(to bottom, var(--hr-scroll-fade-color), rgba(0,0,0,0)); }
        .hrm-scroll-fade-bottom { bottom: 0; margin-top: -16px; background: linear-gradient(to top, var(--hr-scroll-fade-color), rgba(0,0,0,0)); }
        .hrm-sidebar-scroll.is-at-top .hrm-scroll-fade-top { opacity: 0; }
        .hrm-sidebar-scroll.is-at-bottom .hrm-scroll-fade-bottom { opacity: 0; }

        .hrm-main-pane {
            min-height: 100vh;
            overflow: hidden;
        }

        .hrm-main-content {
            flex: 1 1 auto;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .hrm-modern-nav-link {
            border: 1px solid transparent;
            transition: all 160ms ease; /* smooth 150–200ms */
            /* Compact enterprise spacing: 10px vertical */
            padding-top: 0.625rem;
            padding-bottom: 0.625rem;
            min-height: 42px; /* do not exceed 46px */
            font-size: 0.86rem; /* Readable, compact */
            line-height: 1.25;
        }

        /* Ensure consistent 18–20px icon sizing */
        .hrm-modern-nav-link > svg,
        .hrm-modern-nav-link .hrm-nav-icon svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .hrm-modern-nav-link:hover {
            border-color: var(--hr-line);
            background: var(--hr-accent-soft);
        }

        /* Subtle icon motion on hover */
        .hrm-modern-nav-link:hover .hrm-nav-icon {
            transform: translateY(-1px) scale(1.05);
            transition: transform 150ms ease;
        }

        .hrm-modern-nav-link.is-active {
            border-color: var(--hr-accent-border);
            background: var(--hr-accent-soft);
            box-shadow: inset 3px 0 0 0 var(--hr-accent-border);
        }

        /* Compact list gaps */
        .hrm-modern-nav { gap: 0.25rem; /* 4px gap between items */ }

        /* Compact submenu spacing */
        .hrm-submenu-links {
            gap: 0.125rem; /* equals Tailwind gap-0.5 */
        }
        .hrm-submenu-links .hrm-modern-nav-link {
            /* Keep submenu compact with 8px vertical rhythm */
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            min-height: 36px;
            font-size: 0.82rem; /* Slightly smaller submenu font */
        }
        /* Ensure React-rendered submenu links also use 0.90rem */
        .hrm-modern-sidebar ul[id^="submenu-"] a {
            font-size: 0.82rem;
            padding-top: 0.5rem !important;  /* 8px */
            padding-bottom: 0.5rem !important;
            min-height: 36px;
        }
        /* Hover effect for submenu items rendered by React */
        .hrm-modern-sidebar ul[id^="submenu-"] a:hover {
            background: var(--hr-accent-soft);
        }

        .hrm-submenu-toggle {
            width: 100%;
            text-align: left;
        }

        .hrm-submenu-caret {
            margin-left: auto;
            transition: transform 160ms ease;
        }

        .hrm-submenu.is-open .hrm-submenu-caret {
            transform: rotate(180deg);
        }

        .hrm-modern-shell.is-collapsed .hrm-nav-label,
        .hrm-modern-shell.is-collapsed .hrm-brand-copy,
        .hrm-modern-shell.is-collapsed .hrm-sidebar-foot {
            display: none;
        }

        .hrm-modern-shell.is-collapsed .hrm-submenu-links,
        .hrm-modern-shell.is-collapsed .hrm-submenu-caret {
            display: none;
        }

        .hrm-modern-shell.is-collapsed .hrm-modern-nav {
            align-items: center;
        }

        .hrm-modern-shell.is-collapsed .hrm-modern-nav-link {
            justify-content: center;
        }

        /* Sidebar grouping + indentation */
        .hrm-nav-section-label {
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--hr-text-muted);
            padding: 0 var(--sp-sm, 8px);
        }

        .hrm-nav-l1 { padding-left: 16px; }
        .hrm-nav-l2 { padding-left: 32px; }
        .hrm-nav-l3 { padding-left: 48px; font-size: 0.78rem; }

        .hrm-nav-bullet { width: 6px; height: 6px; border-radius: 999px; background: currentColor; }

        .hrm-nav-group { display: flex; flex-direction: column; gap: 6px; }
        /* Section spacing: 16–20px range */
        .hrm-nav-group + .hrm-nav-group { margin-top: var(--sp-md, 16px); }

        /* Flyout for collapsed mode */
        .hrm-flyout { display: none; position: absolute; left: 100%; top: 0; min-width: 220px; border-radius: 12px; border: 1px solid var(--hr-line); background: var(--hr-surface-strong); box-shadow: 0 20px 38px -28px rgb(2 8 23 / 0.86); padding: 6px; z-index: var(--z-popover, 1200); }
        .hrm-flyout a { display: flex; align-items: center; gap: 8px; border-radius: 10px; padding: 8px 10px; font-size: 13px; font-weight: 600; color: var(--hr-text-main); }
        .hrm-flyout a:hover { background: var(--hr-accent-soft); }
        .hrm-has-children { position: relative; }
        .hrm-modern-shell.is-collapsed .hrm-has-children:hover .hrm-flyout { display: block; }
        .hrm-modern-shell.is-collapsed .hrm-nav-section-label { display: none; }
        .hrm-modern-shell.is-collapsed .hrm-nav-label, .hrm-modern-shell.is-collapsed .hrm-nav-caret { display: none; }

        .hrm-brand-logo-wrap {
            width: 12rem;
            height: 3rem;
            margin: 0 auto;
            border-radius: 1rem;
            border: 0;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: all 180ms ease;
        }

        .hrm-brand-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 0.625rem;
        }

        .hrm-brand-logo-fallback {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            color: var(--hr-accent);
            transition: all 180ms ease;
        }

        .hrm-modern-shell.is-collapsed .hrm-brand-logo-wrap {
            height: 3rem;
            border: 0;
            background: transparent;
            border-radius: 0;
        }

        .hrm-modern-shell.is-collapsed .hrm-brand-logo-img {
            padding: 0;
        }

        .hrm-modern-shell.is-collapsed .hrm-brand-logo-fallback {
            width: 2.25rem;
            height: 2.25rem;
            background: transparent;
            color: var(--hr-text-muted);
        }

        .hrm-modern-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .hrm-header-icon-btn {
            position: relative;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid var(--hr-line);
            background: var(--hr-surface-strong);
            color: var(--hr-text-muted);
            transition: all 160ms ease;
        }

        .hrm-header-icon-btn:hover {
            color: var(--hr-text-main);
            border-color: var(--hr-accent-border);
            background: var(--hr-accent-soft);
        }

        .hrm-header-alert-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 999px;
            border: 2px solid var(--hr-surface);
            background: #ef4444;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            line-height: 1;
            font-weight: 700;
        }

        .hrm-header-divider {
            width: 1px;
            height: 28px;
            background: var(--hr-line);
        }

        .hrm-profile-chip {
            border: 1px solid var(--hr-line);
            background: var(--hr-surface-strong);
            border-radius: 14px;
            padding: 5px 8px 5px 10px; /* tighter without feeling cramped */
        }

        .hrm-profile-trigger {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 160ms ease;
        }

        .hrm-profile-trigger:hover {
            border-color: var(--hr-accent-border);
            background: var(--hr-accent-soft);
        }

        .hrm-profile-menu {
            position: relative;
        }

        .hrm-profile-caret {
            transition: transform 160ms ease;
        }

        .hrm-profile-menu.is-open .hrm-profile-caret {
            transform: rotate(180deg);
        }

        .hrm-profile-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 190px;
            border-radius: 12px;
            border: 1px solid var(--hr-line);
            background: var(--hr-surface-strong);
            box-shadow: 0 20px 38px -28px rgb(2 8 23 / 0.86);
            padding: 6px;
            z-index: var(--z-popover, 1200);
        }

        .hrm-profile-dropdown-item {
            width: 100%;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--hr-text-main);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: left;
            transition: background 150ms ease, color 150ms ease;
        }

        .hrm-profile-dropdown-item:hover {
            background: var(--hr-accent-soft);
            color: var(--hr-text-main);
        }

        .hrm-notification-menu {
            position: relative;
        }

        .hrm-notification-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: min(360px, 92vw);
            border-radius: 12px;
            border: 1px solid var(--hr-line);
            background: var(--hr-surface-strong);
            box-shadow: 0 20px 38px -28px rgb(2 8 23 / 0.86);
            z-index: var(--z-popover, 1200);
            overflow: hidden;
        }

        .hrm-notification-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--hr-line);
        }

        .hrm-notification-list {
            max-height: 320px;
            overflow-y: auto;
            padding: 10px;
            display: grid;
            gap: 10px;
        }

        .hrm-notification-item {
            border: 1px solid var(--hr-line);
            border-radius: 10px;
            padding: 12px;
            background: var(--hr-surface);
            display: grid;
            gap: 8px;
        }

        .hrm-notification-item.is-unread {
            border-color: rgb(245 158 11 / 0.45);
        }

        .hrm-notification-foot {
            border-top: 1px solid var(--hr-line);
            padding: 10px 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .hrm-avatar-online-dot {
            position: absolute;
            right: -1px;
            bottom: -1px;
            width: 11px;
            height: 11px;
            border-radius: 999px;
            background: #22c55e;
            border: 2px solid var(--hr-surface-strong);
        }

        .ui-alert {
            border-radius: 12px;
            border: 1px solid var(--hr-line);
            padding: var(--sp-md) var(--sp-lg); /* 16 vertical, 24 horizontal */
            font-size: 14px;
            font-weight: 600;
            background: var(--hr-surface-strong);
            color: var(--hr-text-main);
            margin-bottom: var(--sp-md); /* 16px breathing room */
        }

        .ui-alert-success {
            border-color: rgb(34 197 94 / 0.36);
            background: rgb(34 197 94 / 0.1);
            color: #166534;
        }

        .ui-alert-danger {
            border-color: rgb(239 68 68 / 0.36);
            background: rgb(239 68 68 / 0.1);
            color: #991b1b;
        }

        html.dark .ui-alert-success {
            color: #86efac;
        }

        html.dark .ui-alert-danger {
            color: #fca5a5;
        }

        .ui-hero {
            border-radius: 16px;
            padding: var(--sp-lg);
            border: 1px solid var(--hr-line);
            background: linear-gradient(132deg, var(--hr-surface-strong), var(--hr-surface));
        }

        .ui-kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .ui-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .ui-kpi-grid.is-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .ui-kpi-grid.is-5 {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        @media (min-width: 768px) {
            .ui-kpi-grid.is-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .ui-kpi-card {
            border-radius: 16px;
            border: 1px solid var(--hr-line);
            background: var(--hr-surface);
            box-shadow: var(--hr-shadow-soft);
            padding: 16px;
        }

        .ui-kpi-label {
            font-size: 11px;
            line-height: 1.1;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--hr-text-muted);
        }

        .ui-kpi-value {
            margin-top: 8px;
            font-size: clamp(1.6rem, 2vw, 1.9rem);
            line-height: 1.1;
            font-weight: 800;
        }

        .ui-kpi-meta {
            margin-top: 10px;
            font-size: 12px;
            color: var(--hr-text-muted);
        }

        .ui-icon-chip {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ui-icon-blue {
            background: rgb(59 130 246 / 0.16);
            color: #2563eb;
        }

        .ui-icon-sky {
            background: rgb(14 165 233 / 0.16);
            color: #0284c7;
        }

        .ui-icon-amber {
            background: rgb(245 158 11 / 0.16);
            color: #d97706;
        }

        .ui-icon-green {
            background: rgb(34 197 94 / 0.16);
            color: #15803d;
        }

        .ui-icon-violet {
            background: rgb(124 58 237 / 0.16);
            color: #7c3aed;
        }

        .ui-icon-pink {
            background: rgb(236 72 153 / 0.16);
            color: #db2777;
        }

        .ui-section {
            border-radius: 16px;
            border: 1px solid var(--hr-line);
            background: var(--hr-surface);
            box-shadow: var(--hr-shadow-soft);
            padding: 20px;
        }
        /* vertical rhythm between major blocks */
        .ui-section + .ui-section,
        .ui-kpi-grid + .ui-section,
        .ui-section + .ui-kpi-grid,
        .ui-kpi-grid + .ui-kpi-grid {
            margin-top: var(--sp-lg); /* 24px between major sections */
        }
        .ui-alert + .ui-section,
        .ui-alert + .ui-kpi-grid {
            margin-top: var(--sp-lg); /* standardize to 24px */
        }

        .ui-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .ui-section-title {
            font-size: 1.12rem;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .ui-section-subtitle {
            margin-top: var(--sp-sm); /* 8px under titles */
            font-size: 13px;
            color: var(--hr-text-muted);
        }

        .ui-input,
        .ui-select,
        .ui-textarea {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--hr-line);
            background: transparent;
            color: var(--hr-text-main);
            padding: 10px 12px;
            font-size: 14px;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .ui-input:focus,
        .ui-select:focus,
        .ui-textarea:focus {
            outline: none;
            border-color: var(--hr-accent-border);
            box-shadow: 0 0 0 3px var(--hr-accent-soft);
        }

        .ui-select option {
            background: var(--hr-surface-strong);
            color: var(--hr-text-main);
        }

        /* Standard buttons */
        .ui-btn {
            height: 40px; /* standardized height */
            padding: 0 14px; /* vertical height handled by fixed height */
            border-radius: 8px; /* standardized radius */
            border: 1px solid var(--hr-line);
            font-size: 14px;
            line-height: 1;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }
        /* spacing between adjacent buttons */
        .ui-btn + .ui-btn { margin-left: 8px; }

        .ui-btn:hover { transform: translateY(-1px); }

        /* Primary uses selected brand accent */
        .ui-btn-primary {
            color: #fff;
            border-color: var(--hr-accent-border);
            background: var(--hr-accent);
            box-shadow: 0 18px 28px -22px color-mix(in oklab, var(--hr-accent), #000 24%);
        }
        .ui-btn-primary:hover {
            filter: brightness(0.96);
            box-shadow: 0 20px 30px -20px color-mix(in oklab, var(--hr-accent), #000 28%);
        }

        /* Secondary: outline style */
        .ui-btn-ghost,
        .ui-btn-secondary {
            color: var(--hr-text-main);
            background: transparent;
            border-color: var(--hr-line);
        }
        .ui-btn-ghost:hover,
        .ui-btn-secondary:hover {
            border-color: var(--hr-accent-border);
            background: var(--hr-accent-soft);
        }

        /* Danger: consistent red */
        .ui-btn-danger {
            color: #fff;
            background: #ef4444;
            border-color: #ef4444;
            box-shadow: 0 18px 28px -22px rgba(239, 68, 68, 0.8);
        }
        .ui-btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
            box-shadow: 0 20px 30px -20px rgba(239, 68, 68, 0.85);
        }

        .ui-tile-link {
            border-radius: 14px;
            border: 1px solid var(--hr-line);
            background: var(--hr-surface-strong);
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 160ms ease;
        }

        .ui-tile-link:hover {
            border-color: var(--hr-accent-border);
            transform: translateY(-1px);
        }

        .ui-table-wrap {
            margin-top: var(--sp-lg); /* 24px above tables */
            overflow-x: auto;
            border: 1px solid var(--hr-line);
            border-radius: 14px;
            background: var(--hr-surface-strong);
        }

        .ui-table {
            width: 100%;
            min-width: 840px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .ui-table th,
        .ui-table td {
            padding: var(--sp-md) var(--sp-lg); /* 16px vertical, 24px horizontal */
            border-bottom: 1px solid var(--hr-line);
            text-align: left;
            vertical-align: top;
        }

        .ui-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--hr-surface-strong);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--hr-text-muted);
            font-weight: 700;
        }

        .ui-table tbody tr:last-child td {
            border-bottom: none;
        }

        .ui-table tbody tr:hover {
            background: var(--hr-accent-soft);
        }

        .ui-empty {
            padding: 18px 12px;
            text-align: center;
            font-size: 13px;
            color: var(--hr-text-muted);
        }

        .ui-status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .ui-status-green {
            color: #15803d;
            background: rgb(34 197 94 / 0.16);
        }

        .ui-status-amber {
            color: #b45309;
            background: rgb(245 158 11 / 0.18);
        }

        .ui-status-red {
            color: #b91c1c;
            background: rgb(239 68 68 / 0.18);
        }

        .ui-status-slate {
            color: #64748b;
            background: rgb(100 116 139 / 0.16);
        }

        @keyframes hrm-step-content-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hrm-step-content {
            animation: hrm-step-content-in 220ms ease;
        }

        @media (max-width: 1024px) {
            .hrm-modern-shell {
                grid-template-columns: minmax(0, 1fr);
            }

            .hrm-modern-sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                width: 232px;
                z-index: var(--z-sidebar, 900);
                transform: translateX(-108%);
                transition: transform 220ms ease;
            }

            .hrm-modern-sidebar.is-open {
                transform: translateX(0);
            }
        }
    </style>
    @stack('head')
</head>
<body class="h-full hrm-modern-body">
@php
    $user = auth()->user();
    $user?->loadMissing('profile', 'designation');
    $role = $user?->role;
    $roleLabel = $role instanceof \App\Enums\UserRole ? $role->label() : ucfirst((string) $role);
    $designationLabel = (string) ($user?->designation?->name ?? '—');
    $resolvedAvatar = $user?->profile?->avatar_url ?? null;
    if (blank($resolvedAvatar)) {
        $resolvedAvatar = asset('images/user-avatar.svg');
    } elseif (! str_starts_with((string) $resolvedAvatar, 'http')) {
        $resolvedAvatar = asset((string) $resolvedAvatar);
    }
    $canManageUsers = $user?->hasAnyRole([
        \App\Enums\UserRole::SUPER_ADMIN->value,
        \App\Enums\UserRole::ADMIN->value,
        \App\Enums\UserRole::HR->value,
    ]) ?? false;
    $isAdmin = $user?->hasRole(\App\Enums\UserRole::ADMIN->value) ?? false;
    $isSuperAdmin = $user?->hasRole(\App\Enums\UserRole::SUPER_ADMIN->value) ?? false;
    $isEmployee = $user?->hasRole(\App\Enums\UserRole::EMPLOYEE->value) ?? false;
    $dashboardRoute = $user?->dashboardRouteName() ?? 'dashboard';
    $brandCompanyName = (string) ($companyProfile['company_name'] ?? config('app.name'));
    $brandLogoUrl = null;
    $brandLogoPath = (string) ($companyProfile['company_logo_path'] ?? '');
    if (
        $brandLogoPath !== ''
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($brandLogoPath)
    ) {
        $brandLogoUrl = route('settings.company.logo');
    }
    $unreadNotificationsCount = $user ? (int) $user->unreadNotifications()->count() : 0;
    $headerNotifications = $user
        ? $user->notifications()->latest()->limit(6)->get()
        : collect();
    $unreadCommunicationCount = 0;
    $headerCommunicationMessages = collect();
    if ($user && \Illuminate\Support\Facades\Schema::hasTable('messages')) {
        $unreadCommunicationCount = (int) \App\Models\Message::query()
            ->where('receiver_id', $user->id)
            ->where('read_status', false)
            ->count();
        $headerCommunicationMessages = \App\Models\Message::query()
            ->with('sender:id,name')
            ->where('receiver_id', $user->id)
            ->where('read_status', false)
            ->latest()
            ->limit(6)
            ->get();
    }
@endphp
<div id="hrmModernShell" class="hrm-modern-shell">
    <aside id="hrmModernSidebar" class="hrm-modern-sidebar hrm-modern-surface px-4 py-5 flex flex-col gap-4">
        @php
            $canSeePayroll = $user?->hasAnyRole([\App\Enums\UserRole::SUPER_ADMIN->value, \App\Enums\UserRole::ADMIN->value, \App\Enums\UserRole::HR->value, \App\Enums\UserRole::FINANCE->value]) ?? false;
            $sidebarItems = [];

            // Always show Dashboard (Level 1)
            $sidebarItems[] = [ 'key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'url' => route($dashboardRoute), 'active' => request()->routeIs($dashboardRoute) ];

            // Workforce (Level 2)
            $workforceItems = [];
            $workforceItems[] = [
                'key' => 'employees',
                'label' => $isEmployee ? 'Profile' : 'Employees',
                'icon' => 'employees',
                'url' => route('modules.employees.index'),
                'active' => request()->routeIs('modules.employees.*'),
            ];
            if ($canManageUsers) {
                $workforceItems[] = [ 'key' => 'users', 'label' => 'Users', 'icon' => 'users', 'url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*') ];
                $workforceItems[] = [ 'key' => 'designations', 'label' => 'Designations', 'icon' => 'departments', 'url' => route('modules.designations.index'), 'active' => request()->routeIs('modules.designations.*') ];
            }
            foreach ($workforceItems as $it) { $it['section'] = 'Workforce'; $sidebarItems[] = $it; }

            // Organization (Level 2)
            $organizationItems = [];
            if ($isAdmin || $isSuperAdmin) {
                $organizationItems[] = [ 'key' => 'branches', 'label' => 'Branches', 'icon' => 'branches', 'url' => route('modules.branches.index'), 'active' => request()->routeIs('modules.branches.*') ];
            }
            if ($canManageUsers) {
                $organizationItems[] = [ 'key' => 'departments', 'label' => 'Departments', 'icon' => 'departments', 'url' => route('modules.departments.index'), 'active' => request()->routeIs('modules.departments.*') ];
            }
            if ($user?->can('holiday.view')) {
                $organizationItems[] = [ 'key' => 'holidays', 'label' => 'Holidays', 'icon' => 'calendar', 'url' => route('modules.holidays.index'), 'active' => request()->routeIs('modules.holidays.*') ];
            }
            foreach ($organizationItems as $it) { $it['section'] = 'Organization'; $sidebarItems[] = $it; }

            // Operations (Level 2 + Level 3 where needed)
            $operationsItems = [];
            $attendanceChildren = [
                [ 'key' => 'attendance-overview', 'label' => 'Overview', 'url' => route('modules.attendance.overview'), 'active' => request()->routeIs('modules.attendance.overview') && strtolower((string) request()->query('action', '')) === '' ],
            ];
            if ($isAdmin || $isSuperAdmin) {
                $attendanceChildren[] = [ 'key' => 'attendance-mark', 'label' => 'Mark Attendance', 'url' => route('modules.attendance.overview', ['action' => 'create']), 'active' => request()->routeIs('modules.attendance.overview') && strtolower((string) request()->query('action', '')) === 'create' ];
            }
            $operationsItems[] = [ 'key' => 'attendance', 'label' => 'Attendance', 'icon' => 'attendance', 'url' => route('modules.attendance.overview'), 'active' => request()->routeIs('modules.attendance.*'), 'children' => $attendanceChildren ];

            $operationsItems[] = [ 'key' => 'leave', 'label' => 'Leave', 'icon' => 'leave', 'url' => route('modules.leave.index'), 'active' => request()->routeIs('modules.leave.*') ];

            if ($canSeePayroll) {
                $payrollChildren = [
                    [ 'key' => 'salary-structures', 'label' => 'Salary Structures', 'url' => route('modules.payroll.salary-structures'), 'active' => request()->routeIs('modules.payroll.salary-structures') ],
                    [ 'key' => 'processing', 'label' => 'Payroll Processing', 'url' => route('modules.payroll.processing'), 'active' => request()->routeIs('modules.payroll.processing') ],
                    [ 'key' => 'history', 'label' => 'Payroll History', 'url' => route('modules.payroll.history'), 'active' => request()->routeIs('modules.payroll.history') ],
                    [ 'key' => 'payslips', 'label' => 'Payslips', 'url' => route('modules.payroll.payslips'), 'active' => request()->routeIs('modules.payroll.payslips') ],
                ];
                $operationsItems[] = [ 'key' => 'payroll', 'label' => 'Payroll', 'icon' => 'payroll', 'url' => route('modules.payroll.index'), 'active' => str_starts_with((string) request()->route()?->getName(), 'modules.payroll.'), 'children' => $payrollChildren ];
            }
            foreach ($operationsItems as $it) { $it['section'] = 'Operations'; $sidebarItems[] = $it; }

            // Communication (Level 2, no third level)
            $sidebarItems[] = [ 'key' => 'inbox', 'label' => 'Inbox', 'icon' => 'communication', 'url' => route('modules.communication.index', ['tab' => 'inbox']), 'active' => request()->routeIs('modules.communication.*'), 'badge' => $unreadCommunicationCount, 'section' => 'Communication' ];
            $sidebarItems[] = [ 'key' => 'notifications', 'label' => 'Notifications', 'icon' => 'notifications', 'url' => route('notifications.index'), 'active' => request()->routeIs('notifications.*'), 'badge' => $unreadNotificationsCount, 'section' => 'Communication' ];

            // Reports (Level 2 + Level 3)
            $reportsChildren = [
                [ 'key' => 'reports-overview', 'label' => 'Overview', 'url' => route('modules.reports.index'), 'active' => request()->routeIs('modules.reports.index') ],
                [ 'key' => 'reports-attendance', 'label' => 'Attendance Reports', 'url' => route('modules.reports.index', ['section' => 'attendance']), 'active' => request()->routeIs('modules.reports.index') && strtolower((string) request()->query('section', '')) === 'attendance' ],
                [ 'key' => 'reports-leave', 'label' => 'Leave Reports', 'url' => route('modules.reports.index', ['section' => 'leave']), 'active' => request()->routeIs('modules.reports.index') && strtolower((string) request()->query('section', '')) === 'leave' ],
                [ 'key' => 'reports-payroll', 'label' => 'Payroll Reports', 'url' => route('modules.reports.index', ['section' => 'payroll']), 'active' => request()->routeIs('modules.reports.index') && strtolower((string) request()->query('section', '')) === 'payroll' ],
                [ 'key' => 'reports-activity', 'label' => 'Activity Logs', 'url' => route('modules.reports.activity'), 'active' => request()->routeIs('modules.reports.activity') ],
            ];
            $sidebarItems[] = [ 'key' => 'reports', 'label' => 'Reports', 'icon' => 'reports', 'url' => route('modules.reports.index'), 'active' => request()->routeIs('modules.reports.*'), 'children' => $reportsChildren, 'section' => 'Reports' ];

            // Settings (Section with direct children; max 2 levels)
            if ($canManageUsers) {
                // Direct children under SETTINGS
                $settingsItems = [];

                $settingsItems[] = [
                    'key' => 'settings-overview',
                    'label' => 'Overview',
                    'icon' => 'settings',
                    'url' => route('settings.index'),
                    'active' => request()->routeIs('settings.index') && strtolower((string) request()->query('section', '')) === '',
                ];

                $settingsItems[] = [
                    'key' => 'settings-organization',
                    'label' => 'Organization',
                    'icon' => 'settings',
                    'url' => route('settings.index', ['section' => 'company']),
                    'active' => request()->routeIs('settings.index') && strtolower((string) request()->query('section', '')) === 'company',
                ];

                // Appearance group with Themes (and Typography if available)
                $appearanceChildren = [
                    [
                        'key' => 'appearance-themes',
                        'label' => 'Themes',
                        'url' => route('settings.themes'),
                        'active' => request()->routeIs('settings.themes'),
                    ],
                ];
                // Typography page is optional; include only if route exists
                if (\Illuminate\Support\Facades\Route::has('settings.typography')) {
                    $appearanceChildren[] = [
                        'key' => 'appearance-typography',
                        'label' => 'Typography',
                        'url' => route('settings.typography'),
                        'active' => request()->routeIs('settings.typography'),
                    ];
                }
                $settingsItems[] = [
                    'key' => 'settings-appearance',
                    'label' => 'Appearance',
                    'icon' => 'settings',
                    'url' => route('settings.themes'),
                    'active' => request()->routeIs('settings.themes') || collect($appearanceChildren)->contains(fn ($c) => (bool) ($c['active'] ?? false)),
                    'children' => $appearanceChildren,
                ];

                $settingsItems[] = [
                    'key' => 'settings-access',
                    'label' => 'Access & Security',
                    'icon' => 'settings',
                    'url' => route('settings.index', ['section' => 'system']) . '#security',
                    'active' => request()->routeIs('settings.index') && strtolower((string) request()->query('section', '')) === 'system',
                ];

                $settingsItems[] = [
                    'key' => 'settings-system',
                    'label' => 'System Configuration',
                    'icon' => 'settings',
                    'url' => route('settings.index', ['section' => 'system']),
                    'active' => request()->routeIs('settings.index') && strtolower((string) request()->query('section', '')) === 'system',
                ];

                $settingsItems[] = [
                    'key' => 'settings-roles',
                    'label' => 'Roles & Permissions',
                    'icon' => 'settings',
                    'url' => route('admin.users.index'),
                    'active' => request()->routeIs('admin.users.*'),
                ];

                foreach ($settingsItems as $it) { $it['section'] = 'Settings'; $sidebarItems[] = $it; }
            }

            $sidebarPayload = [
                'brand' => [ 'name' => $brandCompanyName, 'logo' => $brandLogoUrl, 'tagline' => (string) ($companyProfile['brand_tagline'] ?? '') ],
                'items' => array_map(function ($item) { $item['badge'] = (int) ($item['badge'] ?? 0); return $item; }, $sidebarItems),
            ];
        @endphp
        <div id="sidebar-root" data-payload='@json($sidebarPayload)'></div>
        <div class="w-full">
            <div class="hrm-brand-logo-wrap">
                @if ($brandLogoUrl)
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandCompanyName }} logo" class="hrm-brand-logo-img">
                @else
                    <span class="hrm-brand-logo-fallback">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                        </svg>
                    </span>
                @endif
            </div>

            <div class="hrm-brand-copy mt-3 text-center">
                <p class="text-[11px] uppercase tracking-[0.16em] font-bold" style="color: var(--hr-text-muted);">HR Suite</p>
                <h1 class="text-lg font-extrabold tracking-tight">{{ $brandCompanyName }}</h1>
                @if (! empty($companyProfile['brand_tagline']))
                    <p class="text-xs mt-1" style="color: var(--hr-text-muted);">{{ $companyProfile['brand_tagline'] }}</p>
                @endif
            </div>

            <div class="hrm-brand-copy mt-3 border-t" style="border-color: var(--hr-line);"></div>
        </div>

        <div class="hrm-sidebar-scroll flex flex-col">
            <div class="hrm-scroll-fade-top" aria-hidden="true"></div>
            <!-- New Enterprise Sidebar Navigation -->
            <nav class="hrm-modern-nav flex flex-col gap-4 text-sm font-semibold">
                <!-- Primary -->
                <div class="hrm-nav-group">
                    <a href="{{ route($dashboardRoute) }}" class="hrm-modern-nav-link hrm-nav-l1 {{ request()->routeIs($dashboardRoute) ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5"></path><path d="M5 9.9V21h14V9.9"></path></svg>
                        <span class="hrm-nav-label">Dashboard</span>
                    </a>
                </div>

                <!-- Workforce -->
                <div class="hrm-nav-group">
                    <p class="hrm-nav-section-label">Workforce</p>
                    <a href="{{ route('modules.employees.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('modules.employees.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                        <span class="hrm-nav-label">{{ $isEmployee ? 'Profile' : 'Employees' }}</span>
                    </a>
                    @if ($canManageUsers)
                    <a href="{{ route('admin.users.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path></svg>
                        <span class="hrm-nav-label">Users</span>
                    </a>
                    <a href="{{ route('modules.designations.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('modules.designations.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"></path><path d="M6 3h12v18H6z"/></svg>
                        <span class="hrm-nav-label">Designations</span>
                    </a>
                    @endif
                </div>

                <!-- Organization -->
                <div class="hrm-nav-group">
                    <p class="hrm-nav-section-label">Organization</p>
                    @if ($isAdmin || $isSuperAdmin)
                    <a href="{{ route('modules.branches.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('modules.branches.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16"></path><path d="M6 20V8l6-4 6 4v12"></path><path d="M10 12h4"></path><path d="M10 16h4"></path></svg>
                        <span class="hrm-nav-label">Branches</span>
                    </a>
                    @endif
                    @if ($canManageUsers)
                    <a href="{{ route('modules.departments.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('modules.departments.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path><path d="M9 10h6"></path><path d="M9 14h6"></path></svg>
                        <span class="hrm-nav-label">Departments</span>
                    </a>
                    @endif
                    @if ($user?->can('holiday.view'))
                    <a href="{{ route('modules.holidays.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('modules.holidays.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                        <span class="hrm-nav-label">Holidays</span>
                    </a>
                    @endif
                </div>

                <!-- Operations -->
                <div class="hrm-nav-group">
                    <p class="hrm-nav-section-label">Operations</p>
                    @php
                        $attendanceOpen = request()->routeIs('modules.attendance.*');
                        $attendanceAction = strtolower((string) request()->query('action', ''));
                    @endphp
                    <div class="hrm-has-children">
                        <button type="button" class="hrm-modern-nav-link hrm-nav-l2 rounded-xl flex items-center gap-3 hrm-submenu-toggle {{ $attendanceOpen ? 'is-active' : '' }}" aria-expanded="{{ $attendanceOpen ? 'true' : 'false' }}">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                            <span class="hrm-nav-label">Attendance</span>
                            <svg class="h-4 w-4 shrink-0 ml-auto hrm-nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="hrm-submenu-links {{ $attendanceOpen ? '' : 'hidden' }}">
                            <a href="{{ route('modules.attendance.overview') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.attendance.overview') && $attendanceAction === '' ? 'is-active' : '' }} rounded-lg flex items-center gap-2">
                                <span class="hrm-nav-bullet" aria-hidden="true"></span>
                                <span class="hrm-nav-label">Overview</span>
                            </a>
                            @if ($isAdmin || $isSuperAdmin)
                            <a href="{{ route('modules.attendance.overview', ['action' => 'create']) }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.attendance.overview') && $attendanceAction === 'create' ? 'is-active' : '' }} rounded-lg flex items-center gap-2">
                                <span class="hrm-nav-bullet" aria-hidden="true"></span>
                                <span class="hrm-nav-label">Mark Attendance</span>
                            </a>
                            @endif
                        </div>
                        <div class="hrm-flyout">
                            <a href="{{ route('modules.attendance.overview') }}">Overview</a>
                            @if ($isAdmin || $isSuperAdmin)
                            <a href="{{ route('modules.attendance.overview', ['action' => 'create']) }}">Mark Attendance</a>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('modules.leave.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('modules.leave.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path></svg>
                        <span class="hrm-nav-label">Leave</span>
                    </a>

                    @if ($canSeePayroll)
                    @php
                        $payrollOpen = request()->routeIs('modules.payroll.salary-structures') || request()->routeIs('modules.payroll.processing') || request()->routeIs('modules.payroll.history') || request()->routeIs('modules.payroll.payslips');
                    @endphp
                    <div class="hrm-has-children">
                        <button type="button" class="hrm-modern-nav-link hrm-nav-l2 rounded-xl flex items-center gap-3 hrm-submenu-toggle {{ $payrollOpen ? 'is-active' : '' }}" aria-expanded="{{ $payrollOpen ? 'true' : 'false' }}">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                            <span class="hrm-nav-label">Payroll</span>
                            <svg class="h-4 w-4 shrink-0 ml-auto hrm-nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="hrm-submenu-links {{ $payrollOpen ? '' : 'hidden' }}">
                            <a href="{{ route('modules.payroll.salary-structures') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.payroll.salary-structures') ? 'is-active' : '' }} rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Salary Structures</span></a>
                            <a href="{{ route('modules.payroll.processing') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.payroll.processing') ? 'is-active' : '' }} rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Payroll Processing</span></a>
                            <a href="{{ route('modules.payroll.history') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.payroll.history') ? 'is-active' : '' }} rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Payroll History</span></a>
                            <a href="{{ route('modules.payroll.payslips') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.payroll.payslips') ? 'is-active' : '' }} rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Payslips</span></a>
                        </div>
                        <div class="hrm-flyout">
                            <a href="{{ route('modules.payroll.salary-structures') }}">Salary Structures</a>
                            <a href="{{ route('modules.payroll.processing') }}">Payroll Processing</a>
                            <a href="{{ route('modules.payroll.history') }}">Payroll History</a>
                            <a href="{{ route('modules.payroll.payslips') }}">Payslips</a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Communication -->
                <div class="hrm-nav-group">
                    <p class="hrm-nav-section-label">Communication</p>
                    <a href="{{ route('modules.communication.index', ['tab' => 'inbox']) }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('modules.communication.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span class="hrm-nav-label">Inbox</span>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('notifications.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <span class="hrm-nav-label">Notifications</span>
                    </a>
                </div>

                <!-- Reports -->
                @php $reportsOpen = request()->routeIs('modules.reports.*'); @endphp
                <div class="hrm-nav-group">
                    <p class="hrm-nav-section-label">Reports</p>
                    <div class="hrm-has-children">
                        <button type="button" class="hrm-modern-nav-link hrm-nav-l2 rounded-xl flex items-center gap-3 hrm-submenu-toggle {{ $reportsOpen ? 'is-active' : '' }}" aria-expanded="{{ $reportsOpen ? 'true' : 'false' }}">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-6"></path></svg>
                            <span class="hrm-nav-label">Reports</span>
                            <svg class="h-4 w-4 shrink-0 ml-auto hrm-nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="hrm-submenu-links {{ $reportsOpen ? '' : 'hidden' }}">
                            <a href="{{ route('modules.reports.index') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.reports.index') ? 'is-active' : '' }} rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Overview</span></a>
                            <a href="{{ route('modules.reports.index', ['section' => 'attendance']) }}" class="hrm-modern-nav-link hrm-nav-l3 rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Attendance Reports</span></a>
                            <a href="{{ route('modules.reports.index', ['section' => 'leave']) }}" class="hrm-modern-nav-link hrm-nav-l3 rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Leave Reports</span></a>
                            <a href="{{ route('modules.reports.index', ['section' => 'payroll']) }}" class="hrm-modern-nav-link hrm-nav-l3 rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Payroll Reports</span></a>
                            <a href="{{ route('modules.reports.activity') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('modules.reports.activity') ? 'is-active' : '' }} rounded-lg flex items-center gap-2"><span class="hrm-nav-bullet"></span><span class="hrm-nav-label">Activity Logs</span></a>
                        </div>
                        <div class="hrm-flyout">
                            <a href="{{ route('modules.reports.index') }}">Overview</a>
                            <a href="{{ route('modules.reports.index', ['section' => 'attendance']) }}">Attendance Reports</a>
                            <a href="{{ route('modules.reports.index', ['section' => 'leave']) }}">Leave Reports</a>
                            <a href="{{ route('modules.reports.index', ['section' => 'payroll']) }}">Payroll Reports</a>
                            <a href="{{ route('modules.reports.activity') }}">Activity Logs</a>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                @if ($isAdmin || $isSuperAdmin)
                @php $settingsSection = (string) request()->query('section', ''); @endphp
                <div class="hrm-nav-group">
                    <p class="hrm-nav-section-label">Settings</p>

                    <!-- Direct children under Settings -->
                    <a href="{{ route('settings.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('settings.index') && $settingsSection === '' ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83"></path></svg>
                        <span class="hrm-nav-label">Overview</span>
                    </a>
                    <a href="{{ route('settings.index', ['section' => 'company']) }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('settings.index') && $settingsSection === 'company' ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83"></path></svg>
                        <span class="hrm-nav-label">Organization</span>
                    </a>

                    @php $appearanceOpen = request()->routeIs('settings.themes') || request()->routeIs('settings.typography'); @endphp
                    <div class="hrm-has-children">
                        <button type="button" class="hrm-modern-nav-link hrm-nav-l2 rounded-xl flex items-center gap-3 hrm-submenu-toggle {{ $appearanceOpen ? 'is-active' : '' }}" aria-expanded="{{ $appearanceOpen ? 'true' : 'false' }}">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83"></path></svg>
                            <span class="hrm-nav-label">Appearance</span>
                            <svg class="h-4 w-4 shrink-0 ml-auto hrm-nav-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="hrm-submenu-links {{ $appearanceOpen ? '' : 'hidden' }}">
                            <a href="{{ route('settings.themes') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('settings.themes') ? 'is-active' : '' }} rounded-lg flex items-center gap-2">
                                <span class="hrm-nav-bullet" aria-hidden="true"></span>
                                <span class="hrm-nav-label">Themes</span>
                            </a>
                            @if (\Illuminate\Support\Facades\Route::has('settings.typography'))
                            <a href="{{ route('settings.typography') }}" class="hrm-modern-nav-link hrm-nav-l3 {{ request()->routeIs('settings.typography') ? 'is-active' : '' }} rounded-lg flex items-center gap-2">
                                <span class="hrm-nav-bullet" aria-hidden="true"></span>
                                <span class="hrm-nav-label">Typography</span>
                            </a>
                            @endif
                        </div>
                        <div class="hrm-flyout">
                            <a href="{{ route('settings.themes') }}">Themes</a>
                            @if (\Illuminate\Support\Facades\Route::has('settings.typography'))
                                <a href="{{ route('settings.typography') }}">Typography</a>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('settings.index', ['section' => 'system']) }}#security" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('settings.index') && $settingsSection === 'system' ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83"></path></svg>
                        <span class="hrm-nav-label">Access & Security</span>
                    </a>
                    <a href="{{ route('settings.index', ['section' => 'system']) }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('settings.index') && $settingsSection === 'system' ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83"></path></svg>
                        <span class="hrm-nav-label">System Configuration</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="hrm-modern-nav-link hrm-nav-l2 {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }} rounded-xl flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83"></path></svg>
                        <span class="hrm-nav-label">Roles & Permissions</span>
                    </a>
                </div>
                @endif
            </nav>

            <!-- Legacy navigation (hidden) -->
            <nav class="hrm-modern-nav flex flex-col gap-1.5 text-sm font-semibold hidden">
            <a href="{{ route($dashboardRoute) }}" class="hrm-modern-nav-link {{ request()->routeIs($dashboardRoute) ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 10.5L12 3l9 7.5"></path><path d="M5 9.9V21h14V9.9"></path>
                </svg>
                <span class="hrm-nav-label">Dashboard</span>
            </a>
            @if ($canManageUsers)
                <a href="{{ route('admin.users.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M7 21v-2a4 4 0 0 1 3-3.87"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M2 21v-2a4 4 0 0 1 3-3.87"></path>
                    </svg>
                    <span class="hrm-nav-label">Users</span>
                </a>
                <a href="{{ route('modules.departments.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.departments.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 21h18"></path>
                        <path d="M5 21V8l7-5 7 5v13"></path>
                        <path d="M9 10h6"></path>
                        <path d="M9 14h6"></path>
                    </svg>
                    <span class="hrm-nav-label">Departments</span>
                </a>
                @if ($isAdmin)
                    <a href="{{ route('modules.branches.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.branches.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 20h16"></path>
                            <path d="M6 20V8l6-4 6 4v12"></path>
                            <path d="M10 12h4"></path>
                            <path d="M10 16h4"></path>
                        </svg>
                        <span class="hrm-nav-label">Branches</span>
                    </a>
                @endif
            @endif
            <a href="{{ route('modules.holidays.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.holidays.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                    <path d="M3 10h18"></path>
                    <path d="m9.5 14 1.8 1.8 3.2-3.2"></path>
                </svg>
                <span class="hrm-nav-label">Holidays</span>
            </a>
            <a href="{{ route('modules.employees.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.employees.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path>
                </svg>
                <span class="hrm-nav-label">{{ $isEmployee ? 'Profile' : 'Employees' }}</span>
            </a>
            @php
                $attendanceMenuOpen = request()->routeIs('modules.attendance.*');
                $attendanceAction = strtolower((string) request()->query('action', ''));
            @endphp
            <div id="hrmAttendanceSubmenu" class="hrm-submenu flex flex-col gap-1 {{ $attendanceMenuOpen ? 'is-open' : '' }}">
                <button
                    id="hrmAttendanceToggle"
                    type="button"
                    class="hrm-modern-nav-link hrm-submenu-toggle {{ $attendanceMenuOpen ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3"
                    aria-controls="hrmAttendanceLinks"
                    aria-expanded="{{ $attendanceMenuOpen ? 'true' : 'false' }}"
                >
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3.2 2"></path>
                    </svg>
                    <span class="hrm-nav-label">Attendance</span>
                    <svg class="h-4 w-4 shrink-0 hrm-submenu-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </button>
                <div id="hrmAttendanceLinks" class="hrm-submenu-links flex flex-col gap-1 {{ $attendanceMenuOpen ? '' : 'hidden' }}">
                    <a href="{{ route('modules.attendance.overview') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.attendance.overview') && $attendanceAction === '' ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                        <span class="hrm-nav-label">Overview</span>
                    </a>
                    @if ($user?->can('attendance.create') && ! ($isAdmin || $isSuperAdmin))
                        <a href="{{ route('modules.attendance.punch') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.attendance.punch') || request()->routeIs('modules.attendance.punch-in') || request()->routeIs('modules.attendance.punch-out') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Punch In/Out</span>
                        </a>
                    @endif
                    @if ($isAdmin || $isSuperAdmin)
                        <a href="{{ route('modules.attendance.overview', ['action' => 'create']) }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.attendance.overview') && $attendanceAction === 'create' ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Mark Attendance</span>
                        </a>
                    @endif
                </div>
            </div>
            <a href="{{ route('modules.leave.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.leave.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect>
                    <path d="M3 10h18"></path>
                </svg>
                <span class="hrm-nav-label">Leave</span>
            </a>
            @if ($isEmployee)
                <a href="{{ route('modules.payroll.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.payroll.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path>
                    </svg>
                    <span class="hrm-nav-label">Payroll</span>
                </a>
            @else
                @php
                    $payrollMenuOpen = request()->routeIs('modules.payroll.dashboard')
                        || request()->routeIs('modules.payroll.salary-structures')
                        || request()->routeIs('modules.payroll.processing')
                        || request()->routeIs('modules.payroll.history')
                        || request()->routeIs('modules.payroll.payslips');
                @endphp
                <div id="hrmPayrollSubmenu" class="hrm-submenu flex flex-col gap-1 {{ $payrollMenuOpen ? 'is-open' : '' }}">
                    <button
                        id="hrmPayrollToggle"
                        type="button"
                        class="hrm-modern-nav-link hrm-submenu-toggle {{ $payrollMenuOpen ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3"
                        aria-controls="hrmPayrollLinks"
                        aria-expanded="{{ $payrollMenuOpen ? 'true' : 'false' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path>
                        </svg>
                        <span class="hrm-nav-label">Payroll</span>
                        <svg class="h-4 w-4 shrink-0 hrm-submenu-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>
                    <div id="hrmPayrollLinks" class="hrm-submenu-links flex flex-col gap-1 {{ $payrollMenuOpen ? '' : 'hidden' }}">
                        <a href="{{ route('modules.payroll.dashboard') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.payroll.dashboard') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Dashboard</span>
                        </a>
                        <a href="{{ route('modules.payroll.salary-structures') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.payroll.salary-structures') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Salary Structures</span>
                        </a>
                        <a href="{{ route('modules.payroll.processing') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.payroll.processing') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Payroll Processing</span>
                        </a>
                        <a href="{{ route('modules.payroll.history') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.payroll.history') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Payroll History</span>
                        </a>
                        <a href="{{ route('modules.payroll.payslips') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.payroll.payslips') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Payslips</span>
                        </a>
                        
                    </div>
                </div>
            @endif
            <a href="{{ route('modules.communication.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.communication.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span class="hrm-nav-label">Communication</span>
                @if ($unreadCommunicationCount > 0)
                    <span class="ml-auto text-[10px] font-bold leading-none rounded-full px-1.5 py-1 text-white" style="background: #ef4444;">
                        {{ min($unreadCommunicationCount, 99) }}
                    </span>
                @endif
            </a>
            <a href="{{ route('notifications.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="hrm-nav-label">Notifications</span>
            </a>
            @php
                $reportsMenuOpen = request()->routeIs('modules.reports.*');
            @endphp
            <div id="hrmReportsSubmenu" class="hrm-submenu flex flex-col gap-1 {{ $reportsMenuOpen ? 'is-open' : '' }}">
                <button
                    id="hrmReportsToggle"
                    type="button"
                    class="hrm-modern-nav-link hrm-submenu-toggle {{ $reportsMenuOpen ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3"
                    aria-controls="hrmReportsLinks"
                    aria-expanded="{{ $reportsMenuOpen ? 'true' : 'false' }}"
                >
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-6"></path>
                    </svg>
                    <span class="hrm-nav-label">Reports</span>
                    <svg class="h-4 w-4 shrink-0 hrm-submenu-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </button>
                <div id="hrmReportsLinks" class="hrm-submenu-links flex flex-col gap-1 {{ $reportsMenuOpen ? '' : 'hidden' }}">
                    <a href="{{ route('modules.reports.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.reports.index') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                        <span class="hrm-nav-label">Overview</span>
                    </a>
                    <a href="{{ route('modules.reports.activity') }}" class="hrm-modern-nav-link {{ request()->routeIs('modules.reports.activity') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                        <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                        <span class="hrm-nav-label">Activity</span>
                    </a>
                </div>
            </div>
            @if ($canManageUsers)
                @php
                    $settingsMenuOpen = request()->routeIs('settings.*');
                    $settingsSection = strtolower((string) request()->query('section', ''));
                @endphp
                <div id="hrmSettingsSubmenu" class="hrm-submenu flex flex-col gap-1 {{ $settingsMenuOpen ? 'is-open' : '' }}">
                    <button
                        id="hrmSettingsToggle"
                        type="button"
                        class="hrm-modern-nav-link hrm-submenu-toggle {{ $settingsMenuOpen ? 'is-active' : '' }} rounded-xl px-3 py-2.5 flex items-center gap-3"
                        aria-controls="hrmSettingsLinks"
                        aria-expanded="{{ $settingsMenuOpen ? 'true' : 'false' }}"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 8.92 4.6H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09A1.65 1.65 0 0 0 15.08 4.6h.08a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.22.48.34 1.01.34 1.55s-.12 1.07-.34 1.55z"></path>
                        </svg>
                        <span class="hrm-nav-label">Settings</span>
                        <svg class="h-4 w-4 shrink-0 hrm-submenu-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>
                    <div id="hrmSettingsLinks" class="hrm-submenu-links flex flex-col gap-1 {{ $settingsMenuOpen ? '' : 'hidden' }}">
                        <a href="{{ route('settings.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('settings.index') && $settingsSection === '' ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Overview</span>
                        </a>
                        <a href="{{ route('settings.index', ['section' => 'company']) }}" class="hrm-modern-nav-link {{ request()->routeIs('settings.index') && $settingsSection === 'company' ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Organization</span>
                        </a>
                        <a href="{{ route('settings.index', ['section' => 'system']) }}#security" class="hrm-modern-nav-link {{ request()->routeIs('settings.index') && $settingsSection === 'system' ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Access & Security</span>
                        </a>
                        <a href="{{ route('settings.index', ['section' => 'system']) }}" class="hrm-modern-nav-link {{ request()->routeIs('settings.index') && $settingsSection === 'system' ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">System Configuration</span>
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="hrm-modern-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }} rounded-lg pl-10 pr-3 py-1.5 flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                            <span class="hrm-nav-label">Roles & Permissions</span>
                        </a>
                    </div>
                </div>
            @endif
        </nav>

        <div class="hrm-sidebar-foot mt-auto rounded-2xl p-3 text-xs" style="background: var(--hr-accent-soft); border: 1px dashed var(--hr-line); color: var(--hr-text-muted);">
            <p class="font-bold text-[11px] uppercase tracking-[0.14em]">HR Pulse</p>
            <p class="mt-1 leading-relaxed">Live records are synced across users, attendance, leave, and payroll modules.</p>
        </div>
        <div class="hrm-scroll-fade-bottom" aria-hidden="true"></div>
        </div>
    </aside>

    <div class="hrm-main-pane min-w-0 flex flex-col">
        <header class="sticky top-0 z-40 border-b backdrop-blur px-6 py-1.5" style="background: var(--hr-header-bg); border-color: var(--hr-line); z-index: var(--z-header, 1000); min-height: 64px; box-shadow: 0 6px 16px -14px rgb(2 8 23 / 0.28);">
            <div class="flex items-center gap-4">
                <button id="hrmSidebarMobileToggle" type="button" class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl border" style="border-color: var(--hr-line);">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path>
                    </svg>
                </button>
                <button id="hrmSidebarCollapse" type="button" class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-xl border" style="border-color: var(--hr-line);" aria-label="Toggle sidebar">
                    <svg id="hrmSidebarCollapseIcon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 6l-6 6 6 6"></path>
                    </svg>
                    <svg id="hrmSidebarExpandIcon" class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 6l6 6-6 6"></path>
                    </svg>
                </button>

                <div>
                    <p class="text-[11px] uppercase tracking-[0.12em] font-medium" style="color: var(--hr-text-muted);">{{ $roleLabel }} Dashboard</p>
                    <h2 class="text-lg md:text-xl font-semibold tracking-tight leading-tight mt-1.5">@yield('page_heading', 'Dashboard')</h2>
                </div>

                <div class="ml-auto flex items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div id="hrmCommunicationMenu" class="hrm-notification-menu">
                            <button id="hrmCommunicationButton" type="button" class="hrm-header-icon-btn" aria-label="Communication" aria-haspopup="true" aria-expanded="false">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                @if ($unreadCommunicationCount > 0)
                                    <span class="hrm-header-alert-badge">{{ min($unreadCommunicationCount, 99) }}</span>
                                @endif
                            </button>
                            <div id="hrmCommunicationDropdown" class="hrm-notification-dropdown hidden" role="menu">
                                <div class="hrm-notification-head">
                                    <p class="text-sm font-bold">New Messages</p>
                                    <a href="{{ route('modules.communication.index', ['tab' => 'inbox']) }}" class="text-xs font-semibold" style="color: var(--hr-accent);">View Inbox</a>
                                </div>
                                <div class="hrm-notification-list">
                                    @forelse($headerCommunicationMessages as $headerMessage)
                                        @php
                                            $senderName = (string) ($headerMessage->sender?->name ?? 'Unknown Sender');
                                            $previewText = str((string) $headerMessage->message)->squish()->limit(110);
                                        @endphp
                                        <article class="hrm-notification-item is-unread">
                                            <p class="text-sm font-semibold">{{ $senderName }}</p>
                                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $previewText }}</p>
                                            <p class="text-[11px]" style="color: var(--hr-text-muted);">
                                                {{ $headerMessage->created_at?->format('M d, h:i A') ?? 'N/A' }}
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('modules.communication.index', ['tab' => 'inbox']) }}" class="text-xs font-semibold" style="color: var(--hr-accent);">Open Inbox</a>
                                                <form method="POST" action="{{ route('modules.communication.messages.read', $headerMessage) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="text-xs font-semibold" style="color: var(--hr-accent);">Mark Read</button>
                                                </form>
                                            </div>
                                        </article>
                                    @empty
                                        <p class="text-xs px-1 py-2" style="color: var(--hr-text-muted);">No new messages.</p>
                                    @endforelse
                                </div>
                                <div class="hrm-notification-foot">
                                    <a href="{{ route('modules.communication.index', ['tab' => 'inbox']) }}" class="text-xs font-semibold" style="color: var(--hr-accent);">Go to Communication</a>
                                </div>
                            </div>
                        </div>
                        <div id="hrmNotificationMenu" class="hrm-notification-menu">
                            <button id="hrmNotificationButton" type="button" class="hrm-header-icon-btn" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                @if ($unreadNotificationsCount > 0)
                                    <span class="hrm-header-alert-badge">{{ min($unreadNotificationsCount, 99) }}</span>
                                @endif
                            </button>
                            <div id="hrmNotificationDropdown" class="hrm-notification-dropdown hidden" role="menu">
                                <div class="hrm-notification-head">
                                    <p class="text-sm font-bold">Notifications</p>
                                    <a href="{{ route('notifications.index') }}" class="text-xs font-semibold" style="color: var(--hr-accent);">View All</a>
                                </div>
                                <div class="hrm-notification-list">
                                    @forelse($headerNotifications as $headerNotification)
                                        @php
                                            $payload = (array) $headerNotification->data;
                                            $title = (string) ($payload['title'] ?? 'Notification');
                                            $message = (string) ($payload['message'] ?? '');
                                            $url = (string) ($payload['url'] ?? '');
                                            $isUnread = $headerNotification->read_at === null;
                                        @endphp
                                        <article class="hrm-notification-item {{ $isUnread ? 'is-unread' : '' }}">
                                            <p class="text-sm font-semibold">{{ $title }}</p>
                                            <p class="text-xs" style="color: var(--hr-text-muted);">{{ $message }}</p>
                                            <p class="text-[11px]" style="color: var(--hr-text-muted);">
                                                {{ $headerNotification->created_at?->format('M d, h:i A') ?? 'N/A' }}
                                            </p>
                                            <div class="flex items-center gap-2">
                                                @if ($url !== '')
                                                    <a href="{{ route('notifications.open', $headerNotification->id) }}" class="text-xs font-semibold" style="color: var(--hr-accent);">Open</a>
                                                @endif
                                                @if ($isUnread)
                                                    <form method="POST" action="{{ route('notifications.read', $headerNotification->id) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="text-xs font-semibold" style="color: var(--hr-accent);">Mark Read</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('notifications.unread', $headerNotification->id) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="text-xs font-semibold" style="color: var(--hr-text-muted);">Mark Unread</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </article>
                                    @empty
                                        <p class="text-xs px-1 py-2" style="color: var(--hr-text-muted);">No notifications yet.</p>
                                    @endforelse
                                </div>
                                <div class="hrm-notification-foot">
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-xs font-semibold" style="color: var(--hr-accent);">Mark All Read</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <button id="hrmThemeToggle" type="button" class="hrm-header-icon-btn" aria-label="Toggle theme">
                            <!-- Sun icon (shown in light mode) -->
                            <svg class="icon-sun h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="4"></circle>
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
                            </svg>
                            <!-- Moon icon (shown in dark mode) -->
                            <svg class="icon-moon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"></path>
                            </svg>
                            <span id="hrmThemeLabel" class="sr-only">Dark</span>
                        </button>
                    </div>

                    <div class="hrm-header-divider"></div>

                    <div id="hrmProfileMenu" class="hrm-profile-menu">
                        <button id="hrmProfileMenuButton" type="button" class="hrm-profile-chip hrm-profile-trigger" aria-haspopup="true" aria-expanded="false">
                            <div class="hidden sm:block text-right">
                                <p class="text-sm font-extrabold leading-tight">{{ $user?->full_name ?? 'User' }}</p>
                                <p class="text-xs inline-flex items-center gap-1.5 mt-0.5" style="color: var(--hr-text-muted);">
                                    <span>{{ $designationLabel }}</span>
                                    <svg class="h-3.5 w-3.5 hrm-profile-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </p>
                            </div>
                            <div class="relative">
                                <img src="{{ $resolvedAvatar }}" alt="User avatar" class="h-10 w-10 rounded-full object-cover border" style="border-color: var(--hr-line);">
                                <span class="hrm-avatar-online-dot"></span>
                            </div>
                        </button>

                        <div id="hrmProfileDropdown" class="hrm-profile-dropdown hidden" role="menu" aria-labelledby="hrmProfileMenuButton">
                            <a href="{{ route('profile.edit') }}" class="hrm-profile-dropdown-item" role="menuitem">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="7" r="4"></circle>
                                    <path d="M5.5 21a8.5 8.5 0 0 1 13 0"></path>
                                </svg>
                                Edit Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="hrm-profile-dropdown-item" role="menuitem">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16 17 21 12 16 7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="hrm-main-content p-6 space-y-6">
            @yield('content')
        </main>
    </div>
</div>

<script>
    (() => {
        const shell = document.getElementById("hrmModernShell");
        const sidebar = document.getElementById("hrmModernSidebar");
        const sidebarScroll = sidebar ? sidebar.querySelector('.hrm-sidebar-scroll') : null;
        const sidebarCollapse = document.getElementById("hrmSidebarCollapse");
        const mobileToggle = document.getElementById("hrmSidebarMobileToggle");
        const themeToggle = document.getElementById("hrmThemeToggle");
        const themeLabel = document.getElementById("hrmThemeLabel");
        const themeIconSun = themeToggle ? themeToggle.querySelector('.icon-sun') : null;
        const themeIconMoon = themeToggle ? themeToggle.querySelector('.icon-moon') : null;
        const profileMenu = document.getElementById("hrmProfileMenu");
        const profileMenuButton = document.getElementById("hrmProfileMenuButton");
        const profileDropdown = document.getElementById("hrmProfileDropdown");
        const communicationMenu = document.getElementById("hrmCommunicationMenu");
        const communicationButton = document.getElementById("hrmCommunicationButton");
        const communicationDropdown = document.getElementById("hrmCommunicationDropdown");
        const notificationMenu = document.getElementById("hrmNotificationMenu");
        const notificationButton = document.getElementById("hrmNotificationButton");
        const notificationDropdown = document.getElementById("hrmNotificationDropdown");
        const sidebarCollapseIcon = document.getElementById("hrmSidebarCollapseIcon");
        const sidebarExpandIcon = document.getElementById("hrmSidebarExpandIcon");
        const reportsSubmenu = document.getElementById("hrmReportsSubmenu");
        const reportsToggle = document.getElementById("hrmReportsToggle");
        const reportsLinks = document.getElementById("hrmReportsLinks");
        const attendanceSubmenu = document.getElementById("hrmAttendanceSubmenu");
        const attendanceToggle = document.getElementById("hrmAttendanceToggle");
        const attendanceLinks = document.getElementById("hrmAttendanceLinks");
        const payrollSubmenu = document.getElementById("hrmPayrollSubmenu");
        const payrollToggle = document.getElementById("hrmPayrollToggle");
        const payrollLinks = document.getElementById("hrmPayrollLinks");
        const settingsSubmenu = document.getElementById("hrmSettingsSubmenu");
        const settingsToggle = document.getElementById("hrmSettingsToggle");
        const settingsLinks = document.getElementById("hrmSettingsLinks");

        const updateSidebarFades = () => {
            if (!sidebarScroll) return;
            const atTop = sidebarScroll.scrollTop <= 1;
            const atBottom = (sidebarScroll.scrollHeight - (sidebarScroll.scrollTop + sidebarScroll.clientHeight)) <= 1;
            sidebarScroll.classList.toggle('is-at-top', atTop);
            sidebarScroll.classList.toggle('is-at-bottom', atBottom);
        };

        const getInitialTheme = () => {
            const storedTheme = localStorage.getItem("hrm-modern-theme");
            if (storedTheme === "light" || storedTheme === "dark") {
                return storedTheme;
            }

            return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        };

        const applyTheme = (theme) => {
            document.documentElement.classList.toggle("dark", theme === "dark");
            localStorage.setItem("hrm-modern-theme", theme);
            if (themeLabel) {
                themeLabel.textContent = theme === "dark" ? "Light" : "Dark";
            }
            if (themeIconSun && themeIconMoon) {
                // Show icon for the opposite (next) theme
                const isDark = theme === 'dark';
                // In dark mode, show sun (to switch to light). In light mode, show moon (to switch to dark).
                themeIconSun.classList.toggle('hidden', !isDark);
                themeIconMoon.classList.toggle('hidden', isDark);
            }
        };

        const setProfileMenuOpen = (open) => {
            if (!profileMenu || !profileMenuButton || !profileDropdown) {
                return;
            }

            profileMenu.classList.toggle("is-open", open);
            profileDropdown.classList.toggle("hidden", !open);
            profileMenuButton.setAttribute("aria-expanded", open ? "true" : "false");
        };

        const setNotificationMenuOpen = (open) => {
            if (!notificationMenu || !notificationButton || !notificationDropdown) {
                return;
            }

            notificationMenu.classList.toggle("is-open", open);
            notificationDropdown.classList.toggle("hidden", !open);
            notificationButton.setAttribute("aria-expanded", open ? "true" : "false");
        };

        const setCommunicationMenuOpen = (open) => {
            if (!communicationMenu || !communicationButton || !communicationDropdown) {
                return;
            }

            communicationMenu.classList.toggle("is-open", open);
            communicationDropdown.classList.toggle("hidden", !open);
            communicationButton.setAttribute("aria-expanded", open ? "true" : "false");
        };

        const setReportsSubmenuOpen = (open) => {
            if (!reportsSubmenu || !reportsToggle || !reportsLinks) {
                return;
            }

            reportsSubmenu.classList.toggle("is-open", open);
            reportsLinks.classList.toggle("hidden", !open);
            reportsToggle.setAttribute("aria-expanded", open ? "true" : "false");
        };

        const setAttendanceSubmenuOpen = (open) => {
            if (!attendanceSubmenu || !attendanceToggle || !attendanceLinks) {
                return;
            }

            attendanceSubmenu.classList.toggle("is-open", open);
            attendanceLinks.classList.toggle("hidden", !open);
            attendanceToggle.setAttribute("aria-expanded", open ? "true" : "false");
        };

        const setPayrollSubmenuOpen = (open) => {
            if (!payrollSubmenu || !payrollToggle || !payrollLinks) {
                return;
            }

            payrollSubmenu.classList.toggle("is-open", open);
            payrollLinks.classList.toggle("hidden", !open);
            payrollToggle.setAttribute("aria-expanded", open ? "true" : "false");
        };

        const setSettingsSubmenuOpen = (open) => {
            if (!settingsSubmenu || !settingsToggle || !settingsLinks) {
                return;
            }

            settingsSubmenu.classList.toggle("is-open", open);
            settingsLinks.classList.toggle("hidden", !open);
            settingsToggle.setAttribute("aria-expanded", open ? "true" : "false");
        };

        const initPasswordToggles = () => {
            document.querySelectorAll("[data-password-toggle]").forEach((toggleButton) => {
                if (!(toggleButton instanceof HTMLButtonElement) || toggleButton.dataset.bound === "true") {
                    return;
                }

                const targetId = toggleButton.getAttribute("data-target");
                const input = targetId ? document.getElementById(targetId) : null;
                if (!(input instanceof HTMLInputElement)) {
                    return;
                }

                const shownIcon = toggleButton.querySelector(".icon-shown");
                const hiddenIcon = toggleButton.querySelector(".icon-hidden");

                const applyVisibility = (visible) => {
                    toggleButton.setAttribute("data-visible", visible ? "true" : "false");
                    toggleButton.setAttribute("aria-label", visible ? "Hide password" : "Show password");
                    if (shownIcon) {
                        shownIcon.classList.toggle("hidden", visible);
                    }
                    if (hiddenIcon) {
                        hiddenIcon.classList.toggle("hidden", !visible);
                    }
                };

                applyVisibility(false);
                toggleButton.dataset.bound = "true";
                toggleButton.addEventListener("click", () => {
                    const isVisible = input.type === "text";
                    input.type = isVisible ? "password" : "text";
                    applyVisibility(!isVisible);
                });
            });
        };

        const initEnterpriseSidebarSubmenus = () => {
            const sidebar = document.getElementById('hrmModernSidebar');
            if (!sidebar) return;
            sidebar.querySelectorAll('.hrm-submenu-toggle').forEach((btn) => {
                if (btn.dataset.bound === 'true') return;
                btn.dataset.bound = 'true';
                btn.addEventListener('click', () => {
                    const container = btn.parentElement;
                    const links = container ? container.querySelector('.hrm-submenu-links') : null;
                    if (!links) return;
                    const hidden = links.classList.contains('hidden');
                    links.classList.toggle('hidden', !hidden);
                    btn.setAttribute('aria-expanded', hidden ? 'true' : 'false');
                });
            });
        };

        const syncSidebarToggleState = () => {
            if (!shell || !sidebarCollapse || !sidebarCollapseIcon || !sidebarExpandIcon) {
                return;
            }

            const collapsed = shell.classList.contains("is-collapsed");
            sidebarCollapseIcon.classList.toggle("hidden", collapsed);
            sidebarExpandIcon.classList.toggle("hidden", !collapsed);
            sidebarCollapse.setAttribute("aria-label", collapsed ? "Expand sidebar" : "Collapse sidebar");
        };

        // Apply persisted sidebar collapsed state on load
        const storedCollapsed = localStorage.getItem("hrm-modern-sidebar-collapsed");
        if (storedCollapsed === "1") {
            shell?.classList.add("is-collapsed");
        } else if (storedCollapsed === "0") {
            shell?.classList.remove("is-collapsed");
        }

        if (profileMenuButton) {
            profileMenuButton.addEventListener("click", (event) => {
                event.preventDefault();
                const isOpen = profileMenu?.classList.contains("is-open") ?? false;
                setNotificationMenuOpen(false);
                setCommunicationMenuOpen(false);
                setProfileMenuOpen(!isOpen);
            });
        }

        if (sidebarScroll) {
            sidebarScroll.addEventListener('scroll', updateSidebarFades);
            window.addEventListener('resize', updateSidebarFades);
        }

        if (communicationButton) {
            communicationButton.addEventListener("click", (event) => {
                event.preventDefault();
                const isOpen = communicationMenu?.classList.contains("is-open") ?? false;
                setProfileMenuOpen(false);
                setNotificationMenuOpen(false);
                setCommunicationMenuOpen(!isOpen);
            });
        }

        if (notificationButton) {
            notificationButton.addEventListener("click", (event) => {
                event.preventDefault();
                const isOpen = notificationMenu?.classList.contains("is-open") ?? false;
                setProfileMenuOpen(false);
                setCommunicationMenuOpen(false);
                setNotificationMenuOpen(!isOpen);
            });
        }

        if (reportsToggle) {
            reportsToggle.addEventListener("click", (event) => {
                event.preventDefault();
                if (shell?.classList.contains("is-collapsed")) {
                    shell.classList.remove("is-collapsed");
                    syncSidebarToggleState();
                    localStorage.setItem("hrm-modern-sidebar-collapsed", "0");
                }
                const isOpen = reportsSubmenu?.classList.contains("is-open") ?? false;
                setReportsSubmenuOpen(!isOpen);
            });
        }

        if (attendanceToggle) {
            attendanceToggle.addEventListener("click", (event) => {
                event.preventDefault();
                if (shell?.classList.contains("is-collapsed")) {
                    shell.classList.remove("is-collapsed");
                    syncSidebarToggleState();
                    localStorage.setItem("hrm-modern-sidebar-collapsed", "0");
                }
                const isOpen = attendanceSubmenu?.classList.contains("is-open") ?? false;
                setAttendanceSubmenuOpen(!isOpen);
            });
        }

        if (payrollToggle) {
            payrollToggle.addEventListener("click", (event) => {
                event.preventDefault();
                if (shell?.classList.contains("is-collapsed")) {
                    shell.classList.remove("is-collapsed");
                    syncSidebarToggleState();
                    localStorage.setItem("hrm-modern-sidebar-collapsed", "0");
                }
                const isOpen = payrollSubmenu?.classList.contains("is-open") ?? false;
                setPayrollSubmenuOpen(!isOpen);
            });
        }

        if (settingsToggle) {
            settingsToggle.addEventListener("click", (event) => {
                event.preventDefault();
                if (shell?.classList.contains("is-collapsed")) {
                    shell.classList.remove("is-collapsed");
                    syncSidebarToggleState();
                    localStorage.setItem("hrm-modern-sidebar-collapsed", "0");
                }
                const isOpen = settingsSubmenu?.classList.contains("is-open") ?? false;
                setSettingsSubmenuOpen(!isOpen);
            });
        }

        if (themeToggle) {
            themeToggle.addEventListener("click", () => {
                const nextTheme = document.documentElement.classList.contains("dark") ? "light" : "dark";
                applyTheme(nextTheme);
            });
        }

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener("click", () => {
                shell.classList.toggle("is-collapsed");
                syncSidebarToggleState();
                const collapsed = shell.classList.contains("is-collapsed");
                localStorage.setItem("hrm-modern-sidebar-collapsed", collapsed ? "1" : "0");
            });
        }

        if (mobileToggle) {
            mobileToggle.addEventListener("click", () => {
                sidebar.classList.toggle("is-open");
            });
        }

        document.addEventListener("click", (event) => {
            const target = event.target instanceof Element ? event.target : null;
            const clickedInsideSidebar = target ? target.closest("#hrmModernSidebar") : null;
            const clickedMobileToggle = target ? target.closest("#hrmSidebarMobileToggle") : null;
            const clickedSidebarLink = target ? target.closest("#hrmModernSidebar a[href]") : null;

            if (window.innerWidth <= 1024 && !clickedInsideSidebar && !clickedMobileToggle) {
                sidebar.classList.remove("is-open");
            }

            // Auto-hide sidebar on mobile when a nav link is clicked
            if (window.innerWidth <= 1024 && clickedSidebarLink) {
                sidebar.classList.remove("is-open");
            }

            if (profileMenu && target && !profileMenu.contains(target)) {
                setProfileMenuOpen(false);
            }

            if (notificationMenu && target && !notificationMenu.contains(target)) {
                setNotificationMenuOpen(false);
            }

            if (communicationMenu && target && !communicationMenu.contains(target)) {
                setCommunicationMenuOpen(false);
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                setProfileMenuOpen(false);
                setNotificationMenuOpen(false);
                setCommunicationMenuOpen(false);
            }
        });

        syncSidebarToggleState();
        applyTheme(getInitialTheme());
        initPasswordToggles();
        initEnterpriseSidebarSubmenus();
        updateSidebarFades();
    })();
</script>
@stack('scripts')
</body>
</html>
