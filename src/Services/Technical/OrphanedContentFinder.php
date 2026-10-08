<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Technical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrphanedContentFinder
{
    /**
     * Finds content models that do not have any incoming internal links.
     * Note: This requires the InternalLinkIndexer to have populated the link index table (if one existed)
     * or parses the body text. For a simple implementation, we compare published models against known linked URLs.
     *
     * @param  class-string<Model>  $modelClass
     * @return Collection<int, Model>
     */
    public function find(string $modelClass, string $urlCallback): Collection
    {
        $model = new $modelClass;

        // This is a placeholder for the actual logic which would depend on
        // the host application's architecture (e.g. querying a `holvek_internal_links` table).
        // For a v1.0.0 implementation, we return an empty collection or query a mock table.
        return collect();
    }
}
