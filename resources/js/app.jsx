import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { SetupWizard } from './Components/SetupWizard';
import ContentEditor from './Components/ContentEditor';

const App = () => {
    const [activeTab, setActiveTab] = useState('editor');

    return (
        <div className="min-h-screen bg-gray-50 text-gray-900 font-sans">
            <header className="bg-white shadow p-4 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-blue-600">Quinrel SEO</h1>
                <nav className="flex gap-3">
                    <button
                        type="button"
                        onClick={() => setActiveTab('editor')}
                        className={`px-4 py-2 rounded ${activeTab === 'editor' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Content Analyzer
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('setup')}
                        className={`px-4 py-2 rounded ${activeTab === 'setup' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Setup Wizard
                    </button>
                </nav>
            </header>
            <main className="p-8">
                {activeTab === 'editor' ? (
                    <ContentEditor />
                ) : (
                    <SetupWizard onComplete={() => setActiveTab('editor')} />
                )}
            </main>
        </div>
    );
};

const container = document.getElementById('quinrel-app');
if (container) {
    const root = createRoot(container);
    root.render(<App />);
}
