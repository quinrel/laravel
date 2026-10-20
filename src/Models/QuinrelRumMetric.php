<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $url
 * @property string $metric_name
 * @property float $metric_value
 * @property string|null $rating
 * @property string|null $session_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuinrelRumMetric extends Model
{
    use MassPrunable;

    protected $table = 'quinrel_rum_metrics';

    protected $fillable = [
        'url',
        'metric_name',
        'metric_value',
        'rating',
        'session_id',
    ];

    protected $casts = [
        'metric_value' => 'float',
    ];

    /**
     * Get the prunable model query.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = (int) config('quinrel.rum.retention_days', 30);

        return static::where('created_at', '<=', now()->subDays(max(1, $days)));
    }
}
