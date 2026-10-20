<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\HeadRenderer;
use Quinrel\Laravel\Services\Sitemaps\SitemapGenerator;
use Quinrel\Laravel\Tests\TestCase;

class HreflangTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_hreflang_tags_in_head(): void
    {
        $meta = QuinrelMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 1,
            'canonical_url' => 'https://example.com/en',
            'hreflang_links' => [
                'es' => 'https://example.com/es',
                'fr' => 'https://example.com/fr',
            ],
        ]);

        $model = new class extends User
        {
            public function getSeoTitle()
            {
                return 'Test';
            }

            public function getKey()
            {
                return 1;
            }
        };
        $model->id = 1;
        // Mock relation
        $model->setRelation('quinrelMeta', $meta);

        App::shouldReceive('environment')
            ->with('production')
            ->andReturn(true);

        // Clear cache so it re-renders
        Cache::flush();

        $renderer = new HeadRenderer;
        $html = $renderer->render($model);

        $this->assertStringContainsString('<link rel="alternate" hreflang="es" href="https://example.com/es">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="fr" href="https://example.com/fr">', $html);
    }

    public function test_it_includes_hreflang_in_sitemap(): void
    {
        Storage::fake('public');

        QuinrelMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 1,
            'canonical_url' => 'https://example.com/en',
            'hreflang_links' => [
                'es' => 'https://example.com/es',
            ],
            'robots' => 'index',
            'updated_at' => now(),
        ]);

        $generator = new SitemapGenerator;
        $generator->generate('public');

        $monthKey = now()->format('Y-m');
        $sitemapContent = Storage::disk('public')->get("sitemap-{$monthKey}.xml");

        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="es" href="https://example.com/es" />', $sitemapContent);
        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $sitemapContent);
    }
}
