<?php

declare(strict_types=1);

namespace Holvek\Laravel\View\Components;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\View\Component;
use Illuminate\View\View;

class HolvekSitemap extends Component
{
    public function render(): View
    {
        $links = HolvekMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->orderBy('title')
            ->get(['title', 'canonical_url']);

        /** @var view-string $viewName */
        $viewName = 'holvek::components.sitemap';

        return view($viewName, [
            'links' => $links,
        ]);
    }
}
