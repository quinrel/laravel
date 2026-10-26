<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event
 * @property int|null $user_id
 * @property string|null $model_type
 * @property int|null $model_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuinrelAuditLog extends Model
{
    use MassPrunable;

    protected $table = 'quinrel_audit_logs';

    protected $fillable = [
        'event',
        'user_id',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'model_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = (int) config('quinrel.audit_log.retention_days', 90);

        return static::where('created_at', '<=', now()->subDays(max(1, $days)));
    }
}
