<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quinrel_rum_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('url')->index();
            $table->string('metric_name')->index(); // 'LCP', 'INP', 'CLS', 'TTFB', 'FCP'
            $table->decimal('metric_value', 10, 4);
            $table->string('rating')->nullable(); // 'good', 'needs-improvement', 'poor'
            $table->string('session_id')->nullable()->index();
            $table->timestamps();

            $table->index(['metric_name', 'created_at', 'metric_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quinrel_rum_metrics');
    }
};
