<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Sitemaps;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class NewsSitemapGenerator
{
    public function generate(string $disk = 'public'): void
    {
        $storage = Storage::disk($disk);
        $path = $storage->path('sitemap-news.xml');

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($path, 'w');
        if (! $handle) {
            return;
        }

        try {
            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
            fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'.PHP_EOL);

            $query = HolvekMeta::query()
                ->whereNotNull('canonical_url')
                ->where(function ($q) {
                    $q->whereNull('robots')
                        ->orWhere('robots', 'not like', '%noindex%');
                })
                ->where(function ($q) {
                    // Google News requires articles published within the last 48 hours.
                    // We use created_at to determine initial publication date.
                    $q->where('created_at', '>=', now()->subHours(48));
                })
                ->orderBy('created_at', 'desc');

            foreach ($query->lazy() as $meta) {
                /** @var HolvekMeta $meta */
                $date = $meta->created_at ?? now();
                $title = (string) ($meta->title ?: 'News Article');
                $url = (string) $meta->canonical_url;

                $node = $this->buildNewsNode($url, $title, $date);
                fwrite($handle, $node);
            }

            fwrite($handle, '</urlset>'.PHP_EOL);
        } finally {
            fclose($handle);
        }
    }

    protected function buildNewsNode(string $url, string $title, Carbon $publishedAt): string
    {
        $loc = htmlspecialchars($url, ENT_XML1, 'UTF-8');
        $siteName = htmlspecialchars((string) config('app.name', 'Site Name'), ENT_XML1, 'UTF-8');
        $titleEscaped = htmlspecialchars($title, ENT_XML1, 'UTF-8');
        $dateStr = $publishedAt->toAtomString();
        // Google News language (default to en)
        $lang = htmlspecialchars((string) config('app.locale', 'en'), ENT_XML1, 'UTF-8');

        return <<<XML
    <url>
        <loc>{$loc}</loc>
        <news:news>
            <news:publication>
                <news:name>{$siteName}</news:name>
                <news:language>{$lang}</news:language>
            </news:publication>
            <news:publication_date>{$dateStr}</news:publication_date>
            <news:title>{$titleEscaped}</news:title>
        </news:news>
    </url>

XML;
    }
}
