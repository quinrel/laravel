<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Sitemaps;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Quinrel\Laravel\Models\QuinrelMeta;

class SitemapGenerator
{
    /**
     * Generate the sitemaps using cloud-compatible stream handles.
     */
    public function generate(string $disk = 'public'): void
    {
        $storage = Storage::disk($disk);

        /** @var array<string, int> $monthlyCounts */
        $monthlyCounts = [];

        $query = QuinrelMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->orderBy('updated_at', 'desc');

        /** @var array<string, resource> $handles */
        $handles = [];

        try {
            foreach ($query->lazy() as $meta) {
                /** @var QuinrelMeta $meta */
                $date = $meta->updated_at ?? $meta->created_at ?? now();
                $monthKey = $date->format('Y-m');

                if (! isset($handles[$monthKey])) {
                    $handle = fopen('php://temp', 'r+');
                    if ($handle !== false) {
                        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
                        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'.PHP_EOL);
                        $handles[$monthKey] = $handle;
                        $monthlyCounts[$monthKey] = 0;
                    }
                }

                if (isset($handles[$monthKey])) {
                    /** @var array<string, string> $hreflangs */
                    $hreflangs = is_array($meta->hreflang_links) ? $meta->hreflang_links : [];
                    $urlNode = $this->buildUrlNode((string) $meta->canonical_url, $date, $hreflangs);
                    fwrite($handles[$monthKey], $urlNode);
                    $monthlyCounts[$monthKey]++;
                }
            }

            foreach ($handles as $monthKey => $handle) {
                fwrite($handle, '</urlset>'.PHP_EOL);
                rewind($handle);
                $storage->put("sitemap-{$monthKey}.xml", $handle);
                fclose($handle);
                unset($handles[$monthKey]);
            }

            $this->generateIndex($disk, array_keys($monthlyCounts));

        } catch (\Throwable $e) {
            foreach ($handles as $handle) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
            throw $e;
        }
    }

    /**
     * @param  array<string, string>  $hreflangs
     */
    protected function buildUrlNode(string $url, Carbon $updatedAt, array $hreflangs = []): string
    {
        $loc = htmlspecialchars($url, ENT_XML1, 'UTF-8');
        $lastmod = $updatedAt->toAtomString();

        $node = "    <url>\n        <loc>{$loc}</loc>\n        <lastmod>{$lastmod}</lastmod>\n";

        foreach ($hreflangs as $locale => $href) {
            $hrefEscaped = htmlspecialchars($href, ENT_XML1, 'UTF-8');
            $localeEscaped = htmlspecialchars($locale, ENT_XML1, 'UTF-8');
            $node .= "        <xhtml:link rel=\"alternate\" hreflang=\"{$localeEscaped}\" href=\"{$hrefEscaped}\" />\n";
        }

        $node .= "    </url>\n";

        return $node;
    }

    /**
     * @param  array<int, string>  $months
     */
    protected function generateIndex(string $disk, array $months): void
    {
        $storage = Storage::disk($disk);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return;
        }

        try {
            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
            fwrite($handle, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL);

            $baseUrl = rtrim((string) config('app.url'), '/');

            foreach ($months as $monthKey) {
                $sitemapUrl = htmlspecialchars("{$baseUrl}/storage/sitemap-{$monthKey}.xml", ENT_XML1, 'UTF-8');
                $lastmod = now()->toAtomString();

                $node = <<<XML
    <sitemap>
        <loc>{$sitemapUrl}</loc>
        <lastmod>{$lastmod}</lastmod>
    </sitemap>

XML;
                fwrite($handle, $node);
            }

            fwrite($handle, '</sitemapindex>'.PHP_EOL);
            rewind($handle);
            $storage->put('sitemap.xml', $handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }
}
