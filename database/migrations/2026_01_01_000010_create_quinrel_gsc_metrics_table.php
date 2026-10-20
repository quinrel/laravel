<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quinrel_gsc_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('url')->index()->nullable(); // null means site-wide
            $table->date('date')->index();
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('ctr', 5, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['url', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quinrel_gsc_metrics');
    }
};
