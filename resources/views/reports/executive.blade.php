<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Quinrel Executive Audit Report — {{ $report['url'] ?? '' }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --r-bg: #fafafa;
            --r-surface: #ffffff;
            --r-subtle: #f4f4f5;
            --r-border: #e4e4e7;
            --r-text: #09090b;
            --r-secondary: #3f3f46;
            --r-muted: #71717a;
            --r-success: #059669;
            --r-success-bg: #ecfdf5;
            --r-warning: #d97706;
            --r-warning-bg: #fffbeb;
            --r-danger: #dc2626;
            --r-danger-bg: #fef2f2;

            @media (prefers-color-scheme: dark) {
                --r-bg: #09090b;
                --r-surface: #121215;
                --r-subtle: #18181b;
                --r-border: #27272a;
                --r-text: #fafafa;
                --r-secondary: #d4d4d8;
                --r-muted: #a1a1aa;
                --r-success: #34d399;
                --r-success-bg: rgba(16, 185, 129, 0.15);
                --r-warning: #fbbf24;
                --r-warning-bg: rgba(245, 158, 11, 0.15);
                --r-danger: #f87171;
                --r-danger-bg: rgba(239, 68, 68, 0.15);
            }
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", system-ui, sans-serif;
            color: var(--r-text);
            background: var(--r-bg);
            margin: 0;
            padding: 2.5rem 1.5rem;
            line-height: 1.5;
            text-align: start;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3 {
            margin: 0;
            letter-spacing: -0.02em;
            line-height: 1.25;
            text-wrap: balance;
        }

        p, td {
            text-wrap: pretty;
            overflow-wrap: break-word;
        }

        .container {
            max-width: 68rem;
            margin: 0 auto;
            background: var(--r-surface);
            border: 1px solid var(--r-border);
            border-radius: 0.875rem;
            padding: 2.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            border-bottom: 1px solid var(--r-border);
            padding-bottom: 1.5rem;
            margin-bottom: 1.75rem;
        }

        .eyebrow {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--r-muted);
            margin-bottom: 0.375rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--r-text);
            margin-bottom: 0.5rem;
        }

        h2 {
            font-size: 1.0625rem;
            font-weight: 600;
            margin: 2rem 0 0.875rem 0;
            color: var(--r-text);
        }

        .meta {
            font-size: 0.8125rem;
            color: var(--r-secondary);
        }

        .mono {
            font-family: "SF Mono", "JetBrains Mono", ui-monospace, SFMono-Regular, monospace;
            font-size: 0.8125rem;
        }

        .score-box {
            text-align: end;
            padding: 0.75rem 1.125rem;
            border-radius: 0.75rem;
            background: var(--r-subtle);
            border: 1px solid var(--r-border);
            font-variant-numeric: tabular-nums;
        }

        .score-box-label {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--r-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .score-box-value {
            font-size: 1.875rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            line-height: 1.15;
            margin-top: 0.125rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(13.5rem, 1fr));
            gap: 1rem;
        }

        .card {
            padding: 1.125rem;
            border: 1px solid var(--r-border);
            border-radius: 0.625rem;
            background: var(--r-subtle);
        }

        .card-label {
            font-size: 0.75rem;
            color: var(--r-muted);
            font-weight: 500;
        }

        .card-value {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin-top: 0.25rem;
            font-variant-numeric: tabular-nums;
        }

        .meter {
            height: 0.25rem;
            background: var(--r-border);
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 0.625rem;
        }

        .meter-bar {
            height: 100%;
            background: var(--r-text);
            border-radius: 9999px;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--r-border);
            border-radius: 0.625rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        caption {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip-path: inset(50%);
            white-space: nowrap;
        }

        th, td {
            text-align: start;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--r-border);
            vertical-align: top;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        th {
            background: var(--r-subtle);
            font-weight: 600;
            color: var(--r-secondary);
            font-size: 0.75rem;
        }

        .pill {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .sev-critical, .sev-p0 {
            background: var(--r-danger-bg);
            color: var(--r-danger);
        }

        .sev-warning, .sev-serious, .sev-high, .sev-p1 {
            background: var(--r-warning-bg);
            color: var(--r-warning);
        }

        .sev-moderate, .sev-recommendation, .sev-info, .sev-p2, .sev-p3 {
            background: var(--r-subtle);
            color: var(--r-secondary);
        }

        @media print {
            body { background: #ffffff; color: #09090b; padding: 0; }
            .container { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $overallScore = (int) ($report['overall_score'] ?? ($report['pagespeed']['score'] ?? 100));
        $psiScore = $report['pagespeed']['score'] ?? ($report['pillars']['performance']['score'] ?? 100);
        $indexScore = (int) ($report['pillars']['indexing']['score'] ?? 100);
        $seoScore = (int) ($report['pillars']['technical_seo']['score'] ?? 100);
        $a11yScore = (int) ($report['pillars']['accessibility']['score'] ?? 100);
    @endphp
    <main class="container">
        <header class="header">
            <div>
                <div class="eyebrow">Quinrel Diagnostic Dossier</div>
                <h1>Quinrel Executive SEO, Performance &amp; Accessibility Report</h1>
                <div class="meta">Target URL: <strong class="mono">{{ $report['url'] ?? '' }}</strong></div>
                <div class="meta">Generated: <span class="mono">{{ $report['generated_at'] ?? '' }}</span></div>
            </div>
            <div class="score-box">
                <div class="score-box-label">Composite Health</div>
                <div class="score-box-value">{{ $overallScore }}<span style="font-size: 1rem; color: var(--r-muted); font-weight: 500;">/100</span></div>
            </div>
        </header>

        <section aria-label="Pillar Scores" class="grid">
            <div class="card">
                <div class="card-label">PageSpeed / CWV</div>
                <div class="card-value">{{ $psiScore }}</div>
                <div class="meter" aria-hidden="true">
                    <div class="meter-bar" style="width: {{ is_numeric($psiScore) ? min(100, max(0, (int) $psiScore)) : 100 }}%"></div>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Google Indexing</div>
                <div class="card-value">{{ $indexScore }}%</div>
                <div class="meter" aria-hidden="true">
                    <div class="meter-bar" style="width: {{ min(100, max(0, $indexScore)) }}%"></div>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Technical SEO</div>
                <div class="card-value">{{ $seoScore }}%</div>
                <div class="meter" aria-hidden="true">
                    <div class="meter-bar" style="width: {{ min(100, max(0, $seoScore)) }}%"></div>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Accessibility (WCAG AA)</div>
                <div class="card-value">{{ $a11yScore }}%</div>
                <div class="meter" aria-hidden="true">
                    <div class="meter-bar" style="width: {{ min(100, max(0, $a11yScore)) }}%"></div>
                </div>
            </div>
        </section>

        <section aria-labelledby="remediation-heading">
            <h2 id="remediation-heading">Prioritized Remediation Action Plan</h2>
            @if(!empty($report['action_plan']))
                <div class="table-wrap">
                    <table>
                        <caption>Prioritized remediation tickets across all diagnostic pillars</caption>
                        <thead>
                            <tr>
                                <th scope="col">Pillar</th>
                                <th scope="col">Severity</th>
                                <th scope="col">Diagnostic Finding</th>
                                <th scope="col">Recommended Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['action_plan'] as $item)
                                <tr>
                                    <td style="font-weight: 600; white-space: nowrap;">{{ $item['pillar'] ?? '' }}</td>
                                    <td>
                                        <span class="pill sev-{{ strtolower((string) ($item['severity'] ?? 'info')) }}">
                                            {{ $item['severity'] ?? '' }}
                                        </span>
                                    </td>
                                    <td style="font-weight: 500;">{{ $item['title'] ?? '' }}</td>
                                    <td style="color: var(--r-secondary);">{{ $item['remediation'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="meta">No critical or warning remediation tickets detected across the audited pillars.</p>
            @endif
        </section>
    </main>
</body>
</html>
