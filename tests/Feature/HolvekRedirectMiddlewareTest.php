<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Middleware\HolvekRedirectMiddleware;
use Holvek\Laravel\Models\HolvekRedirect;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class HolvekRedirectMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_skips_loop_redirects_and_returns_508(): void
    {
        HolvekRedirect::create([
            'source_url' => '/test-loop',
            'target_url' => '/test-loop-b',
            'status_code' => 301,
            'is_loop' => true,
        ]);

        Route::get('/test-loop', fn () => 'OK')->middleware(HolvekRedirectMiddleware::class);

        $response = $this->get('/test-loop');
        $response->assertStatus(508);
    }

    public function test_flattens_redirect_chains_at_runtime(): void
    {
        HolvekRedirect::create([
            'source_url' => '/test-chain-a',
            'target_url' => '/test-chain-b',
            'status_code' => 301,
            'is_chain' => true,
            'chain_details' => ['/test-chain-b', '/final-destination'],
        ]);

        Route::get('/test-chain-a', fn () => 'OK')->middleware(HolvekRedirectMiddleware::class);

        $response = $this->get('/test-chain-a');
        $response->assertRedirect('/final-destination');
    }
}
