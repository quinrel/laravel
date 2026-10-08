<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Technical;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BrokenLinkChecker
{
    /**
     * @param  array<int, string>  $urls
     * @return array<string, array<string, mixed>>
     */
    public function check(array $urls): array
    {
        $results = [];

        // Chunk URLs to avoid overwhelming memory and network
        foreach (array_chunk($urls, 10) as $chunk) {
            /** @var array<string, Response|\Exception> $responses */
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
                        'error' => clone $response,
                    ];
                }
            }
        }

        return $results;
    }
}
