<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class HeadRenderer
{
    public function render(?object $model = null): string
    {
        // Environment-Aware Profiles: non-production env injects noindex, nofollow
        if (! App::environment('production')) {
            return '<meta name="robots" content="noindex, nofollow">';
        }

        $cacheKey = $this->getCacheKey($model);

        return Cache::rememberForever($cacheKey, function () use ($model) {
            return $this->generateHtml($model);
        });
    }

    protected function getCacheKey(?object $model): string
    {
        if ($model && method_exists($model, 'getKey')) {
            return 'holvek_seo_'.get_class($model).'_'.$model->getKey();
        }

        return 'holvek_seo_global_'.request()->path();
    }

    protected function generateHtml(?object $model): string
    {
        $title = config('app.name');
        $description = '';
        $robots = 'index, follow';
        $canonical = null;
        $hreflangs = [];

        if ($model && method_exists($model, 'getSeoTitle')) {
            $title = $model->getSeoTitle() ?: $title;
            $description = method_exists($model, 'getSeoDescription') ? $model->getSeoDescription() : '';

            if (isset($model->holvekMeta)) {
                if ($model->holvekMeta->robots) {
                    $robots = $model->holvekMeta->robots;
                }
                if ($model->holvekMeta->canonical_url) {
                    $canonical = $model->holvekMeta->canonical_url;
                }
                if (is_array($model->holvekMeta->hreflang_links)) {
                    $hreflangs = $model->holvekMeta->hreflang_links;
                }
            }
        }

        $html = [
            sprintf('<title>%s</title>', e($title)),
            sprintf('<meta name="description" content="%s">', e((string) $description)),
            sprintf('<meta name="robots" content="%s">', e($robots)),
            // OpenGraph / Twitter Cards
            sprintf('<meta property="og:title" content="%s">', e($title)),
            sprintf('<meta property="og:description" content="%s">', e((string) $description)),
            sprintf('<meta name="twitter:card" content="summary_large_image">'),
            sprintf('<meta name="twitter:title" content="%s">', e($title)),
            sprintf('<meta name="twitter:description" content="%s">', e((string) $description)),
        ];

        if ($canonical) {
            $html[] = sprintf('<link rel="canonical" href="%s">', e($canonical));
        }

        foreach ($hreflangs as $locale => $url) {
            $html[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', e($locale), e($url));
        }

        return implode("\n", $html);
    }
}
