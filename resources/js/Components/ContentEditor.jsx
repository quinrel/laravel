import React, { useState, useEffect } from 'react';
import SeoChecklist from './SeoChecklist';
import ReadabilityScore from './ReadabilityScore';
import SearchPreview from './SearchPreview';

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
                // Determine the base path from config or assume /quinrel/api/analyze
                // In a real implementation we would pass the base URL as a prop from Blade
                const response = await fetch('/quinrel/api/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        title,
                        description,
                        content,
                        focus_keyword: focusKeyword
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    setAnalysisResult(data);
                }
            } catch (error) {
                console.error("Error analyzing content:", error);
            } finally {
                setIsAnalyzing(false);
            }
        };

        const timeoutId = setTimeout(() => {
            analyze();
        }, 800); // Debounce

        return () => clearTimeout(timeoutId);
    }, [title, description, content, focusKeyword]);

    return (
        <div className="flex flex-col md:flex-row gap-6 p-6">
            <div className="w-full md:w-2/3 space-y-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold mb-4">Content Editor</h2>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Focus Keyword</label>
                            <input 
                                type="text"
                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                value={focusKeyword}
                                onChange={(e) => setFocusKeyword(e.target.value)}
                                placeholder="e.g. best coffee beans"
                            />
                        </div>
                        
                        <div>
                            <label className="block text-sm font-medium text-gray-700">SEO Title</label>
                            <input 
                                type="text"
                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                placeholder="Write a compelling title..."
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Meta Description</label>
                            <textarea 
                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                rows="3"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Write a compelling meta description..."
                            ></textarea>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Article Content (HTML)</label>
                            <textarea 
                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm font-mono text-sm"
                                rows="12"
                                value={content}
                                onChange={(e) => setContent(e.target.value)}
                                placeholder="<h1>Main Heading</h1><p>Start writing your content here...</p>"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold mb-4">Search Preview</h2>
                    <SearchPreview title={title} description={description} />
                </div>
            </div>

            <div className="w-full md:w-1/3 space-y-6">
                {isAnalyzing ? (
                    <div className="text-gray-500 flex justify-center py-10">Analyzing...</div>
                ) : analysisResult ? (
                    <>
                        <ReadabilityScore data={analysisResult.readability} />
                        <SeoChecklist data={analysisResult.seo} />
                    </>
                ) : (
                    <div className="text-gray-500 text-sm">Start typing to see analysis.</div>
                )}
            </div>
        </div>
    );
}
