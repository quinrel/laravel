import React from 'react';
import { createRoot } from 'react-dom/client';
import { SetupWizard } from './Components/SetupWizard';

const App = () => {
    return (
        <div className="min-h-screen bg-gray-50 text-gray-900 font-sans">
            <header className="bg-white shadow p-4">
                <h1 className="text-2xl font-bold text-blue-600">Holvek SEO</h1>
            </header>
            <main className="p-8">
                <SetupWizard />
            </main>
        </div>
    );
};

const root = createRoot(document.getElementById('holvek-app'));
root.render(<App />);
