import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { SetupWizard } from './Components/SetupWizard';
import ContentEditor from './Components/ContentEditor';
import DiagnosticsPanel from './Components/DiagnosticsPanel';

const App = () => {
    const [activeTab, setActiveTab] = useState('editor');

    return (
        <div className="q-shell">
            <header className="q-topbar">
                <div className="q-topbar-inner">
                    <div className="q-brand">
                        <span className="q-brand-mark" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                                <path d="M11 8v6" />
                                <path d="M8 11h6" />
                            </svg>
                        </span>
                        <span className="q-brand-title">Quinrel</span>
                        <span className="q-version-pill">SEO &amp; GEO v1.2</span>
                    </div>

                    <nav aria-label="Primary" className="q-nav">
                        <button
                            type="button"
                            onClick={() => setActiveTab('editor')}
                            aria-current={activeTab === 'editor' ? 'page' : undefined}
                            className="q-nav-btn"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                            Content Studio
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('diagnostics')}
                            aria-current={activeTab === 'diagnostics' ? 'page' : undefined}
                            className="q-nav-btn"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                            </svg>
                            Diagnostics &amp; Crawl
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('setup')}
                            aria-current={activeTab === 'setup' ? 'page' : undefined}
                            className="q-nav-btn"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="3" />
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
                            </svg>
                            Configuration
                        </button>
                    </nav>
                </div>
            </header>

            <main id="quinrel-main" className="q-main" tabIndex={-1}>
                {activeTab === 'editor' && <ContentEditor />}
                {activeTab === 'diagnostics' && <DiagnosticsPanel />}
                {activeTab === 'setup' && <SetupWizard onComplete={() => setActiveTab('editor')} />}
            </main>
        </div>
    );
};

const container = document.getElementById('quinrel-app');
if (container) {
    const root = createRoot(container);
    root.render(<App />);
}
