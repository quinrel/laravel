<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quinrel_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_url')->index();
            $table->string('target_url');
            $table->integer('status_code')->default(301);
            $table->boolean('is_regex')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('hits')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_regex', 'source_url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quinrel_redirects');
    }
};
