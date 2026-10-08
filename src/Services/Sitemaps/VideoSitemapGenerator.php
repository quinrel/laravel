<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Sitemaps;

use Holvek\Laravel\Events\GatheringVideoSitemapUrls;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class VideoSitemapGenerator
{
    public function generate(string $disk = 'public'): void
    {
        $event = new GatheringVideoSitemapUrls;
        event($event);

        if (empty($event->videos)) {
            return; // Don't generate empty video sitemap
        }

        $storage = Storage::disk($disk);
        $path = $storage->path('video-sitemap.xml');

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
            fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">'.PHP_EOL);

            foreach ($event->videos as $video) {
                $node = $this->buildVideoNode($video);
                fwrite($handle, $node);
            }

            fwrite($handle, '</urlset>'.PHP_EOL);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array{url: string, title: string, description: string, thumbnail_loc: string, content_loc: string, publication_date?: Carbon}  $video
     */
    protected function buildVideoNode(array $video): string
    {
        $loc = htmlspecialchars($video['url'], ENT_XML1, 'UTF-8');
        $title = htmlspecialchars($video['title'], ENT_XML1, 'UTF-8');
        $desc = htmlspecialchars($video['description'], ENT_XML1, 'UTF-8');
        $thumb = htmlspecialchars($video['thumbnail_loc'], ENT_XML1, 'UTF-8');
        $content = htmlspecialchars($video['content_loc'], ENT_XML1, 'UTF-8');

        $dateNode = '';
        if (isset($video['publication_date'])) {
            $dateStr = $video['publication_date']->toAtomString();
            $dateNode = "\n            <video:publication_date>{$dateStr}</video:publication_date>";
        }

        return <<<XML
    <url>
        <loc>{$loc}</loc>
        <video:video>
            <video:thumbnail_loc>{$thumb}</video:thumbnail_loc>
            <video:title>{$title}</video:title>
            <video:description>{$desc}</video:description>
            <video:content_loc>{$content}</video:content_loc>{$dateNode}
        </video:video>
    </url>

XML;
    }
}
