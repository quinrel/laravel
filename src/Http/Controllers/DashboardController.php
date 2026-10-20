<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Quinrel\Laravel\Services\AuditLogService;

class DashboardController
{
    public function index(): View
    {
        Gate::authorize('view-quinrel');

        /** @var view-string $viewName */
        $viewName = 'quinrel::app';

        return view($viewName);
    }

    public function saveSetup(Request $request, AuditLogService $auditLog): JsonResponse
    {
        Gate::authorize('view-quinrel');

        $validated = $request->validate([
            'siteName' => ['required', 'string', 'max:255'],
            'twitterHandle' => ['nullable', 'string', 'max:100'],
            'aiProvider' => ['required', 'string', 'in:ollama,openai,anthropic'],
        ]);

        Cache::forever('quinrel_setup_config', $validated);
        $auditLog->log('setup_wizard_completed', null, null, [], $validated);

        return response()->json([
            'status' => 'saved',
            'config' => $validated,
        ]);
    }
}
