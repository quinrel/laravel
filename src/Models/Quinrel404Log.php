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
 * @property string|null $referrer
 * @property int $hits
 * @property Carbon|null $last_hit_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Quinrel404Log extends Model
{
    use MassPrunable;

    protected $table = 'quinrel_404_logs';

    protected $fillable = [
        'url',
        'referrer',
        'hits',
        'last_hit_at',
    ];

    protected $casts = [
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = (int) config('quinrel.redirects.retention_days', 90);

        return static::where('updated_at', '<=', now()->subDays(max(1, $days)));
    }
}
