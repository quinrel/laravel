<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quinrel_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('canonical_url')->nullable()->index();
            $table->json('hreflang_links')->nullable();
            $table->string('robots')->nullable()->default('index, follow');
            $table->string('og_image')->nullable();
            $table->json('social_overrides')->nullable();
            $table->json('custom_schema')->nullable();
            $table->boolean('is_cornerstone')->default(false);
            $table->string('target_keyword')->nullable()->index();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['is_cornerstone', 'last_reviewed_at']);
            $table->unique(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quinrel_meta');
    }
};
