<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    WCAG 2.1/2.2 AA Accessibility Compliance
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 font-mono break-all">{{ $targetUrl }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="autofixAltTexts"
                    class="px-3 py-1.5 text-xs font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-white/10 rounded-lg hover:bg-gray-200 dark:hover:bg-white/20 transition"
                >
                    Generate Alt Text with AI
                </button>
                <button
                    type="button"
                    wire:click="runAuditNow"
                    class="px-3 py-1.5 text-xs font-semibold text-white bg-gray-900 dark:bg-white dark:text-gray-900 rounded-lg hover:opacity-90 transition"
                >
                    Audit Accessibility
                </button>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                <span class="text-xs text-gray-500 dark:text-gray-400 block">Compliance Score</span>
                <strong class="mt-0.5 block text-xl font-bold tabular-nums {{ $score >= 90 ? 'text-emerald-600 dark:text-emerald-400' : ($score >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ $score }}%
                </strong>
            </div>
            <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                <span class="text-xs text-red-600 dark:text-red-400 block">Critical</span>
                <strong class="mt-0.5 block text-xl font-bold tabular-nums text-red-600 dark:text-red-400">{{ $counts['Critical'] ?? 0 }}</strong>
            </div>
            <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                <span class="text-xs text-amber-600 dark:text-amber-400 block">Serious</span>
                <strong class="mt-0.5 block text-xl font-bold tabular-nums text-amber-600 dark:text-amber-400">{{ $counts['Serious'] ?? 0 }}</strong>
            </div>
            <div class="p-3 bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                <span class="text-xs text-gray-600 dark:text-gray-300 block">Moderate</span>
                <strong class="mt-0.5 block text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $counts['Moderate'] ?? 0 }}</strong>
            </div>
        </div>

        @if(!empty($tickets))
            <div class="mt-4 space-y-2.5">
                @foreach(array_slice($tickets, 0, 5) as $ticket)
                    <div class="p-3 text-xs bg-gray-50/80 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
                        <div class="flex items-center justify-between gap-2 font-semibold text-gray-950 dark:text-white">
                            <span>[{{ $ticket['severity'] }}] {{ $ticket['rule_id'] }} (Line {{ $ticket['line'] }})</span>
                            <span class="text-gray-500 dark:text-gray-400 font-mono">{{ $ticket['wcag'] }}</span>
                        </div>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $ticket['message'] }}</p>
                        <code class="mt-1.5 block p-2 rounded bg-gray-900 text-emerald-300 font-mono overflow-x-auto">Fix: {{ $ticket['recommended_replacement'] }}</code>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
