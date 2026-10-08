<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Analytics;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Support\Collection;

class KeywordCannibalizationDetector
{
    /**
     * @return Collection<string, Collection<int, HolvekMeta>>
     */
    public function detect(): Collection
    {
        /** @var Collection<int, HolvekMeta> $metas */
        $metas = HolvekMeta::whereNotNull('target_keyword')
            ->where('target_keyword', '!=', '')
            ->get();

        return $metas->groupBy(function (HolvekMeta $meta) {
            return mb_strtolower(trim((string) $meta->target_keyword));
        })->filter(function (Collection $group) {
            return $group->count() > 1;
        });
    }
}
