<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    Google Index Status &amp; Remediation
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    URL: <span class="font-mono break-all">{{ $inspectionUrl ?: 'Not configured' }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                @if($diagnosis)
                    @php
                        $badgeColor = match($diagnosis['health_badge'] ?? 'red') {
                            'green' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
                            'yellow' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
                            default => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-300',
                        };
                    @endphp
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $badgeColor }}">
                        {{ $diagnosis['health_label'] ?? 'Unknown' }} &mdash; {{ $diagnosis['coverage_state'] ?? 'Unspecified' }}
                    </span>
                @endif

                <button
                    type="button"
                    wire:click="inspectLiveUrl"
                    wire:loading.attr="disabled"
                    class="px-3 py-1.5 text-xs font-semibold text-white bg-gray-900 dark:bg-white dark:text-gray-900 rounded-lg hover:opacity-90 transition disabled:opacity-50"
                >
                    Inspect Live URL Now
                </button>
            </div>
        </div>

        @if($rateLimitMessage)
            <div class="mt-3 p-3 text-xs text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-950/50 rounded-lg ring-1 ring-amber-600/20">
                {{ $rateLimitMessage }}
            </div>
        @endif

        @if($diagnosis)
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                    <span class="text-gray-500 dark:text-gray-400 block">Indexing State</span>
                    <strong class="mt-0.5 block font-mono text-gray-950 dark:text-white">{{ $diagnosis['indexing_state'] ?? 'N/A' }}</strong>
                </div>
                <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                    <span class="text-gray-500 dark:text-gray-400 block">robots.txt State</span>
                    <strong class="mt-0.5 block font-mono text-gray-950 dark:text-white">{{ $diagnosis['robots_txt_state'] ?? 'N/A' }}</strong>
                </div>
                <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                    <span class="text-gray-500 dark:text-gray-400 block">User Canonical</span>
                    <strong class="mt-0.5 block font-mono break-all text-gray-950 dark:text-white">{{ $diagnosis['user_canonical'] ?? 'None' }}</strong>
                </div>
                <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                    <span class="text-gray-500 dark:text-gray-400 block">Google Canonical</span>
                    <strong class="mt-0.5 block font-mono break-all text-gray-950 dark:text-white">{{ $diagnosis['google_canonical'] ?? 'None' }}</strong>
                </div>
            </div>

            @if(!empty($diagnosis['has_canonical_mismatch']) && !empty($diagnosis['google_canonical']) && $record)
                <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-amber-50 dark:bg-amber-950/40 ring-1 ring-amber-600/20 rounded-lg">
                    <span class="text-xs text-amber-900 dark:text-amber-200">
                        Google selected <strong class="font-mono">{{ $diagnosis['google_canonical'] }}</strong> over your declared canonical.
                    </span>
                    <button
                        type="button"
                        wire:click="adoptGoogleCanonical"
                        class="px-3 py-1.5 text-xs font-semibold text-white bg-amber-600 rounded-md hover:bg-amber-700 transition whitespace-nowrap"
                    >
                        Adopt Google Canonical
                    </button>
                </div>
            @endif

            <div class="mt-4 space-y-2.5">
                @forelse($actions as $action)
                    @php
                        $severityClass = match($action['severity'] ?? 'Recommendation') {
                            'Critical' => 'ring-red-600/20 bg-red-50/60 dark:bg-red-950/30 text-red-950 dark:text-red-200',
                            'Warning' => 'ring-amber-600/20 bg-amber-50/60 dark:bg-amber-950/30 text-amber-950 dark:text-amber-200',
                            default => 'ring-gray-950/5 dark:ring-white/10 bg-gray-50/80 dark:bg-white/5 text-gray-900 dark:text-gray-200',
                        };
                    @endphp
                    <div class="p-3.5 rounded-lg ring-1 {{ $severityClass }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider">{{ $action['severity'] }}</span>
                            <span class="text-xs font-semibold">{{ $action['title'] }}</span>
                        </div>
                        <p class="mt-1 text-xs opacity-90">{{ $action['description'] }}</p>
                        <p class="mt-1.5 text-xs font-semibold">Remediation: {{ $action['remediation'] }}</p>
                    </div>
                @empty
                    <p class="text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                        No indexing blockers or warnings detected for this URL.
                    </p>
                @endforelse
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
