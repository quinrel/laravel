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
            $table->string('robots')->nullable()->default('index, follow');
            $table->string('og_image')->nullable();
            $table->json('social_overrides')->nullable();
            $table->boolean('is_cornerstone')->default(false);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['is_cornerstone', 'last_reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quinrel_meta');
    }
};
