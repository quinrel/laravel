<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holvek_indexing_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('engine'); // e.g. indexnow, chatgpt, google
            $table->integer('status_code')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holvek_indexing_logs');
    }
};
