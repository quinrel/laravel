<?php

declare(strict_types=1);

namespace Holvek\Laravel\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryScanController
{
    public function scan(Request $request): JsonResponse
    {
        $url = $request->query('url');

        if (! $url || ! is_string($url)) {
            return response()->json(['error' => 'URL is required'], 400);
        }

        // Mock discovery scan response
        return response()->json([
            'url' => $url,
            'technical_score' => 85,
            'ai_mentions' => [
                'chatgpt' => true,
                'perplexity' => false,
            ],
            'recommendations' => [
                'Add missing alt tags to 3 images.',
                'Improve LCP score on mobile.',
            ],
        ]);
    }
}
