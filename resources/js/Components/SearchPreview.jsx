import React, { useState } from 'react';

export default function SearchPreview({ title, description, url = "https://example.com/your-page" }) {
    const [view, setView] = useState('desktop');

    const displayTitle = title || "SEO Title - Your Website Name";
    const displayDescription = description || "This is a preview of how your page will look in Google search results. Write a compelling description to improve click-through rates.";

    return (
        <div>
            <div className="flex space-x-2 mb-4">
                <button 
                    onClick={() => setView('desktop')}
                    className={`px-3 py-1 text-xs rounded-full ${view === 'desktop' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'}`}
                >
                    Desktop
                </button>
                <button 
                    onClick={() => setView('mobile')}
                    className={`px-3 py-1 text-xs rounded-full ${view === 'mobile' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'}`}
                >
                    Mobile
                </button>
            </div>

            <div className={`border border-gray-200 rounded p-4 bg-white ${view === 'mobile' ? 'max-w-[375px]' : 'max-w-[600px]'}`}>
                <div className="flex items-center text-sm text-gray-700 mb-1">
                    <span className="truncate">{url}</span>
                    <span className="ml-1 text-gray-500">▼</span>
                </div>
                <div className="text-xl text-[#1a0dab] hover:underline cursor-pointer truncate">
                    {displayTitle}
                </div>
                <div className={`text-sm text-[#4d5156] mt-1 ${view === 'mobile' ? 'line-clamp-3' : 'line-clamp-2'}`}>
                    {displayDescription}
                </div>
            </div>
        </div>
    );
}
