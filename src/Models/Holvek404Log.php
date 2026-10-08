<?php

declare(strict_types=1);

namespace Holvek\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $path
 * @property int $hits
 * @property string|null $last_referrer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Holvek404Log extends Model
{
    protected $table = 'holvek_404_logs';

    protected $fillable = [
        'path',
        'hits',
        'last_referrer',
    ];

    protected $casts = [
        'hits' => 'integer',
    ];
}
