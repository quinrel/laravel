<?php

declare(strict_types=1);

namespace Holvek\Laravel\Http\Controllers;

use Holvek\Laravel\Models\HolvekRumMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HolvekRumController extends Controller
{
    /**
     * Handle incoming Core Web Vitals telemetry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:255'],
            'metric_name' => ['required', 'string', 'in:LCP,INP,CLS,TTFB,FCP'],
            'metric_value' => ['required', 'numeric'],
            'rating' => ['nullable', 'string', 'in:good,needs-improvement,poor'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ]);

        // Strip query string from URL for aggregation purposes if desired,
        // but typically keeping exact canonical URL is fine.
        $url = strtok($validated['url'], '?');

        HolvekRumMetric::create([
            'url' => $url,
            'metric_name' => $validated['metric_name'],
            'metric_value' => $validated['metric_value'],
            'rating' => $validated['rating'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
        ]);

        return response()->json(['status' => 'success'], 202);
    }
}
