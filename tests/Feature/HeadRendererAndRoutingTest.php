<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Quinrel\Laravel\Facades\Quinrel;
use Quinrel\Laravel\Services\HeadRenderer;
use Quinrel\Laravel\Tests\TestCase;

class HeadRendererAndRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_full_seo_tags_in_non_production_with_noindex_protection(): void
    {
        Quinrel::setTitle('Staging Product Page')
            ->setDescription('Detailed staging description')
            ->setCanonical('https://example.com/products/1')
            ->setOgImage('https://example.com/images/product.jpg');

        $html = Quinrel::render();

        $this->assertStringContainsString('<title>Staging Product Page</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Detailed staging description">', $html);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://example.com/products/1">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="https://example.com/images/product.jpg">', $html);
    }

    public function test_route_macro_seo_metadata_renders_via_head_renderer(): void
    {
        Route::get('/pricing', fn () => Blade::render('@quinrelHead'))
            ->seo([
                'title' => 'Pricing Plans - Quinrel',
                'description' => 'Transparent pricing for teams.',
                'canonical' => 'https://example.com/pricing',
            ]);

        $response = $this->get('/pricing');

        $response->assertOk();
        $response->assertSee('<title>Pricing Plans - Quinrel</title>', false);
        $response->assertSee('<meta name="description" content="Transparent pricing for teams.">', false);
        $response->assertSee('<link rel="canonical" href="https://example.com/pricing">', false);
    }

    public function test_resolves_custom_schema_in_json_ld(): void
    {
        $renderer = app(HeadRenderer::class);
        $renderer->setTitle('FAQ Page')
            ->setSchema([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'name' => 'FAQ Page',
            ]);

        $html = $renderer->render();

        $this->assertStringContainsString('<script type="application/ld+json">', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
    }
}
