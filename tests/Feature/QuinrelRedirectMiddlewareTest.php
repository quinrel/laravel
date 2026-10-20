<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Quinrel\Laravel\Middleware\QuinrelRedirectMiddleware;
use Quinrel\Laravel\Models\QuinrelRedirect;
use Quinrel\Laravel\Tests\TestCase;

class QuinrelRedirectMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_skips_loop_redirects_and_returns_508(): void
    {
        QuinrelRedirect::create([
            'source_url' => '/test-loop',
            'target_url' => '/test-loop-b',
            'status_code' => 301,
            'is_loop' => true,
        ]);

        Route::get('/test-loop', fn () => 'OK')->middleware(QuinrelRedirectMiddleware::class);

        $response = $this->get('/test-loop');
        $response->assertStatus(508);
    }

    public function test_flattens_redirect_chains_at_runtime(): void
    {
        QuinrelRedirect::create([
            'source_url' => '/test-chain-a',
            'target_url' => '/test-chain-b',
            'status_code' => 301,
            'is_chain' => true,
            'chain_details' => ['/test-chain-b', '/final-destination'],
        ]);

        Route::get('/test-chain-a', fn () => 'OK')->middleware(QuinrelRedirectMiddleware::class);

        $response = $this->get('/test-chain-a');
        $response->assertRedirect('/final-destination');
    }

    public function test_ignores_inactive_redirects_and_increments_hits_on_active_redirects(): void
    {
        QuinrelRedirect::create([
            'source_url' => '/inactive-source',
            'target_url' => '/should-not-redirect',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $active = QuinrelRedirect::create([
            'source_url' => '/active-source',
            'target_url' => '/destination',
            'status_code' => 302,
            'is_active' => true,
        ]);

        Route::get('/inactive-source', fn () => 'OK')->middleware(QuinrelRedirectMiddleware::class);
        Route::get('/active-source', fn () => 'OK')->middleware(QuinrelRedirectMiddleware::class);

        $this->get('/inactive-source')->assertOk();

        $this->get('/active-source')->assertRedirect('/destination');
        $this->assertSame(1, $active->fresh()?->hits);
    }

    public function test_survives_malformed_regex_patterns_without_fatal_error(): void
    {
        QuinrelRedirect::create([
            'source_url' => '^/broken-([a-z+',
            'target_url' => '/new',
            'status_code' => 301,
            'is_regex' => true,
            'is_active' => true,
        ]);

        Route::get('/normal-page', fn () => 'OK')->middleware(QuinrelRedirectMiddleware::class);

        $this->get('/normal-page')->assertOk();
    }
}
