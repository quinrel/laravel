<?php

declare(strict_types=1);

namespace Holvek\Laravel\Jobs;

use Holvek\Laravel\Services\Indexing\GoogleIndexingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PingGoogleIndexingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $type = 'URL_UPDATED'
    ) {}

    public function handle(GoogleIndexingService $service): void
    {
        $service->submitUrl($this->url, $this->type);
    }
}

