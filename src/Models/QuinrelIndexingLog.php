<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $url
 * @property string $engine
 * @property int|null $status_code
 * @property array<string, mixed>|null $response
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuinrelIndexingLog extends Model
{
    use MassPrunable;

    protected $table = 'quinrel_indexing_logs';

    protected $fillable = [
        'url',
        'engine',
        'status_code',
        'response',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response' => 'array',
    ];

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = (int) config('quinrel.indexnow.retention_days', 30);

        return static::where('created_at', '<=', now()->subDays(max(1, $days)));
    }
}
