<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Quinrel\Laravel\Services\Analysis\ContentAnalyzer;

class AnalyzeContentController
{
    public function __invoke(Request $request, ContentAnalyzer $analyzer): JsonResponse
    {
        Gate::authorize('view-quinrel');

        $validated = $request->validate([
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'focus_keyword' => ['nullable', 'string'],
        ]);

        $result = $analyzer->analyze(
            $validated['title'] ?? '',
            $validated['description'] ?? '',
            $validated['content'] ?? '',
            $validated['focus_keyword'] ?? ''
        );

        return response()->json($result);
    }
}
