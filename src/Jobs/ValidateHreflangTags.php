<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Quinrel\Laravel\Models\QuinrelMeta;

class ValidateHreflangTags implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $metas = QuinrelMeta::whereNotNull('hreflang_links')->get();

        foreach ($metas as $meta) {
            $links = $meta->hreflang_links;
            if (! is_array($links)) {
                continue;
            }

            foreach ($links as $locale => $url) {
                // Check if locale format is somewhat valid (e.g., en, en-US, x-default)
                if (! preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale) && $locale !== 'x-default') {
                    Log::warning("Invalid hreflang locale code: {$locale} for QuinrelMeta ID: {$meta->id}");
                }

                // Check return tags logic (if we had the other models in memory, but for now we just log format issues)
                if (! filter_var($url, FILTER_VALIDATE_URL)) {
                    Log::warning("Invalid hreflang URL: {$url} for QuinrelMeta ID: {$meta->id}");
                }
            }
        }
    }
}
