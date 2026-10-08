<?php

declare(strict_types=1);

namespace Holvek\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $keyword
 * @property string|null $url
 * @property int|null $rank
 * @property string $search_engine
 * @property string|null $location
 * @property Carbon $tracked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HolvekKeywordRanking extends Model
{
    protected $table = 'holvek_keyword_rankings';

    protected $fillable = [
        'keyword',
        'url',
        'rank',
        'search_engine',
        'location',
        'tracked_at',
    ];

    protected $casts = [
        'rank' => 'integer',
        'tracked_at' => 'date',
    ];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForDays(Builder $query, int $days): Builder
    {
        return $query->where('tracked_at', '>=', now()->subDays($days));
    }
}
