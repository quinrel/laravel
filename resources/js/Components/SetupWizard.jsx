import React, { useState } from 'react';

export const SetupWizard = () => {
    const [step, setStep] = useState(1);
    
    return (
        <div className="max-w-2xl mx-auto mt-10 p-6 bg-white rounded shadow">
            <h2 className="text-xl font-semibold mb-4">Holvek Setup Wizard - Step {step} of 3</h2>
            
            {step === 1 && (
                <div>
                    <h3 className="font-medium mb-2">Site Type</h3>
                    <select className="border p-2 w-full rounded">
                        <option>Blog / Publisher</option>
                        <option>E-commerce</option>
                        <option>Corporate</option>
                        <option>Local Business</option>
                    </select>
                </div>
            )}

            {step === 2 && (
                <div>
                    <h3 className="font-medium mb-2">Social Links</h3>
                    <input type="text" placeholder="Twitter URL" className="border p-2 w-full rounded mb-2" />
                    <input type="text" placeholder="Facebook URL" className="border p-2 w-full rounded" />
                </div>
            )}

            {step === 3 && (
                <div>
                    <h3 className="font-medium mb-2">Defaults</h3>
                    <label className="flex items-center space-x-2">
                        <input type="checkbox" defaultChecked />
                        <span>Enable generic fallback image for OpenGraph</span>
                    </label>
                </div>
            )}

            <div className="mt-6 flex justify-between">
                <button 
                    disabled={step === 1} 
                    onClick={() => setStep(step - 1)}
                    className="px-4 py-2 bg-gray-200 rounded disabled:opacity-50">Back</button>
                <button 
                    onClick={() => {
                        if (step < 3) setStep(step + 1);
                        else alert('Setup Complete!');
                    }}
                    className="px-4 py-2 bg-blue-600 text-white rounded">
                    {step === 3 ? 'Finish' : 'Next'}
                </button>
            </div>
        </div>
    );
};
