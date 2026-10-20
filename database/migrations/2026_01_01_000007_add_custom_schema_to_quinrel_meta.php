<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quinrel_meta', function (Blueprint $table) {
            $table->json('custom_schema')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quinrel_meta', function (Blueprint $table) {
            $table->dropColumn('custom_schema');
        });
    }
};
