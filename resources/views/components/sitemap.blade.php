<div class="quinrel-sitemap">
    <ul>
        @foreach($links as $link)
            @if($link->canonical_url)
                <li>
                    <a href="{{ $link->canonical_url }}">{{ $link->title ?: $link->canonical_url }}</a>
                </li>
            @endif
        @endforeach
    </ul>
</div>
