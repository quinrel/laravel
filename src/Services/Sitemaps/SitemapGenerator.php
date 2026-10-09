<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Sitemaps;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SitemapGenerator
{
    /**
     * Generate the sitemaps.
     */
    public function generate(string $disk = 'public'): void
    {
        $storage = Storage::disk($disk);

        // We'll chunk sitemaps by month/year of the updated_at date
        // to keep them manageable and follow the monthly chunking requirement.

        /** @var array<string, int> $monthlyCounts */
        $monthlyCounts = [];

        $query = HolvekMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->orderBy('updated_at', 'desc');

        // First pass: distribute URLs into monthly files.
        // We open file handles for each month as we encounter them.
        $handles = [];

        try {
            foreach ($query->lazy() as $meta) {
                /** @var HolvekMeta $meta */
                $date = $meta->updated_at ?? $meta->created_at ?? now();
                $monthKey = $date->format('Y-m');

                if (! isset($handles[$monthKey])) {
                    $filename = "sitemap-{$monthKey}.xml";
                    $path = $storage->path($filename);

                    // Create directory if it doesn't exist (local disk)
                    $dir = dirname($path);
                    if (! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    $handle = fopen($path, 'w');
                    if ($handle) {
                        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
                        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'.PHP_EOL);
                        $handles[$monthKey] = $handle;
                        $monthlyCounts[$monthKey] = 0;
                    }
                }

                if (isset($handles[$monthKey])) {
                    $urlNode = $this->buildUrlNode((string) $meta->canonical_url, $date, is_array($meta->hreflang_links) ? $meta->hreflang_links : []);
                    fwrite($handles[$monthKey], $urlNode);
                    $monthlyCounts[$monthKey]++;
                }
            }

            // Close all open handles
            foreach ($handles as $monthKey => $handle) {
                fwrite($handle, '</urlset>'.PHP_EOL);
                fclose($handle);
            }

            // Generate the Sitemap Index
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
        $path = $storage->path('sitemap.xml');

        $handle = fopen($path, 'w');
        if (! $handle) {
            return;
        }

        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
        fwrite($handle, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL);

        $baseUrl = rtrim(config('app.url'), '/');

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
        fclose($handle);
    }
}
