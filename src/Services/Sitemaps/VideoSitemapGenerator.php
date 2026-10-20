<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Sitemaps;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Quinrel\Laravel\Events\GatheringVideoSitemapUrls;

class VideoSitemapGenerator
{
    public function generate(string $disk = 'public'): void
    {
        $event = new GatheringVideoSitemapUrls;
        event($event);

        if ($event->videos === []) {
            return;
        }

        $storage = Storage::disk($disk);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
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
            rewind($handle);
            $storage->put('video-sitemap.xml', $handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
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
