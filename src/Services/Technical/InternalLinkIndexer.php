<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Technical;

class InternalLinkIndexer
{
    /**
     * @param  array<string, array<int, string>>  $allLinks
     * @param  array<int, string>  $allPublishedUrls
     * @return array<int, string>
     */
    public function findOrphanedContent(array $allLinks, array $allPublishedUrls): array
    {
        $targetCounts = [];
        foreach ($allPublishedUrls as $url) {
            $targetCounts[$url] = 0;
        }

        foreach ($allLinks as $sourceUrl => $targets) {
            foreach ($targets as $target) {
                if (isset($targetCounts[$target])) {
                    $targetCounts[$target]++;
                }
            }
        }

        $orphaned = [];
        foreach ($targetCounts as $url => $count) {
            if ($count === 0) {
                $orphaned[] = $url;
            }
        }

        return $orphaned;
    }
}
