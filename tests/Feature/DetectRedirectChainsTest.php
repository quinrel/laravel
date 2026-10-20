<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Quinrel\Laravel\Jobs\DetectRedirectChains;
use Quinrel\Laravel\Models\QuinrelRedirect;
use Quinrel\Laravel\Tests\TestCase;

class DetectRedirectChainsTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_redirect_chains_and_marks_them_appropriately(): void
    {
        $r1 = QuinrelRedirect::create([
            'source_url' => '/old-a',
            'target_url' => '/old-b',
            'status_code' => 301,
        ]);

        $r2 = QuinrelRedirect::create([
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
        $r1 = QuinrelRedirect::create([
            'source_url' => '/loop-a',
            'target_url' => '/loop-b',
            'status_code' => 301,
        ]);

        $r2 = QuinrelRedirect::create([
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
