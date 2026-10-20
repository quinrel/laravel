<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Technical\TechnicalSeoAuditor;

class DiscoveryScanController
{
    public function scan(Request $request, TechnicalSeoAuditor $auditor): JsonResponse
    {
        $url = $request->query('url');

        if (! is_string($url) || trim($url) === '') {
            return response()->json(['error' => 'URL is required'], 400);
        }

        $checks = $auditor->auditUrl($url);
        $score = 0;
        $recommendations = [];

        foreach ($checks as $check) {
            if ($check['status'] === 'success') {
                $score += 25;
            } elseif ($check['status'] === 'warning') {
                $score += 15;
                $recommendations[] = $check['message'];
            } else {
                $recommendations[] = $check['message'];
            }
        }

        $meta = QuinrelMeta::where('canonical_url', $url)->first();
        $isIndexable = $meta === null || ! str_contains((string) $meta->robots, 'noindex');

        return response()->json([
            'url' => $url,
            'technical_score' => $score,
            'checks' => $checks,
            'ai_mentions' => [
                'llms_txt_enabled' => (bool) config('quinrel.llms_txt.enabled', true),
                'indexable' => $isIndexable,
            ],
            'recommendations' => $recommendations,
        ]);
    }
}
