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
        $redirects = QuinrelRedirect::where('is_active', true)->get();
        $redirectMap = $redirects->pluck('target_url', 'source_url')->toArray();
        $autoFlatten = (bool) config('quinrel.redirects.auto_flatten_chains', false);

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

            if (count($chain) > 0 && ! $isLoop) {
                $chain[] = $current;
            }

            $isChain = count($chain) > 0;

            if ($autoFlatten && $isChain && ! $isLoop) {
                $finalDestination = (string) end($chain);
                if ($finalDestination !== '' && $finalDestination !== $redirect->source_url) {
                    $redirect->update([
                        'target_url' => $finalDestination,
                        'is_chain' => false,
                        'is_loop' => false,
                        'chain_details' => null,
                    ]);
                    $redirectMap[$redirect->source_url] = $finalDestination;

                    continue;
                }
            }

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
