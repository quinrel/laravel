<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Jobs\DetectRedirectChains;
use Holvek\Laravel\Models\HolvekRedirect;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DetectRedirectChainsTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_redirect_chains_and_marks_them_appropriately(): void
    {
        $r1 = HolvekRedirect::create([
            'source_url' => '/old-a',
            'target_url' => '/old-b',
            'status_code' => 301,
        ]);

        $r2 = HolvekRedirect::create([
            'source_url' => '/old-b',
            'target_url' => '/new-c',
            'status_code' => 301,
        ]);

        DetectRedirectChains::dispatchSync();

        $r1->refresh();
        $this->assertTrue($r1->is_chain);
        $this->assertFalse($r1->is_loop);
        $this->assertEquals(['/old-b', '/new-c'], $r1->chain_details);

        $r2->refresh();
        $this->assertFalse($r2->is_chain);
        $this->assertFalse($r2->is_loop);
    }

    public function test_detects_infinite_redirect_loops_and_marks_them_appropriately(): void
    {
        $r1 = HolvekRedirect::create([
            'source_url' => '/loop-a',
            'target_url' => '/loop-b',
            'status_code' => 301,
        ]);

        $r2 = HolvekRedirect::create([
            'source_url' => '/loop-b',
            'target_url' => '/loop-a',
            'status_code' => 301,
        ]);

        DetectRedirectChains::dispatchSync();

        $r1->refresh();
        $this->assertTrue($r1->is_chain);
        $this->assertTrue($r1->is_loop);
        $this->assertEquals(['/loop-b', '/loop-a'], $r1->chain_details);
    }
}
