<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Technical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrphanedContentFinder
{
    public function __construct(
        protected ?InternalLinkIndexer $linkIndexer = null
    ) {
        $this->linkIndexer ??= new InternalLinkIndexer;
    }

    /**
     * Finds content models that do not have any incoming internal links from other models.
     *
     * @param  class-string<Model>  $modelClass
     * @return Collection<int, Model>
     */
    public function find(string $modelClass, string $urlCallback = 'getUrl'): Collection
    {
        /** @var Collection<int, Model> $models */
        $models = $modelClass::query()->get();
        if ($models->isEmpty()) {
            return collect();
        }

        $urlToModel = [];
        $allLinks = [];

        foreach ($models as $model) {
            $url = $this->resolveModelUrl($model, $urlCallback);
            if ($url === null || $url === '') {
                continue;
            }

            $urlToModel[$url] = $model;
            $body = $this->extractBodyText($model);
            $allLinks[$url] = $this->extractHrefLinks($body, $url);
        }

        $orphanedUrls = $this->linkIndexer
            ? $this->linkIndexer->findOrphanedContent($allLinks, array_keys($urlToModel))
            : [];

        $orphanedModels = [];
        foreach ($orphanedUrls as $orphanedUrl) {
            if (isset($urlToModel[$orphanedUrl])) {
                $orphanedModels[] = $urlToModel[$orphanedUrl];
            }
        }

        return collect($orphanedModels);
    }

    protected function resolveModelUrl(Model $model, string $urlCallback): ?string
    {
        if (method_exists($model, $urlCallback)) {
            $resolved = $model->{$urlCallback}();
            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }
        }

        $attr = $model->getAttribute($urlCallback);
        if (is_string($attr) && $attr !== '') {
            return $attr;
        }

        $canonical = $model->getAttribute('canonical_url');
        if (is_string($canonical) && $canonical !== '') {
            return $canonical;
        }

        return null;
    }

    protected function extractBodyText(Model $model): string
    {
        foreach (['content', 'body', 'html', 'description'] as $field) {
            $value = $model->getAttribute($field);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    protected function extractHrefLinks(string $html, string $selfUrl): array
    {
        if ($html === '') {
            return [];
        }

        if (! preg_match_all('/<a\s[^>]*href=[\'"]([^\'"]+)[\'"]/i', $html, $matches)) {
            return [];
        }

        $links = [];
        foreach ($matches[1] as $href) {
            if ($href !== $selfUrl) {
                $links[] = $href;
            }
        }

        return array_values(array_unique($links));
    }
}
