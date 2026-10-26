<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="quinrel-base-path" content="{{ '/' . trim((string) config('quinrel.ui.path', 'quinrel'), '/') }}">
    <title>Quinrel — Search &amp; Generative Engine Intelligence</title>
    <style>
        :root {
            color-scheme: light dark;

            --q-bg-light: #fafafa;
            --q-bg-dark: #09090b;
            --q-surface-light: #ffffff;
            --q-surface-dark: #121215;
            --q-surface-subtle-light: #f4f4f5;
            --q-surface-subtle-dark: #18181b;
            --q-surface-elevated-light: rgba(255, 255, 255, 0.84);
            --q-surface-elevated-dark: rgba(18, 18, 21, 0.84);

            --q-border-light: #e4e4e7;
            --q-border-dark: #27272a;
            --q-border-strong-light: #d4d4d8;
            --q-border-strong-dark: #3f3f46;

            --q-text-light: #09090b;
            --q-text-dark: #fafafa;
            --q-text-secondary-light: #3f3f46;
            --q-text-secondary-dark: #d4d4d8;
            --q-text-muted-light: #71717a;
            --q-text-muted-dark: #a1a1aa;

            --q-primary-light: #18181b;
            --q-primary-dark: #fafafa;
            --q-primary-fg-light: #ffffff;
            --q-primary-fg-dark: #09090b;

            --q-accent-light: #2563eb;
            --q-accent-dark: #60a5fa;

            --q-success-light: #059669;
            --q-success-dark: #34d399;
            --q-success-bg-light: #ecfdf5;
            --q-success-bg-dark: rgba(16, 185, 129, 0.14);
            --q-success-border-light: #a7f3d0;
            --q-success-border-dark: rgba(52, 211, 153, 0.3);

            --q-warning-light: #d97706;
            --q-warning-dark: #fbbf24;
            --q-warning-bg-light: #fffbeb;
            --q-warning-bg-dark: rgba(245, 158, 11, 0.14);
            --q-warning-border-light: #fde68a;
            --q-warning-border-dark: rgba(251, 191, 36, 0.3);

            --q-danger-light: #dc2626;
            --q-danger-dark: #f87171;
            --q-danger-bg-light: #fef2f2;
            --q-danger-bg-dark: rgba(239, 68, 68, 0.14);
            --q-danger-border-light: #fecaca;
            --q-danger-border-dark: rgba(248, 113, 113, 0.3);

            --q-bg: var(--q-bg-light);
            --q-surface: var(--q-surface-light);
            --q-surface-subtle: var(--q-surface-subtle-light);
            --q-surface-elevated: var(--q-surface-elevated-light);
            --q-border: var(--q-border-light);
            --q-border-strong: var(--q-border-strong-light);
            --q-text: var(--q-text-light);
            --q-text-secondary: var(--q-text-secondary-light);
            --q-text-muted: var(--q-text-muted-light);
            --q-primary: var(--q-primary-light);
            --q-primary-fg: var(--q-primary-fg-light);
            --q-accent: var(--q-accent-light);
            --q-success: var(--q-success-light);
            --q-success-bg: var(--q-success-bg-light);
            --q-success-border: var(--q-success-border-light);
            --q-warning: var(--q-warning-light);
            --q-warning-bg: var(--q-warning-bg-light);
            --q-warning-border: var(--q-warning-border-light);
            --q-danger: var(--q-danger-light);
            --q-danger-bg: var(--q-danger-bg-light);
            --q-danger-border: var(--q-danger-border-light);
            --q-shadow-sm: 0 1px 2px 0 rgba(9, 9, 11, 0.04);
            --q-shadow-md: 0 4px 12px -2px rgba(9, 9, 11, 0.06), 0 2px 4px -2px rgba(9, 9, 11, 0.04);
            --q-radius-sm: 0.375rem;
            --q-radius-md: 0.5rem;
            --q-radius-lg: 0.75rem;
            --q-radius-full: 9999px;

            @media (prefers-color-scheme: dark) {
                --q-bg: var(--q-bg-dark);
                --q-surface: var(--q-surface-dark);
                --q-surface-subtle: var(--q-surface-subtle-dark);
                --q-surface-elevated: var(--q-surface-elevated-dark);
                --q-border: var(--q-border-dark);
                --q-border-strong: var(--q-border-strong-dark);
                --q-text: var(--q-text-dark);
                --q-text-secondary: var(--q-text-secondary-dark);
                --q-text-muted: var(--q-text-muted-dark);
                --q-primary: var(--q-primary-dark);
                --q-primary-fg: var(--q-primary-fg-dark);
                --q-accent: var(--q-accent-dark);
                --q-success: var(--q-success-dark);
                --q-success-bg: var(--q-success-bg-dark);
                --q-success-border: var(--q-success-border-dark);
                --q-warning: var(--q-warning-dark);
                --q-warning-bg: var(--q-warning-bg-dark);
                --q-warning-border: var(--q-warning-border-dark);
                --q-danger: var(--q-danger-dark);
                --q-danger-bg: var(--q-danger-bg-dark);
                --q-danger-border: var(--q-danger-border-dark);
                --q-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.35);
                --q-shadow-md: 0 8px 24px -4px rgba(0, 0, 0, 0.5);
            }

            @supports (color: light-dark(white, black)) {
                --q-bg: light-dark(var(--q-bg-light), var(--q-bg-dark));
                --q-surface: light-dark(var(--q-surface-light), var(--q-surface-dark));
                --q-surface-subtle: light-dark(var(--q-surface-subtle-light), var(--q-surface-subtle-dark));
                --q-surface-elevated: light-dark(var(--q-surface-elevated-light), var(--q-surface-elevated-dark));
                --q-border: light-dark(var(--q-border-light), var(--q-border-dark));
                --q-border-strong: light-dark(var(--q-border-strong-light), var(--q-border-strong-dark));
                --q-text: light-dark(var(--q-text-light), var(--q-text-dark));
                --q-text-secondary: light-dark(var(--q-text-secondary-light), var(--q-text-secondary-dark));
                --q-text-muted: light-dark(var(--q-text-muted-light), var(--q-text-muted-dark));
                --q-primary: light-dark(var(--q-primary-light), var(--q-primary-dark));
                --q-primary-fg: light-dark(var(--q-primary-fg-light), var(--q-primary-fg-dark));
                --q-accent: light-dark(var(--q-accent-light), var(--q-accent-dark));
                --q-success: light-dark(var(--q-success-light), var(--q-success-dark));
                --q-success-bg: light-dark(var(--q-success-bg-light), var(--q-success-bg-dark));
                --q-success-border: light-dark(var(--q-success-border-light), var(--q-success-border-dark));
                --q-warning: light-dark(var(--q-warning-light), var(--q-warning-dark));
                --q-warning-bg: light-dark(var(--q-warning-bg-light), var(--q-warning-bg-dark));
                --q-warning-border: light-dark(var(--q-warning-border-light), var(--q-warning-border-dark));
                --q-danger: light-dark(var(--q-danger-light), var(--q-danger-dark));
                --q-danger-bg: light-dark(var(--q-danger-bg-light), var(--q-danger-bg-dark));
                --q-danger-border: light-dark(var(--q-danger-border-light), var(--q-danger-border-dark));
            }

            accent-color: var(--q-accent);
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", system-ui, sans-serif;
            background-color: var(--q-bg);
            color: var(--q-text);
            line-height: 1.5;
            text-align: start;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6 {
            margin: 0;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.25;
            text-wrap: balance;
            color: var(--q-text);
        }

        p, li {
            margin: 0;
            text-wrap: pretty;
            overflow-wrap: break-word;
        }

        code, pre, .q-mono {
            font-family: "SF Mono", "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8125rem;
        }

        .q-tabular {
            font-variant-numeric: tabular-nums;
        }

        .visually-hidden:where(:not(:focus-within, :active)) {
            position: absolute !important;
            clip-path: inset(50%) !important;
            overflow: hidden !important;
            width: 1px !important;
            height: 1px !important;
            margin: -1px !important;
            padding: 0 !important;
            border: 0 !important;
            white-space: nowrap !important;
        }

        .q-skip-link:focus,
        .q-skip-link:active {
            position: fixed;
            top: 0.75rem;
            left: 0.75rem;
            z-index: 9999;
            padding: 0.5rem 1rem;
            background: var(--q-primary);
            color: var(--q-primary-fg);
            border-radius: var(--q-radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: var(--q-shadow-md);
        }

        /* Layout & Shell */
        .q-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .q-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: var(--q-surface-elevated);
            backdrop-filter: saturate(180%) blur(14px);
            -webkit-backdrop-filter: saturate(180%) blur(14px);
            border-bottom: 1px solid var(--q-border);
        }

        .q-topbar-inner {
            max-width: 82rem;
            margin: 0 auto;
            padding: 0.75rem 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .q-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .q-brand-mark {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.4375rem;
            background: var(--q-primary);
            color: var(--q-primary-fg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--q-shadow-sm);
        }

        .q-brand-title {
            font-size: 0.9375rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--q-text);
        }

        .q-version-pill {
            font-size: 0.6875rem;
            font-weight: 500;
            padding: 0.125rem 0.5rem;
            border-radius: var(--q-radius-full);
            background: var(--q-surface-subtle);
            color: var(--q-text-muted);
            border: 1px solid var(--q-border);
            font-variant-numeric: tabular-nums;
        }

        .q-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem;
            background: var(--q-surface-subtle);
            border: 1px solid var(--q-border);
            border-radius: var(--q-radius-md);
        }

        .q-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4375rem;
            padding: 0.375rem 0.75rem;
            border-radius: calc(var(--q-radius-md) - 2px);
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--q-text-secondary);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: background-color 140ms ease, color 140ms ease, box-shadow 140ms ease;
        }

        .q-nav-btn:hover {
            color: var(--q-text);
        }

        .q-nav-btn[aria-current="page"] {
            background: var(--q-surface);
            color: var(--q-text);
            box-shadow: var(--q-shadow-sm);
        }

        .q-main {
            flex: 1;
            max-width: 82rem;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        .q-main:focus {
            outline: none;
        }

        /* Grid & Cards */
        .q-workspace-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        @media (min-width: 64rem) {
            .q-workspace-grid {
                grid-template-columns: minmax(0, 1.65fr) minmax(21rem, 1fr);
            }
        }

        .q-stack {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .q-card {
            background: var(--q-surface);
            border: 1px solid var(--q-border);
            border-radius: var(--q-radius-lg);
            box-shadow: var(--q-shadow-sm);
            overflow: hidden;
        }

        .q-card-header {
            padding: 1.125rem 1.375rem;
            border-bottom: 1px solid var(--q-border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .q-card-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--q-text);
        }

        .q-card-subtitle {
            font-size: 0.8125rem;
            color: var(--q-text-muted);
            margin-top: 0.125rem;
        }

        .q-card-body {
            padding: 1.375rem;
        }

        /* Forms & Inputs */
        .q-field {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .q-field + .q-field {
            margin-top: 1.125rem;
        }

        .q-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .q-label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--q-text);
        }

        .q-counter {
            font-size: 0.75rem;
            color: var(--q-text-muted);
            font-variant-numeric: tabular-nums;
        }

        .q-input,
        .q-textarea,
        .q-select {
            width: 100%;
            padding: 0.5625rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            color: var(--q-text);
            background-color: var(--q-surface);
            border: 1px solid var(--q-border-strong);
            border-radius: var(--q-radius-md);
            transition: border-color 140ms ease, box-shadow 140ms ease;
        }

        .q-textarea {
            resize: vertical;
        }

        .q-input::placeholder,
        .q-textarea::placeholder {
            color: var(--q-text-muted);
        }

        .q-input:focus-visible,
        .q-textarea:focus-visible,
        .q-select:focus-visible,
        .q-btn:focus-visible,
        .q-nav-btn:focus-visible,
        .q-segmented-btn:focus-visible {
            outline: 2px solid var(--q-accent);
            outline-offset: 1px;
            border-color: var(--q-accent);
        }

        .q-meter-track {
            height: 0.25rem;
            width: 100%;
            background: var(--q-surface-subtle);
            border-radius: var(--q-radius-full);
            overflow: hidden;
            margin-top: 0.25rem;
        }

        .q-meter-fill {
            height: 100%;
            border-radius: var(--q-radius-full);
            transition: width 200ms ease, background-color 200ms ease;
        }

        /* Buttons */
        .q-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4375rem;
            padding: 0.5rem 0.9375rem;
            font-size: 0.8125rem;
            font-weight: 500;
            line-height: 1.25;
            border-radius: var(--q-radius-md);
            border: 1px solid transparent;
            cursor: pointer;
            transition: opacity 140ms ease, background-color 140ms ease, border-color 140ms ease, transform 80ms ease;
            white-space: nowrap;
        }

        .q-btn:active:not(:disabled) {
            transform: scale(0.99);
        }

        .q-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .q-btn-primary {
            background: var(--q-primary);
            color: var(--q-primary-fg);
            box-shadow: var(--q-shadow-sm);
        }

        .q-btn-primary:hover:not(:disabled) {
            opacity: 0.92;
        }

        .q-btn-secondary {
            background: var(--q-surface);
            color: var(--q-text);
            border-color: var(--q-border-strong);
        }

        .q-btn-secondary:hover:not(:disabled) {
            background: var(--q-surface-subtle);
        }

        .q-btn-ghost {
            background: transparent;
            color: var(--q-text-secondary);
            padding: 0.375rem 0.625rem;
        }

        .q-btn-ghost:hover:not(:disabled) {
            background: var(--q-surface-subtle);
            color: var(--q-text);
        }

        /* Segmented Control */
        .q-segmented {
            display: inline-flex;
            padding: 0.1875rem;
            background: var(--q-surface-subtle);
            border: 1px solid var(--q-border);
            border-radius: var(--q-radius-md);
            gap: 0.125rem;
        }

        .q-segmented-btn {
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: calc(var(--q-radius-md) - 3px);
            background: transparent;
            color: var(--q-text-muted);
            border: none;
            cursor: pointer;
        }

        .q-segmented-btn[aria-pressed="true"] {
            background: var(--q-surface);
            color: var(--q-text);
            box-shadow: var(--q-shadow-sm);
        }

        /* Badges & Status Pills */
        .q-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3125rem;
            padding: 0.1875rem 0.5625rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: var(--q-radius-full);
            font-variant-numeric: tabular-nums;
            border: 1px solid transparent;
        }

        .q-badge-success {
            background: var(--q-success-bg);
            color: var(--q-success);
            border-color: var(--q-success-border);
        }

        .q-badge-warning {
            background: var(--q-warning-bg);
            color: var(--q-warning);
            border-color: var(--q-warning-border);
        }

        .q-badge-danger {
            background: var(--q-danger-bg);
            color: var(--q-danger);
            border-color: var(--q-danger-border);
        }

        .q-badge-neutral {
            background: var(--q-surface-subtle);
            color: var(--q-text-secondary);
            border-color: var(--q-border);
        }

        /* Checklist List */
        .q-checklist {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }

        .q-check-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: var(--q-radius-md);
            background: var(--q-surface-subtle);
            border: 1px solid var(--q-border);
        }

        .q-check-icon {
            flex-shrink: 0;
            width: 1.125rem;
            height: 1.125rem;
            margin-top: 0.125rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .q-check-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--q-text);
        }

        .q-check-desc {
            font-size: 0.75rem;
            color: var(--q-text-secondary);
            margin-top: 0.125rem;
        }

        /* Metric Bento Grid */
        .q-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
            gap: 0.875rem;
        }

        .q-kpi-card {
            padding: 1rem 1.125rem;
            background: var(--q-surface-subtle);
            border: 1px solid var(--q-border);
            border-radius: var(--q-radius-md);
        }

        .q-kpi-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--q-text-muted);
        }

        .q-kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--q-text);
            margin-top: 0.25rem;
            font-variant-numeric: tabular-nums;
        }

        /* SERP & AI Preview */
        .q-serp-card {
            padding: 1.125rem 1.25rem;
            background: var(--q-surface-subtle);
            border: 1px solid var(--q-border);
            border-radius: var(--q-radius-md);
            transition: max-width 200ms ease;
        }

        .q-serp-breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--q-text-secondary);
            margin-bottom: 0.375rem;
        }

        .q-serp-favicon {
            width: 1.375rem;
            height: 1.375rem;
            border-radius: var(--q-radius-full);
            background: var(--q-surface);
            border: 1px solid var(--q-border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--q-text-muted);
            flex-shrink: 0;
        }

        .q-serp-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: var(--q-accent);
            line-height: 1.35;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .q-serp-desc {
            font-size: 0.8125rem;
            color: var(--q-text-secondary);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Readability stats */
        .q-stat-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .q-stat-cell {
            padding: 0.625rem;
            background: var(--q-surface-subtle);
            border: 1px solid var(--q-border);
            border-radius: var(--q-radius-md);
            text-align: center;
        }

        .q-stat-val {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--q-text);
            font-variant-numeric: tabular-nums;
        }

        .q-stat-lbl {
            font-size: 0.6875rem;
            color: var(--q-text-muted);
            margin-top: 0.125rem;
        }

        /* Tickets & Alerts */
        .q-ticket {
            padding: 0.875rem 1rem;
            border: 1px solid var(--q-border);
            border-radius: var(--q-radius-md);
            background: var(--q-surface-subtle);
        }

        .q-ticket + .q-ticket {
            margin-top: 0.625rem;
        }

        .q-ticket-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.375rem;
        }

        .q-alert {
            padding: 0.75rem 1rem;
            border-radius: var(--q-radius-md);
            font-size: 0.8125rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .q-alert-danger {
            background: var(--q-danger-bg);
            color: var(--q-danger);
            border: 1px solid var(--q-danger-border);
        }

        .q-alert-success {
            background: var(--q-success-bg);
            color: var(--q-success);
            border: 1px solid var(--q-success-border);
        }

        /* Wizard Stepper */
        .q-wizard-wrap {
            max-width: 38rem;
            margin: 1.5rem auto 0;
        }

        .q-stepper {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            list-style: none;
            padding: 0;
        }

        .q-step-pill {
            padding: 0.5rem 0.75rem;
            border-radius: var(--q-radius-md);
            border: 1px solid var(--q-border);
            background: var(--q-surface-subtle);
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--q-text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .q-step-pill[data-active="true"] {
            background: var(--q-surface);
            border-color: var(--q-primary);
            color: var(--q-text);
            font-weight: 600;
        }

        .q-step-pill[data-done="true"] {
            color: var(--q-success);
            border-color: var(--q-success-border);
        }
    </style>
    @php
        $packageRoot = dirname((string) (new \ReflectionClass(\Quinrel\Laravel\QuinrelServiceProvider::class))->getFileName(), 2);
        $publishedViteManifest = public_path('vendor/quinrel/build/.vite/manifest.json');
        $publishedLegacyManifest = public_path('vendor/quinrel/build/manifest.json');
        $packageViteManifest = $packageRoot . '/public/build/.vite/manifest.json';

        $manifestPath = file_exists($publishedViteManifest)
            ? $publishedViteManifest
            : (file_exists($publishedLegacyManifest) ? $publishedLegacyManifest : (file_exists($packageViteManifest) ? $packageViteManifest : null));

        $manifest = $manifestPath ? json_decode((string) file_get_contents($manifestPath), true) : null;
        $entryFile = is_array($manifest) && isset($manifest['resources/js/app.jsx']['file'])
            ? $manifest['resources/js/app.jsx']['file']
            : null;
    @endphp
    @if($entryFile && ($manifestPath === $publishedViteManifest || $manifestPath === $publishedLegacyManifest))
        <script type="module" src="{{ asset('vendor/quinrel/build/' . $entryFile) }}"></script>
    @elseif($entryFile && $manifestPath === $packageViteManifest && file_exists($packageRoot . '/public/build/' . $entryFile))
        <script type="module">{!! file_get_contents($packageRoot . '/public/build/' . $entryFile) !!}</script>
    @else
        <script type="module" src="http://localhost:5173/resources/js/app.jsx"></script>
    @endif
</head>
<body>
    <a href="#quinrel-main" class="q-skip-link visually-hidden">Skip to main content</a>
    <div id="quinrel-app"></div>
</body>
</html>
