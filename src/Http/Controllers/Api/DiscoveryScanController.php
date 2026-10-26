<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\RobotsTxtManager;
use Quinrel\Laravel\Services\Technical\TechnicalSeoAuditor;

class DiscoveryScanController
{
    public function scan(Request $request, TechnicalSeoAuditor $auditor, RobotsTxtManager $robotsManager): JsonResponse
    {
        $configuredToken = (string) config('quinrel.api.cloud_key', '');
        $providedToken = (string) ($request->bearerToken() ?: $request->header('X-Quinrel-Token', ''));

        $hasValidToken = $configuredToken !== '' && $providedToken !== '' && hash_equals($configuredToken, $providedToken);
        if (! $hasValidToken) {
            Gate::authorize('view-quinrel');
        }

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
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        $aiBotAudit = $robotsManager->auditAiBots($path);

        return response()->json([
            'url' => $url,
            'technical_score' => $score,
            'checks' => $checks,
            'ai_mentions' => [
                'llms_txt_enabled' => (bool) config('quinrel.llms_txt.enabled', true),
                'markdown_negotiation_enabled' => (bool) config('quinrel.llms_txt.advertise_markdown_alternate', true),
                'indexable' => $isIndexable,
                'search_bots_allowed' => $aiBotAudit['all_search_bots_allowed'],
                'training_bots_blocked' => $aiBotAudit['training_bots_blocked'],
                'bots' => $aiBotAudit['search_bots'],
            ],
            'recommendations' => $recommendations,
        ]);
    }
}
