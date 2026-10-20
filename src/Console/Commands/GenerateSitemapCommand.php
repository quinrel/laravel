<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Quinrel\Laravel\Services\Sitemaps\NewsSitemapGenerator;
use Quinrel\Laravel\Services\Sitemaps\SitemapGenerator;
use Quinrel\Laravel\Services\Sitemaps\VideoSitemapGenerator;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'quinrel:generate-sitemaps
                            {--disk=public : The storage disk to write sitemaps to}
                            {--news : Also generate Google News sitemap}
                            {--video : Also generate Video sitemap}';

    protected $description = 'Generate XML sitemaps for Quinrel SEO';

    public function handle(
        SitemapGenerator $sitemapGenerator,
        NewsSitemapGenerator $newsGenerator,
        VideoSitemapGenerator $videoGenerator
    ): int {
        $diskOption = $this->option('disk');
        $disk = is_string($diskOption) && $diskOption !== '' ? $diskOption : 'public';

        $this->info("Generating sitemaps on disk [{$disk}]...");

        $sitemapGenerator->generate($disk);

        if ($this->option('news')) {
            $newsGenerator->generate($disk);
            $this->info('News sitemap generated.');
        }

        if ($this->option('video')) {
            $videoGenerator->generate($disk);
            $this->info('Video sitemap generated.');
        }

        $this->info('Sitemaps generated successfully.');

        return self::SUCCESS;
    }
}
