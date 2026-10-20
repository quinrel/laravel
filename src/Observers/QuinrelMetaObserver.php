<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Observers;

use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Jobs\PingGoogleIndexingJob;
use Quinrel\Laravel\Jobs\PingIndexNowJob;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\AuditLogService;

class QuinrelMetaObserver
{
    public function saved(QuinrelMeta $meta): void
    {
        $this->clearCache($meta);
        $this->pingIndexNow($meta);
        $this->pingGoogleIndexing($meta, 'URL_UPDATED');

        if (config('quinrel.audit_log.enabled', true)) {
            $event = $meta->wasRecentlyCreated ? 'meta.created' : 'meta.updated';
            app(AuditLogService::class)->log(
                $event,
                $meta->model_type ?: QuinrelMeta::class,
                is_numeric($meta->model_id) ? (int) $meta->model_id : $meta->id,
                $meta->wasRecentlyCreated ? [] : $meta->getOriginal(),
                $meta->getAttributes()
            );
        }
    }

    public function deleted(QuinrelMeta $meta): void
    {
        $this->clearCache($meta);
        $this->pingIndexNow($meta);
        $this->pingGoogleIndexing($meta, 'URL_DELETED');

        if (config('quinrel.audit_log.enabled', true)) {
            app(AuditLogService::class)->log(
                'meta.deleted',
                $meta->model_type ?: QuinrelMeta::class,
                is_numeric($meta->model_id) ? (int) $meta->model_id : $meta->id,
                $meta->getOriginal(),
                []
            );
        }
    }

    protected function clearCache(QuinrelMeta $meta): void
    {
        $cacheKey = 'quinrel_seo_'.$meta->model_type.'_'.$meta->model_id;
        Cache::forget($cacheKey);
        Cache::forget($cacheKey.'_nonprod');
    }

    protected function pingIndexNow(QuinrelMeta $meta): void
    {
        if ($meta->canonical_url && config('quinrel.indexnow.enabled')) {
            PingIndexNowJob::dispatch([$meta->canonical_url]);
        }
    }

    protected function pingGoogleIndexing(QuinrelMeta $meta, string $type): void
    {
        if ($meta->canonical_url && config('quinrel.google_indexing.enabled')) {
            PingGoogleIndexingJob::dispatch($meta->canonical_url, $type);
        }
    }
}
