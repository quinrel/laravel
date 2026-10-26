@props([
    'type' => 'assisted',
    'model' => null,
    'reviewedBy' => null,
])

<aside
    class="quinrel-ai-disclosure"
    role="note"
    aria-label="AI Transparency Disclosure"
    style="color-scheme: light dark; display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.875rem 1rem; margin: 1.25rem 0; border-radius: 0.625rem; border: 1px solid rgba(113, 113, 122, 0.24); background: rgba(113, 113, 122, 0.06); font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Inter', system-ui, sans-serif; font-size: 0.8125rem; line-height: 1.5; color: inherit;"
>
    <span aria-hidden="true" style="flex-shrink: 0; margin-top: 0.125rem; opacity: 0.75;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l1.9 5.8a2 2 0 0 0 1.3 1.3L21 12l-5.8 1.9a2 2 0 0 0-1.3 1.3L12 21l-1.9-5.8a2 2 0 0 0-1.3-1.3L3 12l5.8-1.9a2 2 0 0 0 1.3-1.3L12 3z" />
        </svg>
    </span>
    <div style="text-wrap: pretty;">
        <strong style="font-weight: 600;">Editorial &amp; AI Transparency:</strong>
        @if($type === 'assisted')
            This content was authored by a human writer with research or structural assistance from generative AI.
        @elseif($type === 'generated')
            This content was drafted using generative AI and verified by our editorial team prior to publication.
        @endif
        @if($model || $reviewedBy)
            <span style="display: block; margin-top: 0.25rem; font-size: 0.75rem; opacity: 0.75;">
                @if($model)
                    Model: {{ $model }}
                @endif
                @if($model && $reviewedBy)
                    &middot;
                @endif
                @if($reviewedBy)
                    Reviewed by: {{ $reviewedBy }}
                @endif
            </span>
        @endif
    </div>
</aside>
