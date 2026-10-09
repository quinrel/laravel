<?php

declare(strict_types=1);

namespace Holvek\Laravel\Jobs;

use Holvek\Laravel\Services\IndexNow\IndexNowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
