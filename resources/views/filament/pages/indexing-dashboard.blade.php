<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
            <h2 class="text-lg font-bold">IndexNow Status (Last 24h)</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Submissions: <strong>{{ number_format($indexNowSent ?? 0) }}</strong> / {{ number_format($indexNowQuota ?? 10000) }} daily quota
            </p>
        </div>
        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
            <h2 class="text-lg font-bold">Google Indexing API (Last 24h)</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Submissions: <strong>{{ number_format($googleSent ?? 0) }}</strong> / {{ number_format($googleQuota ?? 200) }} daily quota
            </p>
        </div>
    </div>

    <div class="mt-6 p-4 bg-white rounded-lg shadow dark:bg-gray-800">
        <h3 class="text-base font-semibold mb-3">Recent Indexing Submissions</h3>
        @if(!empty($recentLogs) && count($recentLogs) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="py-2 pr-4">URL</th>
                            <th class="py-2 pr-4">Engine</th>
                            <th class="py-2 pr-4">HTTP Status</th>
                            <th class="py-2">Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLogs as $log)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 pr-4 font-mono text-xs">{{ $log->url }}</td>
                                <td class="py-2 pr-4 uppercase text-xs">{{ $log->engine }}</td>
                                <td class="py-2 pr-4">{{ $log->status_code }}</td>
                                <td class="py-2 text-xs text-gray-500">{{ $log->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No indexing submissions recorded yet.</p>
        @endif
    </div>
</x-filament-panels::page>
