<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Models\HolvekMeta;
use Holvek\Laravel\Services\Analytics\KeywordCannibalizationDetector;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Schema;

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
        HolvekMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 1,
            'target_keyword' => 'seo optimization',
        ]);

        HolvekMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 2,
            'target_keyword' => 'seo optimization',
        ]);

        HolvekMeta::forceCreate([
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
