<?php

declare(strict_types=1);

namespace Holvek\Laravel\Models;

use Carbon\Carbon;
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
class HolvekRumMetric extends Model
{
    protected $table = 'holvek_rum_metrics';

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
}
