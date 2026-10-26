import React, { useState, useEffect } from 'react';
import SeoChecklist from './SeoChecklist';
import ReadabilityScore from './ReadabilityScore';
import SearchPreview from './SearchPreview';

const SAMPLE_PRESET = {
    focusKeyword: 'Generative Engine Optimization',
    title: 'Generative Engine Optimization in Laravel — Complete Guide',
    description: 'Learn how Generative Engine Optimization in Laravel boosts AI citations across Perplexity, ChatGPT Search, and Google AI Overviews.',
    content: `<h1>Generative Engine Optimization in Laravel</h1>
<h2>What is Generative Engine Optimization (GEO)?</h2>
<p>Generative Engine Optimization is the engineering discipline of structuring web content, Schema.org entity graphs, and machine-readable Markdown endpoints so AI answer engines like Perplexity, ChatGPT Search, and Google AI Overviews accurately cite your pages.</p>
<ul>
  <li>Place concise 25 to 80 word direct-answer capsules immediately after question headings.</li>
  <li>Expose clean Markdown via content negotiation and root llms.txt discovery files.</li>
</ul>
<p>Empirical benchmarks show that combining quantitative evidence—such as a 37% lift in citation recall and 120ms faster parsing—with authoritative references like <a href="https://schema.org/FAQPage">Schema.org FAQPage</a> significantly increases domain attribution.</p>`,
};

export default function ContentEditor() {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [content, setContent] = useState('');
    const [focusKeyword, setFocusKeyword] = useState('');

    const [analysisResult, setAnalysisResult] = useState(null);
    const [isAnalyzing, setIsAnalyzing] = useState(false);

    useEffect(() => {
        const analyze = async () => {
            setIsAnalyzing(true);
            try {
                const basePath = document.querySelector('meta[name="quinrel-base-path"]')?.getAttribute('content') || '/quinrel';
                const response = await fetch(`${basePath}/api/analyze`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        title,
                        description,
                        content,
                        focus_keyword: focusKeyword,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    setAnalysisResult(data);
                }
            } catch (error) {
                console.error('Error analyzing content:', error);
            } finally {
                setIsAnalyzing(false);
            }
        };

        const timeoutId = setTimeout(() => {
            analyze();
        }, 450);

        return () => clearTimeout(timeoutId);
    }, [title, description, content, focusKeyword]);

    const applySample = () => {
        setFocusKeyword(SAMPLE_PRESET.focusKeyword);
        setTitle(SAMPLE_PRESET.title);
        setDescription(SAMPLE_PRESET.description);
        setContent(SAMPLE_PRESET.content);
    };

    const titleLen = title.length;
    const titlePercent = Math.min(100, Math.round((titleLen / 60) * 100));
    const titleColor = titleLen >= 30 && titleLen <= 60 ? 'var(--q-success)' : titleLen > 0 ? 'var(--q-warning)' : 'var(--q-border-strong)';

    const descLen = description.length;
    const descPercent = Math.min(100, Math.round((descLen / 160) * 100));
    const descColor = descLen >= 120 && descLen <= 160 ? 'var(--q-success)' : descLen > 0 ? 'var(--q-warning)' : 'var(--q-border-strong)';

    return (
        <div className="q-workspace-grid">
            <div className="q-stack">
                <section className="q-card" aria-labelledby="editor-heading">
                    <div className="q-card-header">
                        <div>
                            <h1 id="editor-heading" className="q-card-title">Content &amp; Metadata Studio</h1>
                            <p className="q-card-subtitle">Compose search-ready metadata and structured HTML evaluated in real time for SEO and AI citability.</p>
                        </div>
                        <button type="button" onClick={applySample} className="q-btn q-btn-secondary">
                            Load GEO Sample
                        </button>
                    </div>

                    <div className="q-card-body">
                        <div className="q-field">
                            <div className="q-label-row">
                                <label htmlFor="quinrel-focus-keyword" className="q-label">Focus Keyword / Primary Entity</label>
                                <span className="q-counter">Target query</span>
                            </div>
                            <input
                                id="quinrel-focus-keyword"
                                type="text"
                                className="q-input"
                                value={focusKeyword}
                                onChange={(e) => setFocusKeyword(e.target.value)}
                                placeholder="e.g. Generative Engine Optimization"
                            />
                        </div>

                        <div className="q-field">
                            <div className="q-label-row">
                                <label htmlFor="quinrel-seo-title" className="q-label">SEO Title</label>
                                <span className="q-counter">{titleLen} / 60 chars (optimal 30–60)</span>
                            </div>
                            <input
                                id="quinrel-seo-title"
                                type="text"
                                className="q-input"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                placeholder="Write a clear, high-signal title..."
                            />
                            <div className="q-meter-track" aria-hidden="true">
                                <div className="q-meter-fill" style={{ width: `${titlePercent}%`, backgroundColor: titleColor }} />
                            </div>
                        </div>

                        <div className="q-field">
                            <div className="q-label-row">
                                <label htmlFor="quinrel-meta-description" className="q-label">Meta Description</label>
                                <span className="q-counter">{descLen} / 160 chars (optimal 120–160)</span>
                            </div>
                            <textarea
                                id="quinrel-meta-description"
                                className="q-textarea"
                                rows={3}
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Summarize the primary answer and value proposition..."
                            />
                            <div className="q-meter-track" aria-hidden="true">
                                <div className="q-meter-fill" style={{ width: `${descPercent}%`, backgroundColor: descColor }} />
                            </div>
                        </div>

                        <div className="q-field">
                            <div className="q-label-row">
                                <label htmlFor="quinrel-article-content" className="q-label">Article Body (Semantic HTML)</label>
                                <span className="q-counter">Supports &lt;h1&gt;–&lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;table&gt;</span>
                            </div>
                            <textarea
                                id="quinrel-article-content"
                                className="q-textarea q-mono"
                                rows={11}
                                value={content}
                                onChange={(e) => setContent(e.target.value)}
                                placeholder="<h1>Main Heading</h1><h2>What is...?</h2><p>Direct 40-word answer capsule...</p>"
                            />
                        </div>
                    </div>
                </section>

                <SearchPreview title={title} description={description} focusKeyword={focusKeyword} />
            </div>

            <aside className="q-stack" aria-label="Live Content Diagnostics">
                {analysisResult ? (
                    <>
                        <ReadabilityScore data={analysisResult.readability} isUpdating={isAnalyzing} />
                        <SeoChecklist data={analysisResult.seo} geo={analysisResult.geo} />
                    </>
                ) : (
                    <div className="q-card">
                        <div className="q-card-body" style={{ textAlign: 'center', padding: '2.5rem 1.5rem', color: 'var(--q-text-muted)', fontSize: '0.8125rem' }}>
                            {isAnalyzing ? 'Evaluating content signals…' : 'Enter a title, description, or body copy to inspect SEO & GEO telemetry.'}
                        </div>
                    </div>
                )}
            </aside>
        </div>
    );
}
