<nav class="quinrel-sitemap" aria-label="Site directory" style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Inter', system-ui, sans-serif; line-height: 1.5;">
    <ul role="list" style="list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr)); gap: 0.75rem;">
        @foreach($links as $link)
            @php
                $href = data_get($link, 'canonical_url') ?? data_get($link, 'url');
                $title = data_get($link, 'title') ?: $href;
                $description = data_get($link, 'description');
            @endphp
            @if($href)
                <li style="padding: 0.875rem 1rem; border-radius: 0.625rem; border: 1px solid rgba(113, 113, 122, 0.22); background: rgba(113, 113, 122, 0.03);">
                    <a href="{{ $href }}" style="display: block; font-size: 0.875rem; font-weight: 600; color: inherit; text-decoration: none; overflow-wrap: break-word;">
                        {{ $title }}
                    </a>
                    @if(!empty($description))
                        <p style="margin: 0.25rem 0 0; font-size: 0.75rem; opacity: 0.75; text-wrap: pretty;">
                            {{ $description }}
                        </p>
                    @endif
                </li>
            @endif
        @endforeach
    </ul>
</nav>
