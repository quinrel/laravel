<?php

declare(strict_types=1);

namespace Holvek\Laravel\Events;

use Illuminate\Support\Carbon;

class GatheringVideoSitemapUrls
{
    /**
     * @var array<int, array{url: string, title: string, description: string, thumbnail_loc: string, content_loc: string, publication_date?: Carbon}>
     */
    public array $videos = [];

    /**
     * @param  string  $url  The URL of the page containing the video
     */
    public function addVideo(
        string $url,
        string $title,
        string $description,
        string $thumbnailLoc,
        string $contentLoc,
        ?Carbon $publicationDate = null
    ): void {
        $video = [
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'thumbnail_loc' => $thumbnailLoc,
            'content_loc' => $contentLoc,
        ];

        if ($publicationDate) {
            $video['publication_date'] = $publicationDate;
        }

        $this->videos[] = $video;
    }
}
