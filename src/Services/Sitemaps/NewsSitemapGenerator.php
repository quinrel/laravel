<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Sitemaps;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Quinrel\Laravel\Models\QuinrelMeta;

class NewsSitemapGenerator
{
    public function generate(string $disk = 'public'): void
    {
        $storage = Storage::disk($disk);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return;
        }

        try {
            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
            fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'.PHP_EOL);

            $query = QuinrelMeta::query()
                ->whereNotNull('canonical_url')
                ->where(function ($q) {
                    $q->whereNull('robots')
                        ->orWhere('robots', 'not like', '%noindex%');
                })
                ->where(function ($q) {
                    $q->where('created_at', '>=', now()->subHours(48));
                })
                ->orderBy('created_at', 'desc');

            foreach ($query->lazy() as $meta) {
                /** @var QuinrelMeta $meta */
                $date = $meta->created_at ?? now();
                $title = (string) ($meta->title ?: 'News Article');
                $url = (string) $meta->canonical_url;

                $node = $this->buildNewsNode($url, $title, $date);
                fwrite($handle, $node);
            }

            fwrite($handle, '</urlset>'.PHP_EOL);
            rewind($handle);
            $storage->put('sitemap-news.xml', $handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    protected function buildNewsNode(string $url, string $title, Carbon $publishedAt): string
    {
        $loc = htmlspecialchars($url, ENT_XML1, 'UTF-8');
        $siteName = htmlspecialchars((string) config('app.name', 'Site Name'), ENT_XML1, 'UTF-8');
        $titleEscaped = htmlspecialchars($title, ENT_XML1, 'UTF-8');
        $dateStr = $publishedAt->toAtomString();
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
