<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Schema;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Analytics\KeywordCannibalizationDetector;
use Quinrel\Laravel\Tests\TestCase;

class KeywordCannibalizationDetectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create dummy users table for morphs
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function test_it_detects_duplicate_target_keywords(): void
    {
        QuinrelMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 1,
            'target_keyword' => 'seo optimization',
        ]);

        QuinrelMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 2,
            'target_keyword' => 'seo optimization',
        ]);

        QuinrelMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 3,
            'target_keyword' => 'laravel ranking',
        ]);

        $detector = new KeywordCannibalizationDetector;
        $results = $detector->detect();

        // Should only have 1 group: 'seo optimization'
        $this->assertCount(1, $results);
        $this->assertTrue($results->has('seo optimization'));
        $this->assertCount(2, $results->get('seo optimization'));
    }
}
