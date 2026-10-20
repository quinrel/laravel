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
            $table->string('target_keyword')->nullable()->after('is_cornerstone')->index();
        });
    }

    public function down(): void
    {
        Schema::table('quinrel_meta', function (Blueprint $table) {
            $table->dropIndex(['target_keyword']);
            $table->dropColumn('target_keyword');
        });
    }
};
