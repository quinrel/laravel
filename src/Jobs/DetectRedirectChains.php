<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Quinrel\Laravel\Models\QuinrelRedirect;

class DetectRedirectChains implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Load all active redirects to check for chains and loops
        $redirects = QuinrelRedirect::where('is_active', true)->get();

        // Build a map of source -> target for fast lookup
        $redirectMap = $redirects->pluck('target_url', 'source_url')->toArray();

        foreach ($redirects as $redirect) {
            $visited = [$redirect->source_url];
            $current = $redirect->target_url;
            $chain = [];
            $isLoop = false;

            while (isset($redirectMap[$current])) {
                $chain[] = $current;

                if (in_array($current, $visited, true)) {
                    $isLoop = true;
                    break;
                }

                $visited[] = $current;
                $current = $redirectMap[$current];
            }

            if (count($chain) > 0) {
                // Add the final destination to the chain if it's not a loop
                if (! $isLoop) {
                    $chain[] = $current;
                }
            }

            $isChain = count($chain) > 0;

            if (
                $redirect->is_chain !== $isChain ||
                $redirect->is_loop !== $isLoop ||
                $redirect->chain_details !== ($isChain ? $chain : null)
            ) {
                $redirect->update([
                    'is_chain' => $isChain,
                    'is_loop' => $isLoop,
                    'chain_details' => $isChain ? $chain : null,
                ]);
            }
        }
    }
}
