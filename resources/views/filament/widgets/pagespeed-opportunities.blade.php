<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    Lighthouse Top Performance Opportunities
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 font-mono break-all">{{ $targetUrl }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs tabular-nums">
                @if($performanceScore !== null)
                    <span class="px-2.5 py-1 font-semibold rounded-full bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white">
                        Perf: {{ $performanceScore }}/100
                    </span>
                @endif
                @if($seoScore !== null)
                    <span class="px-2.5 py-1 font-semibold rounded-full bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white">
                        SEO: {{ $seoScore }}/100
                    </span>
                @endif
                @if($bestPracticesScore !== null)
                    <span class="px-2.5 py-1 font-semibold rounded-full bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white">
                        Best Practices: {{ $bestPracticesScore }}/100
                    </span>
                @endif
            </div>
        </div>

        <div class="space-y-2.5">
            @forelse($opportunities as $opportunity)
                <div class="p-3.5 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-gray-950 dark:text-white">
                            {{ $opportunity['title'] }}
                        </span>
                        @if(($opportunity['wasted_ms'] ?? 0) > 0)
                            <span class="text-xs font-semibold tabular-nums text-amber-600 dark:text-amber-400">
                                Est. Savings: {{ $opportunity['wasted_ms'] }}ms
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                        {{ $opportunity['recommendation'] }}
                    </p>
                    @if(!empty($opportunity['blade_snippet']))
                        <pre class="mt-2 p-2.5 text-xs bg-gray-950 text-gray-100 rounded-md overflow-x-auto font-mono"><code>{{ $opportunity['blade_snippet'] }}</code></pre>
                    @endif
                </div>
            @empty
                <p class="text-xs text-gray-500 dark:text-gray-400">No Lighthouse opportunities detected or PageSpeed API data unavailable.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
