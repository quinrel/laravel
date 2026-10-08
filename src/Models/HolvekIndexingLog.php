<?php

declare(strict_types=1);

namespace Holvek\Laravel\Models;

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
class HolvekIndexingLog extends Model
{
    protected $table = 'holvek_indexing_logs';

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
}
