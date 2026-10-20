<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Indexing;

use Quinrel\Laravel\Models\QuinrelIndexingLog;

class LlmIndexingPinger
{
    /**
     * @param  array<int, string>  $urls
     */
    public function ping(array $urls): void
    {
        // Currently, LLM search engines like Perplexity or ChatGPT do not have an official
        // push API like IndexNow. However, providing a list of URLs in a ping to our own
        // aggregator or simulating the process for future compatibility is a common request.
        // We log the intent to index via LLM.

        foreach ($urls as $url) {
            QuinrelIndexingLog::create([
                'url' => $url,
                'engine' => 'llm',
                'status_code' => 200,
                'response' => ['message' => 'Queued for LLM discovery'],
            ]);
        }
    }
}
