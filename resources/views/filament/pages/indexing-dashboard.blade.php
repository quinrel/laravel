<x-filament-panels::page>
    @php
        $inSent = (int) ($indexNowSent ?? 0);
        $inQuota = max(1, (int) ($indexNowQuota ?? 10000));
        $inPct = min(100, round(($inSent / $inQuota) * 100, 1));

        $gSent = (int) ($googleSent ?? 0);
        $gQuota = max(1, (int) ($googleQuota ?? 200));
        $gPct = min(100, round(($gSent / $gQuota) * 100, 1));
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">IndexNow Dispatch Quota (24h)</h2>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">Bing &middot; Yandex &middot; IndexNow</span>
            </div>
            <p class="mt-2 text-2xl font-bold tabular-nums tracking-tight text-gray-950 dark:text-white">
                {{ number_format($inSent) }}
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">/ {{ number_format($inQuota) }} daily limit</span>
            </p>
            <div class="mt-3 h-1.5 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden" aria-hidden="true">
                <div class="h-full bg-emerald-600 dark:bg-emerald-400 rounded-full" style="width: {{ $inPct }}%"></div>
            </div>
        </div>

        <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Google Indexing API Quota (24h)</h2>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">URL_UPDATED</span>
            </div>
            <p class="mt-2 text-2xl font-bold tabular-nums tracking-tight text-gray-950 dark:text-white">
                {{ number_format($gSent) }}
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">/ {{ number_format($gQuota) }} daily limit</span>
            </p>
            <div class="mt-3 h-1.5 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden" aria-hidden="true">
                <div class="h-full bg-blue-600 dark:bg-blue-400 rounded-full" style="width: {{ $gPct }}%"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Recent Indexing Submissions</h3>
        </div>
        @if(!empty($recentLogs) && count($recentLogs) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <caption class="sr-only">Recent search engine indexing submissions</caption>
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50/60 dark:bg-white/5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            <th scope="col" class="py-3 px-4">URL</th>
                            <th scope="col" class="py-3 px-4">Engine</th>
                            <th scope="col" class="py-3 px-4">HTTP Status</th>
                            <th scope="col" class="py-3 px-4 text-right">Submitted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach($recentLogs as $log)
                            @php
                                $code = (int) ($log->status_code ?? 0);
                                $statusBadge = $code >= 200 && $code < 300
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400'
                                    : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400';
                            @endphp
                            <tr>
                                <td class="py-3 px-4 font-mono text-xs text-gray-800 dark:text-gray-200 break-all">{{ $log->url }}</td>
                                <td class="py-3 px-4 uppercase font-mono text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $log->engine }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums ring-1 ring-inset {{ $statusBadge }}">
                                        {{ $log->status_code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-xs text-gray-500 dark:text-gray-400 tabular-nums">{{ $log->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-xs text-gray-500 dark:text-gray-400">
                No indexing submissions recorded yet.
            </div>
        @endif
    </div>
</x-filament-panels::page>
