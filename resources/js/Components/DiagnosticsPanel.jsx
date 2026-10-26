import React, { useEffect, useState } from 'react';

export default function DiagnosticsPanel() {
    const [crawlReport, setCrawlReport] = useState(null);
    const [a11yReport, setA11yReport] = useState(null);
    const [inspectUrl, setInspectUrl] = useState('');
    const [inspectResult, setInspectResult] = useState(null);
    const [loadingCrawl, setLoadingCrawl] = useState(false);
    const [loadingInspect, setLoadingInspect] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const basePath = document.querySelector('meta[name="quinrel-base-path"]')?.getAttribute('content') || '/quinrel';

    useEffect(() => {
        fetch(`${basePath}/api/diagnostics`, { headers: { Accept: 'application/json' } })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                if (data?.crawl) setCrawlReport(data.crawl);
                if (data?.accessibility) setA11yReport(data.accessibility);
            })
            .catch(() => {});
    }, [basePath]);

    const handleRunCrawl = async () => {
        setLoadingCrawl(true);
        setErrorMessage('');
        try {
            const res = await fetch(`${basePath}/api/crawl`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const data = await res.json();
            if (!res.ok) {
                setErrorMessage(data.message || 'Crawl request failed.');
            } else {
                setCrawlReport(data);
            }
        } catch {
            setErrorMessage('Network error while executing crawl.');
        } finally {
            setLoadingCrawl(false);
        }
    };

    const handleInspectUrl = async (e) => {
        e.preventDefault();
        if (!inspectUrl) return;
        setLoadingInspect(true);
        setErrorMessage('');
        try {
            const res = await fetch(`${basePath}/api/inspect-url`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ url: inspectUrl }),
            });
            const data = await res.json();
            if (!res.ok) {
                setErrorMessage(data.message || 'Inspection failed.');
            } else {
                setInspectResult(data);
            }
        } catch {
            setErrorMessage('Network error while inspecting URL.');
        } finally {
            setLoadingInspect(false);
        }
    };

    const plan = crawlReport?.plan;
    const priorityBadge = (priority) => {
        if (priority === 'P0' || priority === 'Critical') return 'q-badge-danger';
        if (priority === 'P1' || priority === 'Warning') return 'q-badge-warning';
        return 'q-badge-neutral';
    };

    return (
        <div className="q-stack">
            {errorMessage && (
                <div className="q-alert q-alert-danger" role="alert">
                    <span>{errorMessage}</span>
                </div>
            )}

            <section className="q-card" aria-labelledby="crawl-health-heading">
                <div className="q-card-header">
                    <div>
                        <h1 id="crawl-health-heading" className="q-card-title">Site Crawl Health &amp; Remediation Plan</h1>
                        <p className="q-card-subtitle">
                            {crawlReport?.crawled_at
                                ? `Last crawl completed ${crawlReport.crawled_at}`
                                : 'No site crawl snapshot cached yet. Trigger a crawl to audit internal URLs.'}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={handleRunCrawl}
                        disabled={loadingCrawl}
                        className="q-btn q-btn-primary"
                    >
                        {loadingCrawl ? 'Crawling Site…' : 'Run Crawl Now'}
                    </button>
                </div>

                {plan && (
                    <div className="q-card-body">
                        <div className="q-kpi-grid">
                            <div className="q-kpi-card">
                                <div className="q-kpi-label">Health Score</div>
                                <div className="q-kpi-value">{plan.score} / 100</div>
                            </div>
                            <div className="q-kpi-card">
                                <div className="q-kpi-label">Pages Crawled</div>
                                <div className="q-kpi-value">{crawlReport?.crawl?.total_crawled ?? 0}</div>
                            </div>
                            <div className="q-kpi-card">
                                <div className="q-kpi-label">Critical (P0) / High (P1)</div>
                                <div className="q-kpi-value">
                                    {plan.summary?.P0 ?? plan.counts?.P0 ?? 0} / {plan.summary?.P1 ?? plan.counts?.P1 ?? 0}
                                </div>
                            </div>
                        </div>

                        {Array.isArray(plan.tickets) && plan.tickets.length > 0 && (
                            <div style={{ marginTop: '1.5rem' }}>
                                <h2 className="q-card-title" style={{ marginBottom: '0.75rem' }}>Prioritized Remediation Tickets</h2>
                                {plan.tickets.slice(0, 10).map((ticket, index) => (
                                    <div key={index} className="q-ticket">
                                        <div className="q-ticket-top">
                                            <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                                <span className={`q-badge ${priorityBadge(ticket.priority)}`}>
                                                    {ticket.priority}
                                                </span>
                                                <span style={{ fontSize: '0.8125rem', fontWeight: 600 }}>
                                                    {ticket.title}
                                                </span>
                                            </div>
                                            <span className="q-mono" style={{ color: 'var(--q-text-muted)' }}>
                                                {ticket.url}
                                            </span>
                                        </div>
                                        <p style={{ fontSize: '0.8125rem', color: 'var(--q-text-secondary)' }}>
                                            {ticket.recommendation || ticket.remediation}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </section>

            <section className="q-card" aria-labelledby="gsc-inspect-heading">
                <div className="q-card-header">
                    <div>
                        <h2 id="gsc-inspect-heading" className="q-card-title">Google Index Status &amp; Live URL Inspection</h2>
                        <p className="q-card-subtitle">Query Google Search Console URL Inspection API and generate deterministic fixes.</p>
                    </div>
                </div>

                <div className="q-card-body">
                    <form onSubmit={handleInspectUrl} style={{ display: 'flex', gap: '0.625rem', alignItems: 'center' }}>
                        <label htmlFor="quinrel-inspect-url" className="visually-hidden">URL to inspect</label>
                        <input
                            id="quinrel-inspect-url"
                            type="text"
                            className="q-input"
                            value={inspectUrl}
                            onChange={(e) => setInspectUrl(e.target.value)}
                            placeholder="https://example.com/page"
                        />
                        <button
                            type="submit"
                            disabled={loadingInspect}
                            className="q-btn q-btn-primary"
                        >
                            {loadingInspect ? 'Inspecting…' : 'Inspect Live URL'}
                        </button>
                    </form>

                    {inspectResult?.diagnosis && (
                        <div style={{ marginTop: '1.25rem' }} className="q-stack">
                            <div className="q-ticket" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                                <div>
                                    <div style={{ fontSize: '0.875rem', fontWeight: 600 }}>
                                        {inspectResult.diagnosis.coverage_state}
                                    </div>
                                    <div style={{ fontSize: '0.75rem', color: 'var(--q-text-muted)', marginTop: '0.125rem' }}>
                                        Verdict: <span className="q-mono">{inspectResult.diagnosis.verdict}</span> · Indexing: <span className="q-mono">{inspectResult.diagnosis.indexing_state}</span>
                                    </div>
                                </div>
                                <span className={`q-badge ${inspectResult.diagnosis.verdict === 'PASS' ? 'q-badge-success' : 'q-badge-warning'}`}>
                                    {(inspectResult.diagnosis.health_status || inspectResult.diagnosis.health_label || inspectResult.diagnosis.verdict || 'INSPECTED').toUpperCase()}
                                </span>
                            </div>

                            {Array.isArray(inspectResult.actions) &&
                                inspectResult.actions.map((action, idx) => (
                                    <div key={idx} className="q-ticket">
                                        <div className="q-ticket-top">
                                            <span className={`q-badge ${priorityBadge(action.severity)}`}>
                                                {action.severity?.toUpperCase()}
                                            </span>
                                            <span style={{ fontSize: '0.8125rem', fontWeight: 600 }}>{action.title}</span>
                                        </div>
                                        <p style={{ fontSize: '0.8125rem', color: 'var(--q-text-secondary)' }}>{action.description}</p>
                                        <p style={{ fontSize: '0.8125rem', color: 'var(--q-accent)', marginTop: '0.375rem', fontWeight: 500 }}>
                                            {action.remediation}
                                        </p>
                                    </div>
                                ))}
                        </div>
                    )}
                </div>
            </section>

            {a11yReport && (
                <section className="q-card" aria-labelledby="a11y-summary-heading">
                    <div className="q-card-header">
                        <div>
                            <h2 id="a11y-summary-heading" className="q-card-title">Accessibility (WCAG 2.1/2.2 AA) Summary</h2>
                            <p className="q-card-subtitle">{a11yReport.url}</p>
                        </div>
                        <span className={`q-badge ${a11yReport.score >= 90 ? 'q-badge-success' : 'q-badge-warning'}`}>
                            {a11yReport.score} / 100
                        </span>
                    </div>
                    <div className="q-card-body">
                        <p style={{ fontSize: '0.8125rem', color: 'var(--q-text-secondary)' }}>
                            Detected <strong className="q-tabular">{a11yReport.violation_count}</strong> static WCAG violation(s) on the target document.
                        </p>
                    </div>
                </section>
            )}
        </div>
    );
}
