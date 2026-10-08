import React from 'react';

export default function SeoChecklist({ data }) {
    if (!data) return null;

    const renderItem = (item, label) => {
        let iconClass = "text-gray-400";
        let icon = "⚪";
        
        if (item.status === 'success') {
            iconClass = "text-green-500";
            icon = "✅";
        } else if (item.status === 'warning') {
            iconClass = "text-yellow-500";
            icon = "⚠️";
        } else if (item.status === 'error') {
            iconClass = "text-red-500";
            icon = "❌";
        }

        return (
            <li className="flex items-start mb-2">
                <span className={`mr-2 ${iconClass}`}>{icon}</span>
                <div>
                    <div className="font-medium text-sm text-gray-800">{label}</div>
                    <div className="text-xs text-gray-500">{item.message}</div>
                </div>
            </li>
        );
    };

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4 text-gray-800">SEO Checklist</h2>
            <ul>
                {renderItem(data.title_length, 'Title Length')}
                {renderItem(data.description_length, 'Description Length')}
                {renderItem(data.keyword_in_title, 'Keyword in Title')}
                {renderItem(data.keyword_in_description, 'Keyword in Description')}
                {renderItem(data.keyword_in_content, 'Keyword in Content')}
                {renderItem(data.headings_check, 'Headings (H1/H2)')}
                {renderItem(data.alt_text_check, 'Image Alt Attributes')}
            </ul>
        </div>
    );
}
