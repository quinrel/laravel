import React, { useState } from 'react';

export default function SearchPreview({ title, description, focusKeyword, url = 'https://example.com/insights/guide' }) {
    const [surface, setSurface] = useState('desktop');

    const displayTitle = title || 'Untitled Document — Configure Your SEO Title';
    const displayDescription =
        description ||
        'Provide a concise 120–160 character meta description so search engines and AI answer engines can summarize and cite this page accurately.';

    return (
        <section className="q-card" aria-labelledby="serp-preview-heading">
            <div className="q-card-header">
                <div>
                    <h2 id="serp-preview-heading" className="q-card-title">Search &amp; Answer Engine Preview</h2>
                    <p className="q-card-subtitle">Inspect how your metadata renders across Google SERP and AI citation cards.</p>
                </div>

                <div className="q-segmented" role="group" aria-label="Preview viewport">
                    <button
                        type="button"
                        onClick={() => setSurface('desktop')}
                        aria-pressed={surface === 'desktop'}
                        className="q-segmented-btn"
                    >
                        Desktop
                    </button>
                    <button
                        type="button"
                        onClick={() => setSurface('mobile')}
                        aria-pressed={surface === 'mobile'}
                        className="q-segmented-btn"
                    >
                        Mobile
                    </button>
                    <button
                        type="button"
                        onClick={() => setSurface('ai')}
                        aria-pressed={surface === 'ai'}
                        className="q-segmented-btn"
                    >
                        AI Citation
                    </button>
                </div>
            </div>

            <div className="q-card-body">
                {surface !== 'ai' ? (
                    <div
                        className="q-serp-card"
                        style={{ maxWidth: surface === 'mobile' ? '23.5rem' : '38rem' }}
                    >
                        <div className="q-serp-breadcrumb">
                            <span className="q-serp-favicon" aria-hidden="true">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M2 12h20" />
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                </svg>
                            </span>
                            <span className="q-mono" style={{ fontSize: '0.75rem' }}>{url}</span>
                        </div>

                        <div className="q-serp-title">{displayTitle}</div>

                        <p
                            className="q-serp-desc"
                            style={{ WebkitLineClamp: surface === 'mobile' ? 3 : 2 }}
                        >
                            {displayDescription}
                        </p>
                    </div>
                ) : (
                    <div className="q-serp-card" style={{ maxWidth: '38rem' }}>
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                            <span className="q-badge q-badge-neutral">
                                AI Overview / Grounded Source Card
                            </span>
                            <span className="q-mono" style={{ fontSize: '0.6875rem', color: 'var(--q-text-muted)' }}>
                                ?format=md
                            </span>
                        </div>
                        <p style={{ fontSize: '0.8125rem', color: 'var(--q-text)', fontWeight: 600, marginBottom: '0.25rem' }}>
                            {displayTitle}
                        </p>
                        <p style={{ fontSize: '0.75rem', color: 'var(--q-text-secondary)' }}>
                            {displayDescription}
                        </p>
                        {focusKeyword && (
                            <div style={{ marginTop: '0.625rem', fontSize: '0.6875rem', color: 'var(--q-text-muted)' }}>
                                Grounded Entity: <strong style={{ color: 'var(--q-text)' }}>{focusKeyword}</strong>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}
