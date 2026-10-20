<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quinrel_keyword_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('url')->nullable();
            $table->integer('rank')->nullable();
            $table->string('search_engine')->default('google');
            $table->string('location')->nullable();
            $table->date('tracked_at');
            $table->timestamps();

            $table->index(['keyword', 'tracked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quinrel_keyword_rankings');
    }
};
