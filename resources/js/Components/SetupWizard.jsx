import React, { useState } from 'react';

export const SetupWizard = ({ onComplete }) => {
    const [step, setStep] = useState(1);
    const [siteName, setSiteName] = useState('Blog / Publisher');
    const [twitterHandle, setTwitterHandle] = useState('');
    const [aiProvider, setAiProvider] = useState('ollama');
    const [saving, setSaving] = useState(false);
    const [savedMessage, setSavedMessage] = useState('');

    const handleFinish = async () => {
        setSaving(true);
        setSavedMessage('');
        try {
            const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content || '';
            const basePath = document.querySelector('meta[name="quinrel-base-path"]')?.getAttribute('content') || '/quinrel';
            const response = await fetch(`${basePath}/api/setup`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    siteName,
                    twitterHandle,
                    aiProvider,
                }),
            });

            if (response.ok) {
                setSavedMessage('Configuration saved successfully.');
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }
        } catch (err) {
            console.error('Failed to save setup:', err);
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="q-wizard-wrap">
            <ol className="q-stepper" role="list" aria-label="Setup progress">
                <li className="q-step-pill" data-active={step === 1} data-done={step > 1}>
                    <span className="q-mono">01</span>
                    <span>Site Profile</span>
                </li>
                <li className="q-step-pill" data-active={step === 2} data-done={step > 2}>
                    <span className="q-mono">02</span>
                    <span>Social Identity</span>
                </li>
                <li className="q-step-pill" data-active={step === 3} data-done={false}>
                    <span className="q-mono">03</span>
                    <span>AI Engine</span>
                </li>
            </ol>

            <section className="q-card" aria-labelledby="setup-wizard-heading">
                <div className="q-card-header">
                    <div>
                        <h1 id="setup-wizard-heading" className="q-card-title">
                            System Configuration — Step {step} of 3
                        </h1>
                        <p className="q-card-subtitle">
                            Configure default schema entities, OpenGraph attribution, and your preferred generative AI provider.
                        </p>
                    </div>
                </div>

                <div className="q-card-body">
                    {savedMessage && (
                        <div className="q-alert q-alert-success" style={{ marginBottom: '1rem' }} role="status">
                            {savedMessage}
                        </div>
                    )}

                    {step === 1 && (
                        <div className="q-field">
                            <label htmlFor="quinrel-site-profile" className="q-label">Primary Site Architecture</label>
                            <select
                                id="quinrel-site-profile"
                                value={siteName}
                                onChange={(e) => setSiteName(e.target.value)}
                                className="q-select"
                            >
                                <option value="Blog / Publisher">Blog / Publisher (Article &amp; News Schema)</option>
                                <option value="E-commerce">E-commerce (Product &amp; Offer Schema)</option>
                                <option value="Corporate">Corporate / SaaS (Organization &amp; WebSite Schema)</option>
                                <option value="Local Business">Local Business (LocalBusiness &amp; GeoCoordinates)</option>
                            </select>
                        </div>
                    )}

                    {step === 2 && (
                        <div className="q-field">
                            <label htmlFor="quinrel-twitter-handle" className="q-label">Default X (Twitter) Attribution Handle</label>
                            <input
                                id="quinrel-twitter-handle"
                                type="text"
                                value={twitterHandle}
                                onChange={(e) => setTwitterHandle(e.target.value)}
                                placeholder="@yourbrand"
                                className="q-input"
                            />
                        </div>
                    )}

                    {step === 3 && (
                        <div className="q-field">
                            <label htmlFor="quinrel-ai-provider" className="q-label">Generative AI Provider for Metadata &amp; Alt-Text Autofix</label>
                            <select
                                id="quinrel-ai-provider"
                                value={aiProvider}
                                onChange={(e) => setAiProvider(e.target.value)}
                                className="q-select"
                            >
                                <option value="ollama">Local Ollama (Zero-Cloud / On-Device Privacy)</option>
                                <option value="openai">OpenAI (GPT-4o)</option>
                                <option value="anthropic">Anthropic (Claude 3.5 Sonnet)</option>
                            </select>
                        </div>
                    )}

                    <div style={{ marginTop: '1.5rem', paddingTop: '1rem', borderTop: '1px solid var(--q-border)', display: 'flex', justifyContent: 'space-between' }}>
                        <button
                            type="button"
                            disabled={step === 1 || saving}
                            onClick={() => setStep(step - 1)}
                            className="q-btn q-btn-secondary"
                        >
                            Back
                        </button>
                        <button
                            type="button"
                            disabled={saving}
                            onClick={() => {
                                if (step < 3) setStep(step + 1);
                                else handleFinish();
                            }}
                            className="q-btn q-btn-primary"
                        >
                            {step === 3 ? (saving ? 'Saving…' : 'Save Configuration') : 'Continue'}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    );
};
