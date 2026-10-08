<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services;

class SeoRouteManager
{
    /** @var array<string, array<string, mixed>> */
    protected array $routes = [];

    /**
     * @param  array<string, mixed>  $seoData
     */
    public function register(string $uri, array $seoData): void
    {
        $this->routes[$uri] = $seoData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getForUri(string $uri): ?array
    {
        // Exact match
        if (isset($this->routes[$uri])) {
            return $this->routes[$uri];
        }

        // Wildcard match
        foreach ($this->routes as $pattern => $data) {
            $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).'$#';
            if (preg_match($regex, $uri)) {
                return $data;
            }
        }

        return null;
    }
}
