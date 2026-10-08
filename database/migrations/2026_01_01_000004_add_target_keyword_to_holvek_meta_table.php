<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holvek_meta', function (Blueprint $table) {
            $table->string('target_keyword')->nullable()->after('is_cornerstone');
        });
    }

    public function down(): void
    {
        Schema::table('holvek_meta', function (Blueprint $table) {
            $table->dropColumn('target_keyword');
        });
    }
};
