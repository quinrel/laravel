<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services;

class SeoRouteManager
{
    /** @var array<string, array<string, mixed>> */
    protected array $routes = [];

    /**
     * @param  array<string, mixed>  $seoData
     */
    public function register(string $uri, array $seoData): void
    {
        $normalized = '/'.ltrim($uri, '/');
        $this->routes[$normalized] = $seoData;
        $this->routes[ltrim($uri, '/')] = $seoData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getForUri(string $uri): ?array
    {
        $normalized = '/'.ltrim($uri, '/');
        $trimmed = ltrim($uri, '/');

        // Exact match
        if (isset($this->routes[$normalized])) {
            return $this->routes[$normalized];
        }

        if (isset($this->routes[$trimmed])) {
            return $this->routes[$trimmed];
        }

        // Wildcard match
        foreach ($this->routes as $pattern => $data) {
            $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).'$#';
            if (@preg_match($regex, $normalized) === 1 || @preg_match($regex, $trimmed) === 1) {
                return $data;
            }
        }

        return null;
    }
}
