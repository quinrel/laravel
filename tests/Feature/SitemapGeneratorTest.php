<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Models\HolvekMeta;
use Holvek\Laravel\Services\Sitemaps\SitemapGenerator;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Storage;

class SitemapGeneratorTest extends TestCase
{
    public function test_it_generates_sitemaps_safely_to_disk(): void
    {
        Storage::fake('public');

        HolvekMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 1,
            'canonical_url' => 'https://example.com/p1',
            'robots' => 'index, follow',
            'updated_at' => now(),
        ]);

        $generator = new SitemapGenerator;
        $generator->generate('public');

        $this->assertTrue(Storage::disk('public')->exists('sitemap.xml'));

        $monthKey = now()->format('Y-m');
        $this->assertTrue(Storage::disk('public')->exists("sitemap-{$monthKey}.xml"));
    }
}
