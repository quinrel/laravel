<?php

declare(strict_types=1);

namespace Holvek\Laravel\Http\Controllers\Api;

use Holvek\Laravel\Services\Analysis\ContentAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyzeContentController
{
    public function __invoke(Request $request, ContentAnalyzer $analyzer): JsonResponse
    {
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
