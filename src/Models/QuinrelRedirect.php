<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Services\AuditLogService;

/**
 * @property int $id
 * @property string $source_url
 * @property string $target_url
 * @property int $status_code
 * @property bool $is_regex
 * @property bool $is_active
 * @property int $hits
 * @property bool $is_chain
 * @property bool $is_loop
 * @property array<int, string>|null $chain_details
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuinrelRedirect extends Model
{
    protected $table = 'quinrel_redirects';

    protected $fillable = [
        'source_url',
        'target_url',
        'status_code',
        'is_regex',
        'is_active',
        'hits',
        'is_chain',
        'is_loop',
        'chain_details',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_regex' => 'boolean',
        'is_active' => 'boolean',
        'hits' => 'integer',
        'is_chain' => 'boolean',
        'is_loop' => 'boolean',
        'chain_details' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $redirect): void {
            Cache::forget('quinrel_redirects_map');

            if (config('quinrel.audit_log.enabled', true) && ! $redirect->wasChanged('hits')) {
                $event = $redirect->wasRecentlyCreated ? 'redirect.created' : 'redirect.updated';
                app(AuditLogService::class)->log(
                    $event,
                    self::class,
                    $redirect->id,
                    $redirect->wasRecentlyCreated ? [] : $redirect->getOriginal(),
                    $redirect->getAttributes()
                );
            }
        });

        static::deleted(function (self $redirect): void {
            Cache::forget('quinrel_redirects_map');

            if (config('quinrel.audit_log.enabled', true)) {
                app(AuditLogService::class)->log(
                    'redirect.deleted',
                    self::class,
                    $redirect->id,
                    $redirect->getOriginal(),
                    []
                );
            }
        });
    }
}
