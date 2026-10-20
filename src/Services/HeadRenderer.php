<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Middleware\InertiaSeoMiddleware;
use Quinrel\Laravel\Middleware\LivewireSeoMiddleware;
use Quinrel\Laravel\Services\Schema\SchemaGenerator;

class HeadRenderer
{
    /** @var array<string, mixed> */
    protected array $overrides = [];

    public function setTitle(string $title): self
    {
        $this->overrides['title'] = $title;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->overrides['description'] = $description;

        return $this;
    }

    public function setCanonical(string $canonical): self
    {
        $this->overrides['canonical_url'] = $canonical;

        return $this;
    }

    public function setRobots(string $robots): self
    {
        $this->overrides['robots'] = $robots;

        return $this;
    }

    public function setOgImage(string $ogImage): self
    {
        $this->overrides['og_image'] = $ogImage;

        return $this;
    }

    /**
     * @param  array<string, string>  $hreflangs
     */
    public function setHreflangs(array $hreflangs): self
    {
        $this->overrides['hreflang_links'] = $hreflangs;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function setSchema(array $schema): self
    {
        $this->overrides['custom_schema'] = $schema;

        return $this;
    }

    public function resetOverrides(): self
    {
        $this->overrides = [];

        return $this;
    }

    public function inertia(): string
    {
        return InertiaSeoMiddleware::class;
    }

    public function livewire(): string
    {
        return LivewireSeoMiddleware::class;
    }

    public function render(?object $model = null): string
    {
        $isProduction = (bool) App::environment('production');

        if (! empty($this->overrides)) {
            return $this->generateHtml($model, $isProduction);
        }

        $route = request()->route();
        if ($model === null && ($route === null || $route->isFallback)) {
            return $this->generateHtml(null, $isProduction);
        }

        $cacheKey = $this->getCacheKey($model).($isProduction ? '' : '_nonprod');
        $ttl = (int) config('quinrel.cache.ttl', 3600);

        return Cache::remember($cacheKey, max(60, $ttl), function () use ($model, $isProduction) {
            return $this->generateHtml($model, $isProduction);
        });
    }

    protected function getCacheKey(?object $model): string
    {
        if ($model && method_exists($model, 'getKey')) {
            return 'quinrel_seo_'.get_class($model).'_'.$model->getKey();
        }

        return 'quinrel_seo_global_'.md5(request()->path());
    }

    /**
     * Resolve structured SEO metadata for the given model, current route, or runtime overrides.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     robots: string,
     *     canonical: ?string,
     *     og_image: ?string,
     *     twitter_handle: ?string,
     *     hreflangs: array<string, string>,
     *     social_overrides: array<string, mixed>,
     *     custom_schema: array<string, mixed>
     * }
     */
    public function resolveMetadata(?object $model = null, ?bool $isProduction = null): array
    {
        $isProduction ??= (bool) App::environment('production');

        $title = (string) config('app.name', 'Laravel');
        $description = '';
        $robots = 'index, follow';
        $canonical = null;
        $ogImage = config('quinrel.defaults.og_image');
        $ogImage = is_string($ogImage) && $ogImage !== '' ? $ogImage : null;
        $twitterHandle = config('quinrel.social.twitter');
        $twitterHandle = is_string($twitterHandle) && $twitterHandle !== '' ? $twitterHandle : null;
        $hreflangs = [];
        $socialOverrides = [];
        $customSchema = [];

        // 1. Route macro / SeoRouteManager fallback
        $routeSeo = $this->resolveRouteSeo();
        if (is_array($routeSeo)) {
            if (! empty($routeSeo['title']) && is_string($routeSeo['title'])) {
                $title = $routeSeo['title'];
            }
            if (isset($routeSeo['description']) && is_string($routeSeo['description'])) {
                $description = $routeSeo['description'];
            }
            if (! empty($routeSeo['robots']) && is_string($routeSeo['robots'])) {
                $robots = $routeSeo['robots'];
            }
            if (! empty($routeSeo['canonical_url']) && is_string($routeSeo['canonical_url'])) {
                $canonical = $routeSeo['canonical_url'];
            } elseif (! empty($routeSeo['canonical']) && is_string($routeSeo['canonical'])) {
                $canonical = $routeSeo['canonical'];
            }
            if (! empty($routeSeo['og_image']) && is_string($routeSeo['og_image'])) {
                $ogImage = $routeSeo['og_image'];
            }
            if (! empty($routeSeo['hreflang_links']) && is_array($routeSeo['hreflang_links'])) {
                /** @var array<string, string> $routeHreflangs */
                $routeHreflangs = $routeSeo['hreflang_links'];
                $hreflangs = $routeHreflangs;
            }
            if (! empty($routeSeo['custom_schema']) && is_array($routeSeo['custom_schema'])) {
                /** @var array<string, mixed> $routeSchema */
                $routeSchema = $routeSeo['custom_schema'];
                $customSchema = $routeSchema;
            }
        }

        // 2. Model metadata
        if ($model !== null) {
            if (method_exists($model, 'getSeoTitle')) {
                $modelTitle = $model->getSeoTitle();
                if (is_string($modelTitle) && $modelTitle !== '') {
                    $title = $modelTitle;
                }
            }

            if (method_exists($model, 'getSeoDescription')) {
                $modelDesc = $model->getSeoDescription();
                if (is_string($modelDesc)) {
                    $description = $modelDesc;
                }
            }

            if (method_exists($model, 'getSeoImage')) {
                $modelImg = $model->getSeoImage();
                if (is_string($modelImg) && $modelImg !== '') {
                    $ogImage = $modelImg;
                }
            }

            if (isset($model->quinrelMeta) && is_object($model->quinrelMeta)) {
                $meta = $model->quinrelMeta;
                if (! empty($meta->robots) && is_string($meta->robots)) {
                    $robots = $meta->robots;
                }
                if (! empty($meta->canonical_url) && is_string($meta->canonical_url)) {
                    $canonical = $meta->canonical_url;
                }
                if (! empty($meta->og_image) && is_string($meta->og_image)) {
                    $ogImage = $meta->og_image;
                }
                if (! empty($meta->hreflang_links) && is_array($meta->hreflang_links)) {
                    /** @var array<string, string> $metaHreflangs */
                    $metaHreflangs = $meta->hreflang_links;
                    $hreflangs = $metaHreflangs;
                }
                if (! empty($meta->social_overrides) && is_array($meta->social_overrides)) {
                    /** @var array<string, mixed> $metaSocial */
                    $metaSocial = $meta->social_overrides;
                    $socialOverrides = $metaSocial;
                }
                if (! empty($meta->custom_schema) && is_array($meta->custom_schema)) {
                    /** @var array<string, mixed> $metaSchema */
                    $metaSchema = $meta->custom_schema;
                    $customSchema = $metaSchema;
                }
            }
        }

        // 3. Runtime fluent overrides
        if (! empty($this->overrides['title']) && is_string($this->overrides['title'])) {
            $title = $this->overrides['title'];
        }
        if (isset($this->overrides['description']) && is_string($this->overrides['description'])) {
            $description = $this->overrides['description'];
        }
        if (! empty($this->overrides['robots']) && is_string($this->overrides['robots'])) {
            $robots = $this->overrides['robots'];
        }
        if (! empty($this->overrides['canonical_url']) && is_string($this->overrides['canonical_url'])) {
            $canonical = $this->overrides['canonical_url'];
        }
        if (! empty($this->overrides['og_image']) && is_string($this->overrides['og_image'])) {
            $ogImage = $this->overrides['og_image'];
        }
        if (! empty($this->overrides['hreflang_links']) && is_array($this->overrides['hreflang_links'])) {
            /** @var array<string, string> $overrideHreflangs */
            $overrideHreflangs = $this->overrides['hreflang_links'];
            $hreflangs = $overrideHreflangs;
        }
        if (! empty($this->overrides['custom_schema']) && is_array($this->overrides['custom_schema'])) {
            /** @var array<string, mixed> $overrideSchema */
            $overrideSchema = $this->overrides['custom_schema'];
            $customSchema = $overrideSchema;
        }

        // 4. Non-production safety profile: enforce noindex, nofollow while preserving all tags
        if (! $isProduction && config('quinrel.defaults.noindex_non_production', true)) {
            $robots = 'noindex, nofollow';
        }

        return [
            'title' => $title,
            'description' => $description,
            'robots' => $robots,
            'canonical' => $canonical,
            'og_image' => $ogImage,
            'twitter_handle' => $twitterHandle,
            'hreflangs' => $hreflangs,
            'social_overrides' => $socialOverrides,
            'custom_schema' => $customSchema,
        ];
    }

    protected function generateHtml(?object $model, ?bool $isProduction = null): string
    {
        $meta = $this->resolveMetadata($model, $isProduction);

        $ogTitle = isset($meta['social_overrides']['og_title']) && is_string($meta['social_overrides']['og_title'])
            ? $meta['social_overrides']['og_title']
            : $meta['title'];
        $ogDesc = isset($meta['social_overrides']['og_description']) && is_string($meta['social_overrides']['og_description'])
            ? $meta['social_overrides']['og_description']
            : $meta['description'];
        $twitterTitle = isset($meta['social_overrides']['twitter_title']) && is_string($meta['social_overrides']['twitter_title'])
            ? $meta['social_overrides']['twitter_title']
            : $ogTitle;
        $twitterDesc = isset($meta['social_overrides']['twitter_description']) && is_string($meta['social_overrides']['twitter_description'])
            ? $meta['social_overrides']['twitter_description']
            : $ogDesc;

        $html = [
            sprintf('<title>%s</title>', e($meta['title'])),
            sprintf('<meta name="description" content="%s">', e($meta['description'])),
            sprintf('<meta name="robots" content="%s">', e($meta['robots'])),
            sprintf('<meta property="og:title" content="%s">', e($ogTitle)),
            sprintf('<meta property="og:description" content="%s">', e($ogDesc)),
            sprintf('<meta name="twitter:card" content="summary_large_image">'),
            sprintf('<meta name="twitter:title" content="%s">', e($twitterTitle)),
            sprintf('<meta name="twitter:description" content="%s">', e($twitterDesc)),
        ];

        if ($meta['og_image']) {
            $html[] = sprintf('<meta property="og:image" content="%s">', e($meta['og_image']));
            $html[] = sprintf('<meta name="twitter:image" content="%s">', e($meta['og_image']));
        }

        if ($meta['twitter_handle']) {
            $html[] = sprintf('<meta name="twitter:site" content="%s">', e($meta['twitter_handle']));
        }

        if ($meta['canonical']) {
            $html[] = sprintf('<link rel="canonical" href="%s">', e($meta['canonical']));
        }

        foreach ($meta['hreflangs'] as $locale => $url) {
            $html[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', e((string) $locale), e((string) $url));
        }

        if (! empty($meta['custom_schema'])) {
            $generator = new SchemaGenerator;
            $generator->addCustom($meta['custom_schema']);
            $schemaHtml = $generator->render();
            if ($schemaHtml !== '') {
                $html[] = $schemaHtml;
            }
        }

        return implode("\n", $html);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function resolveRouteSeo(): ?array
    {
        $request = request();
        $route = $request->route();

        if ($route !== null) {
            $actionSeo = $route->getAction('quinrel_seo');
            if (is_array($actionSeo)) {
                return $actionSeo;
            }
        }

        return app(SeoRouteManager::class)->getForUri($request->path());
    }
}
