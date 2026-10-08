<?php

declare(strict_types=1);

namespace Holvek\Laravel\Http\Controllers;

use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Http\Response;

class LlmsTxtController
{
    public function index(): Response
    {
        $siteName = config('app.name', 'Laravel');
        $baseUrl = rtrim(config('app.url'), '/');
        $description = config('holvek.llm_description', "This is the official website for {$siteName}.");

        $content = "# {$siteName}\n\n";
        $content .= "> {$description}\n\n";

        $content .= "## Navigation\n";

        $links = HolvekMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->limit(50) // Keep the basic llms.txt small
            ->get(['title', 'description', 'canonical_url']);

        foreach ($links as $link) {
            $title = $link->title ?: $link->canonical_url;
            $url = $link->canonical_url;
            $desc = $link->description ? " - {$link->description}" : '';
            $content .= "- [{$title}]({$url}){$desc}\n";
        }

        return response($content)->header('Content-Type', 'text/plain');
    }

    public function full(): Response
    {
        $siteName = config('app.name', 'Laravel');
        $description = config('holvek.llm_description', "This is the official website for {$siteName}.");

        $content = "# {$siteName} (Full Archive)\n\n";
        $content .= "> {$description}\n\n";

        $content .= "## All Pages\n";

        $links = HolvekMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->get(['title', 'description', 'canonical_url']);

        foreach ($links as $link) {
            $title = $link->title ?: $link->canonical_url;
            $url = $link->canonical_url;
            $desc = $link->description ? " - {$link->description}" : '';
            $content .= "- [{$title}]({$url}){$desc}\n";
        }

        return response($content)->header('Content-Type', 'text/plain');
    }
}
