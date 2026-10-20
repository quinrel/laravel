<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(string $event, ?string $modelType = null, ?int $modelId = null, array $oldValues = [], array $newValues = []): void
    {
        DB::table('quinrel_audit_logs')->insert([
            'event' => $event,
            'user_id' => Auth::id(),
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
