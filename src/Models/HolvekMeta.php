<?php

declare(strict_types=1);

namespace Holvek\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|string $model_id
 * @property string $model_type
 * @property string|null $title
 * @property string|null $description
 * @property string|null $canonical_url
 * @property string|null $robots
 * @property string|null $og_image
 * @property array<string, mixed>|null $social_overrides
 * @property string|null $target_keyword
 * @property array<string, mixed>|null $custom_schema
 * @property bool $is_cornerstone
 * @property Carbon|null $last_reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HolvekMeta extends Model
{
    protected $table = 'holvek_meta';

    protected $fillable = [
        'title',
        'description',
        'canonical_url',
        'robots',
        'og_image',
        'social_overrides',
        'target_keyword',
        'is_cornerstone',
        'last_reviewed_at',
        'custom_schema',
    ];

    protected $casts = [
        'social_overrides' => 'array',
        'custom_schema' => 'array',
        'is_cornerstone' => 'boolean',
        'last_reviewed_at' => 'datetime',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStaleCornerstones($query)
    {
        return $query->where('is_cornerstone', true)
            ->where(function ($q) {
                $q->whereNull('last_reviewed_at')
                    ->orWhere('last_reviewed_at', '<', now()->subDays(180));
            });
    }
}
