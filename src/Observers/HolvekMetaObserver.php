<?php

declare(strict_types=1);

namespace Holvek\Laravel\Observers;

use Holvek\Laravel\Jobs\PingGoogleIndexingJob;
use Holvek\Laravel\Jobs\PingIndexNowJob;
use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Support\Facades\Cache;

class HolvekMetaObserver
{
    public function saved(HolvekMeta $meta): void
    {
        $this->clearCache($meta);
        $this->pingIndexNow($meta);
        $this->pingGoogleIndexing($meta, 'URL_UPDATED');
    }

    public function deleted(HolvekMeta $meta): void
    {
        $this->clearCache($meta);
        $this->pingIndexNow($meta);
        $this->pingGoogleIndexing($meta, 'URL_DELETED');
    }

    protected function clearCache(HolvekMeta $meta): void
    {
        $cacheKey = 'holvek_seo_'.$meta->model_type.'_'.$meta->model_id;
        Cache::forget($cacheKey);
    }

    protected function pingIndexNow(HolvekMeta $meta): void
    {
        if ($meta->canonical_url && config('holvek.indexnow.enabled')) {
            PingIndexNowJob::dispatch([$meta->canonical_url]);
        }
    }

    protected function pingGoogleIndexing(HolvekMeta $meta, string $type): void
    {
        if ($meta->canonical_url && config('holvek.google_indexing.enabled')) {
            PingGoogleIndexingJob::dispatch($meta->canonical_url, $type);
        }
    }
}
