<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Models;

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
}
