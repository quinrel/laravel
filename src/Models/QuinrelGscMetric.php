<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $url
 * @property Carbon $date
 * @property int $clicks
 * @property int $impressions
 * @property float $ctr
 * @property float $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuinrelGscMetric extends Model
{
    protected $table = 'quinrel_gsc_metrics';

    protected $fillable = [
        'url',
        'date',
        'clicks',
        'impressions',
        'ctr',
        'position',
    ];

    protected $casts = [
        'date' => 'date',
        'clicks' => 'integer',
        'impressions' => 'integer',
        'ctr' => 'float',
        'position' => 'float',
    ];
}
