<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers;

use Illuminate\Http\Response;
use Quinrel\Laravel\Models\QuinrelMeta;

class LlmsTxtController
{
    public function index(): Response
    {
        $siteName = (string) config('app.name', 'Laravel');
        $description = (string) config('quinrel.llm_description', "This is the official website for {$siteName}.");

        $content = "# {$siteName}\n\n";
        $content .= "> {$description}\n\n";
        $content .= "## Navigation\n";

        $links = QuinrelMeta::query()
            ->select(['title', 'description', 'canonical_url'])
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->limit(50)
            ->cursor();

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
        $siteName = (string) config('app.name', 'Laravel');
        $description = (string) config('quinrel.llm_description', "This is the official website for {$siteName}.");

        $content = "# {$siteName} (Full Archive)\n\n";
        $content .= "> {$description}\n\n";
        $content .= "## All Pages\n";

        $links = QuinrelMeta::query()
            ->select(['title', 'description', 'canonical_url'])
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->cursor();

        foreach ($links as $link) {
            $title = $link->title ?: $link->canonical_url;
            $url = $link->canonical_url;
            $desc = $link->description ? " - {$link->description}" : '';
            $content .= "- [{$title}]({$url}){$desc}\n";
        }

        return response($content)->header('Content-Type', 'text/plain');
    }
}
