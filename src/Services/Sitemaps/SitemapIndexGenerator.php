<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Sitemaps;

use Illuminate\Support\Facades\Storage;

class SitemapIndexGenerator
{
    /**
     * @param  array<int, string>  $sitemaps
     */
    public function generate(array $sitemaps, string $disk = 'public'): void
    {
        $storage = Storage::disk($disk);
        $path = $storage->path('sitemap_index.xml');

        $handle = fopen($path, 'w');
        if (! $handle) {
            return;
        }

        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
        fwrite($handle, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL);

        foreach ($sitemaps as $url) {
            $loc = htmlspecialchars($url, ENT_XML1, 'UTF-8');
            $lastmod = now()->toAtomString();

            $node = <<<XML
    <sitemap>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
    </sitemap>

XML;
            fwrite($handle, $node);
        }

        fwrite($handle, '</sitemapindex>'.PHP_EOL);
        fclose($handle);
    }
}
