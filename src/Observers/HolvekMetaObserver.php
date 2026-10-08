<?php

declare(strict_types=1);

namespace Holvek\Laravel\Observers;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Support\Facades\Cache;

class HolvekMetaObserver
{
    public function saved(HolvekMeta $meta): void
    {
        $this->clearCache($meta);
    }

    public function deleted(HolvekMeta $meta): void
    {
        $this->clearCache($meta);
    }

    protected function clearCache(HolvekMeta $meta): void
    {
        $cacheKey = 'holvek_seo_'.$meta->model_type.'_'.$meta->model_id;
        Cache::forget($cacheKey);
    }
}
