<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Quinrel\Laravel\Services\IndexNow\IndexNowService;

class PingIndexNowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(public array $urls) {}

    public function handle(IndexNowService $service): void
    {
        $service->submit($this->urls);
    }
}
