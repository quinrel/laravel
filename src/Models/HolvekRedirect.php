<?php

declare(strict_types=1);

namespace Holvek\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $source_url
 * @property string $target_url
 * @property int $status_code
 * @property bool $is_regex
 * @property bool $is_active
 * @property int $hits
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HolvekRedirect extends Model
{
    protected $table = 'holvek_redirects';

    protected $fillable = [
        'source_url',
        'target_url',
        'status_code',
        'is_regex',
        'is_active',
        'hits',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_regex' => 'boolean',
        'is_active' => 'boolean',
        'hits' => 'integer',
    ];
}
