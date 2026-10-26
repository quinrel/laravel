import React from 'react';

const StatusIcon = ({ status }) => {
    if (status === 'success') {
        return (
            <span className="q-check-icon" style={{ color: 'var(--q-success)' }} aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </span>
        );
    }

    if (status === 'warning') {
        return (
            <span className="q-check-icon" style={{ color: 'var(--q-warning)' }} aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
            </span>
        );
    }

    if (status === 'error') {
        return (
            <span className="q-check-icon" style={{ color: 'var(--q-danger)' }} aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <path d="m15 9-6 6" />
                    <path d="m9 9 6 6" />
                </svg>
            </span>
        );
    }

    return (
        <span className="q-check-icon" style={{ color: 'var(--q-text-muted)' }} aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="9" />
            </svg>
        </span>
    );
};

export default function SeoChecklist({ data, geo }) {
    if (!data) return null;

    const renderItem = (item, label) => {
        if (!item) return null;

        return (
            <li className="q-check-item">
                <StatusIcon status={item.status} />
                <div>
                    <div className="q-check-title">{label}</div>
                    <p className="q-check-desc">{item.message}</p>
                </div>
            </li>
        );
    };

    const geoScore = typeof geo?.score === 'number' ? geo.score : null;
    const geoBadgeClass =
        geoScore === null
            ? 'q-badge-neutral'
            : geoScore >= 80
                ? 'q-badge-success'
                : geoScore >= 50
                    ? 'q-badge-warning'
                    : 'q-badge-danger';

    return (
        <div className="q-stack">
            {(data.geo_direct_answers || data.geo_structured_data || data.geo_factual_density) && (
                <section className="q-card" aria-labelledby="geo-checklist-heading">
                    <div className="q-card-header">
                        <div>
                            <h2 id="geo-checklist-heading" className="q-card-title">GEO &amp; AI Citability</h2>
                            <p className="q-card-subtitle">Answer engine retrieval readiness</p>
                        </div>
                        {geoScore !== null && (
                            <span className={`q-badge ${geoBadgeClass}`}>
                                {geoScore} / 100
                            </span>
                        )}
                    </div>
                    <div className="q-card-body">
                        <ul className="q-checklist" role="list">
                            {renderItem(data.geo_direct_answers, 'Direct Answer Capsules (Q&A)')}
                            {renderItem(data.geo_structured_data, 'Structured Lists & Tables')}
                            {renderItem(data.geo_factual_density, 'Factual & Citation Density')}
                        </ul>
                    </div>
                </section>
            )}

            <section className="q-card" aria-labelledby="seo-checklist-heading">
                <div className="q-card-header">
                    <div>
                        <h2 id="seo-checklist-heading" className="q-card-title">On-Page SEO Signals</h2>
                        <p className="q-card-subtitle">Core technical &amp; lexical checks</p>
                    </div>
                </div>
                <div className="q-card-body">
                    <ul className="q-checklist" role="list">
                        {renderItem(data.title_length, 'Title Length')}
                        {renderItem(data.description_length, 'Description Length')}
                        {renderItem(data.keyword_in_title, 'Keyword in Title')}
                        {renderItem(data.keyword_in_description, 'Keyword in Description')}
                        {renderItem(data.keyword_in_content, 'Keyword in Content')}
                        {renderItem(data.headings_check, 'Heading Hierarchy (H1/H2)')}
                        {renderItem(data.alt_text_check, 'Image Alt Attributes')}
                    </ul>
                </div>
            </section>
        </div>
    );
}
