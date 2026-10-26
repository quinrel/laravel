<x-filament-panels::page>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <div>
            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                Site-Wide Technical SEO &amp; GEO Crawler
            </h2>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                @if($crawledAt)
                    Last crawl snapshot completed <strong class="font-medium text-gray-700 dark:text-gray-200">{{ $crawledAt }}</strong>
                @else
                    No site crawl has been executed yet. Trigger a crawl to audit internal URLs.
                @endif
            </p>
        </div>
        <div>
            <button
                type="button"
                wire:click="runCrawlNow"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-gray-900 dark:bg-white dark:text-gray-900 rounded-lg shadow-sm hover:opacity-90 transition disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="runCrawlNow">Run Crawl Now</span>
                <span wire:loading wire:target="runCrawlNow">Crawling Site…</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Health Score</span>
            <p class="mt-1.5 text-2xl font-bold tabular-nums tracking-tight {{ $score >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($score >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                {{ $score }}%
            </p>
        </div>
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Pages Crawled</span>
            <p class="mt-1.5 text-2xl font-bold tabular-nums tracking-tight text-gray-950 dark:text-white">
                {{ number_format($totalCrawled) }}
            </p>
        </div>
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <span class="text-xs font-medium text-red-600 dark:text-red-400 block">Critical (P0)</span>
            <p class="mt-1.5 text-2xl font-bold tabular-nums tracking-tight text-red-600 dark:text-red-400">
                {{ number_format($counts['P0'] ?? 0) }}
            </p>
        </div>
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <span class="text-xs font-medium text-amber-600 dark:text-amber-400 block">High / Warning (P1)</span>
            <p class="mt-1.5 text-2xl font-bold tabular-nums tracking-tight text-amber-600 dark:text-amber-400">
                {{ number_format($counts['P1'] ?? 0) }}
            </p>
        </div>
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Medium &amp; Info (P2/P3)</span>
            <p class="mt-1.5 text-2xl font-bold tabular-nums tracking-tight text-gray-950 dark:text-white">
                {{ number_format(($counts['P2'] ?? 0) + ($counts['P3'] ?? 0)) }}
            </p>
        </div>
    </div>

    <div class="mt-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Prioritized Crawl Remediation Tickets</h3>
        </div>
        @if(!empty($tickets) && count($tickets) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <caption class="sr-only">Prioritized crawl remediation tickets</caption>
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50/60 dark:bg-white/5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            <th scope="col" class="py-3 px-4">Priority</th>
                            <th scope="col" class="py-3 px-4">Finding</th>
                            <th scope="col" class="py-3 px-4">URL</th>
                            <th scope="col" class="py-3 px-4">Remediation</th>
                            <th scope="col" class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach($tickets as $ticket)
                            @php
                                $prio = $ticket['priority'] ?? 'P2';
                                $pillClass = match($prio) {
                                    'P0' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20',
                                    'P1' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20',
                                    default => 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-500/20 dark:bg-gray-400/10 dark:text-gray-300 dark:ring-gray-400/20',
                                };
                            @endphp
                            <tr>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums {{ $pillClass }}">
                                        {{ $prio }} &middot; {{ $ticket['severity'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-950 dark:text-white">{{ $ticket['title'] }}</td>
                                <td class="py-3 px-4 font-mono text-xs text-gray-600 dark:text-gray-400 break-all">{{ $ticket['url'] }}</td>
                                <td class="py-3 px-4 text-xs text-gray-600 dark:text-gray-300">{{ $ticket['remediation'] }}</td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    @if(($ticket['autofix_action'] ?? null) === 'create_suggested_redirect')
                                        <button
                                            type="button"
                                            wire:click="createSuggestedRedirect('{{ addslashes($ticket['url']) }}')"
                                            class="px-2.5 py-1 text-xs font-semibold text-white bg-emerald-600 rounded-md hover:bg-emerald-700 transition"
                                        >
                                            Create Suggested Redirect
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">Manual</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-xs text-gray-500 dark:text-gray-400">
                No crawl issues found. Run a crawl to inspect your application URLs.
            </div>
        @endif
    </div>
</x-filament-panels::page>
