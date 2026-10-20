<?php

declare(strict_types=1);

namespace Quinrel\Laravel\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Quinrel\Laravel\Models\QuinrelMeta;

class QuinrelSitemap extends Component
{
    public function render(): View
    {
        $links = QuinrelMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->orderBy('title')
            ->get(['title', 'canonical_url']);

        /** @var view-string $viewName */
        $viewName = 'quinrel::components.sitemap';

        return view($viewName, [
            'links' => $links,
        ]);
    }
}
