<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Quinrel\Laravel\Services\AuditLogService;
use Quinrel\Laravel\Services\Cloud\QuinrelCloudClient;

class AuditWebhookController
{
    public function __invoke(Request $request, QuinrelCloudClient $client, AuditLogService $auditLog): JsonResponse
    {
        $signature = (string) $request->header('X-Quinrel-Signature', '');
        $payload = $request->getContent();

        if (! $client->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        /** @var array<string, mixed> $data */
        $data = $request->json()->all();
        $auditLog->log('cloud_audit_completed', null, null, [], $data);

        return response()->json(['status' => 'accepted']);
    }
}
