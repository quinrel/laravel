import React from 'react';

export default function ReadabilityScore({ data, isUpdating }) {
    if (!data) return null;

    const numericScore = typeof data.score === 'number' ? data.score : 0;
    const toneClass =
        numericScore >= 60
            ? 'q-badge-success'
            : numericScore >= 50
                ? 'q-badge-warning'
                : numericScore > 0
                    ? 'q-badge-danger'
                    : 'q-badge-neutral';

    return (
        <section className="q-card" aria-labelledby="readability-heading">
            <div className="q-card-header">
                <div>
                    <h2 id="readability-heading" className="q-card-title">Readability &amp; Clarity</h2>
                    <p className="q-card-subtitle">Flesch-Kincaid comprehension index</p>
                </div>
                <span className={`q-badge ${toneClass}`}>
                    {data.level || 'N/A'}
                </span>
            </div>

            <div className="q-card-body">
                <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                    <div className="q-tabular" style={{ fontSize: '2.25rem', fontWeight: 700, letterSpacing: '-0.04em', lineHeight: 1 }}>
                        {numericScore}
                        <span style={{ fontSize: '0.875rem', fontWeight: 500, color: 'var(--q-text-muted)', marginLeft: '0.25rem' }}>/ 100</span>
                    </div>
                    {isUpdating && (
                        <span style={{ fontSize: '0.75rem', color: 'var(--q-text-muted)' }}>Syncing…</span>
                    )}
                </div>

                <p style={{ fontSize: '0.8125rem', color: 'var(--q-text-secondary)' }}>
                    {data.message}
                </p>

                <div className="q-stat-row">
                    <div className="q-stat-cell">
                        <div className="q-stat-val">{data.stats?.words ?? 0}</div>
                        <div className="q-stat-lbl">Words</div>
                    </div>
                    <div className="q-stat-cell">
                        <div className="q-stat-val">{data.stats?.sentences ?? 0}</div>
                        <div className="q-stat-lbl">Sentences</div>
                    </div>
                    <div className="q-stat-cell">
                        <div className="q-stat-val">{data.stats?.syllables ?? 0}</div>
                        <div className="q-stat-lbl">Syllables</div>
                    </div>
                </div>
            </div>
        </section>
    );
}
