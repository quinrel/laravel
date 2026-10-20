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
            const response = await fetch('/quinrel/api/setup', {
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
        <div className="max-w-2xl mx-auto mt-10 p-6 bg-white rounded shadow">
            <h2 className="text-xl font-semibold mb-4">Quinrel Setup Wizard - Step {step} of 3</h2>

            {savedMessage && (
                <div className="p-2 mb-4 text-sm text-blue-600">{savedMessage}</div>
            )}

            {step === 1 && (
                <div>
                    <h3 className="font-medium mb-2">Site Profile</h3>
                    <select
                        value={siteName}
                        onChange={(e) => setSiteName(e.target.value)}
                        className="border p-2 w-full rounded"
                    >
                        <option value="Blog / Publisher">Blog / Publisher</option>
                        <option value="E-commerce">E-commerce</option>
                        <option value="Corporate">Corporate</option>
                        <option value="Local Business">Local Business</option>
                    </select>
                </div>
            )}

            {step === 2 && (
                <div>
                    <h3 className="font-medium mb-2">Social Handle</h3>
                    <input
                        type="text"
                        value={twitterHandle}
                        onChange={(e) => setTwitterHandle(e.target.value)}
                        placeholder="@yourhandle"
                        className="border p-2 w-full rounded mb-2"
                    />
                </div>
            )}

            {step === 3 && (
                <div>
                    <h3 className="font-medium mb-2">AI Writing Provider</h3>
                    <select
                        value={aiProvider}
                        onChange={(e) => setAiProvider(e.target.value)}
                        className="border p-2 w-full rounded"
                    >
                        <option value="ollama">Local Ollama</option>
                        <option value="openai">OpenAI (GPT-4o)</option>
                        <option value="anthropic">Anthropic (Claude 3.5 Sonnet)</option>
                    </select>
                </div>
            )}

            <div className="mt-6 flex justify-between">
                <button
                    type="button"
                    disabled={step === 1 || saving}
                    onClick={() => setStep(step - 1)}
                    className="px-4 py-2 bg-gray-200 rounded"
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
                    className="px-4 py-2 bg-blue-600 text-white rounded"
                >
                    {step === 3 ? (saving ? 'Saving...' : 'Finish') : 'Next'}
                </button>
            </div>
        </div>
    );
};
