<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Technical;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Support\UrlValidator;

class BrokenLinkChecker
{
    /**
     * @param  array<int, string>  $urls
     * @return array<string, array<string, mixed>>
     */
    public function check(array $urls): array
    {
        $results = [];
        $safeUrls = [];

        foreach ($urls as $url) {
            if (! UrlValidator::isSafe($url)) {
                $results[$url] = [
                    'status' => 0,
                    'is_broken' => true,
                    'error' => 'Unsafe or restricted URL blocked by SSRF policy.',
                ];

                continue;
            }

            $safeUrls[] = $url;
        }

        foreach (array_chunk($safeUrls, 10) as $chunk) {
            /** @var array<string, Response|\Throwable> $responses */
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $reqs = [];
                foreach ($chunk as $url) {
                    $reqs[] = $pool->as($url)->timeout(10)->head($url);
                }

                return $reqs;
            });

            foreach ($responses as $url => $response) {
                if ($response instanceof Response) {
                    $status = $response->status();
                    $results[$url] = [
                        'status' => $status,
                        'is_broken' => $response->serverError() || $response->clientError() || $status === 404,
                    ];
                } else {
                    $results[$url] = [
                        'status' => 0,
                        'is_broken' => true,
                        'error' => $response->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }
}
