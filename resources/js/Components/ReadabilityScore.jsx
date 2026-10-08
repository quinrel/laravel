import React from 'react';

export default function ReadabilityScore({ data }) {
    if (!data) return null;

    let scoreColor = "text-gray-600";
    if (data.score >= 60) scoreColor = "text-green-600";
    else if (data.score >= 50) scoreColor = "text-yellow-600";
    else if (data.score > 0) scoreColor = "text-red-600";

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4 text-gray-800">Readability</h2>
            
            <div className="flex items-center mb-4">
                <div className={`text-4xl font-bold mr-4 ${scoreColor}`}>
                    {data.score}
                </div>
                <div>
                    <div className="font-semibold text-gray-800">{data.level}</div>
                    <div className="text-xs text-gray-500">Flesch Reading Ease</div>
                </div>
            </div>

            <p className="text-sm text-gray-600 mb-4">{data.message}</p>

            <div className="grid grid-cols-3 gap-2 text-center text-xs text-gray-500">
                <div className="bg-gray-50 p-2 rounded">
                    <div className="font-semibold text-gray-700">{data.stats?.words || 0}</div>
                    <div>Words</div>
                </div>
                <div className="bg-gray-50 p-2 rounded">
                    <div className="font-semibold text-gray-700">{data.stats?.sentences || 0}</div>
                    <div>Sentences</div>
                </div>
                <div className="bg-gray-50 p-2 rounded">
                    <div className="font-semibold text-gray-700">{data.stats?.syllables || 0}</div>
                    <div>Syllables</div>
                </div>
            </div>
        </div>
    );
}
