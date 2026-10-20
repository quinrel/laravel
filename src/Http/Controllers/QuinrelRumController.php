<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Quinrel\Laravel\Models\QuinrelRumMetric;

class QuinrelRumController extends Controller
{
    /**
     * Handle incoming Core Web Vitals telemetry (supports both single and batched payloads).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('metrics') && is_array($request->input('metrics'))) {
            $validated = $request->validate([
                'url' => ['required', 'string', 'max:255'],
                'session_id' => ['nullable', 'string', 'max:255'],
                'metrics' => ['required', 'array', 'min:1', 'max:10'],
                'metrics.*.metric_name' => ['required', 'string', 'in:LCP,INP,CLS,TTFB,FCP'],
                'metrics.*.metric_value' => ['required', 'numeric'],
                'metrics.*.rating' => ['nullable', 'string', 'in:good,needs-improvement,poor'],
            ]);

            $url = (string) strtok($validated['url'], '?');
            $sessionId = $validated['session_id'] ?? null;
            $now = now();
            $rows = [];

            foreach ($validated['metrics'] as $metric) {
                $rows[] = [
                    'url' => $url,
                    'metric_name' => $metric['metric_name'],
                    'metric_value' => (float) $metric['metric_value'],
                    'rating' => $metric['rating'] ?? null,
                    'session_id' => $sessionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            QuinrelRumMetric::insert($rows);

            return response()->json(['status' => 'success', 'recorded' => count($rows)], 202);
        }

        $validated = $request->validate([
            'url' => ['required', 'string', 'max:255'],
            'metric_name' => ['required', 'string', 'in:LCP,INP,CLS,TTFB,FCP'],
            'metric_value' => ['required', 'numeric'],
            'rating' => ['nullable', 'string', 'in:good,needs-improvement,poor'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ]);

        $url = (string) strtok($validated['url'], '?');

        QuinrelRumMetric::create([
            'url' => $url,
            'metric_name' => $validated['metric_name'],
            'metric_value' => (float) $validated['metric_value'],
            'rating' => $validated['rating'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
        ]);

        return response()->json(['status' => 'success'], 202);
    }
}
