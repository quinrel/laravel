<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Technical;

use Illuminate\Support\Facades\Http;

class FaviconChecker
{
    /**
     * Verifies if standard favicons exist on the given URL.
     *
     * @return array<string, bool>
     */
    public function check(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');

        $icons = [
            'favicon.ico' => $baseUrl.'/favicon.ico',
            'apple-touch-icon.png' => $baseUrl.'/apple-touch-icon.png',
            'icon-192x192.png' => $baseUrl.'/icon-192x192.png',
            'icon-512x512.png' => $baseUrl.'/icon-512x512.png',
        ];

        $results = [];

        foreach ($icons as $name => $url) {
            try {
                $response = Http::timeout(5)->head($url);
                $results[$name] = $response->successful();
            } catch (\Exception $e) {
                $results[$name] = false;
            }
        }

        return $results;
    }
}
