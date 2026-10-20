<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quinrel_redirects', function (Blueprint $table) {
            $table->boolean('is_chain')->default(false)->after('hits');
            $table->boolean('is_loop')->default(false)->after('is_chain');
            $table->json('chain_details')->nullable()->after('is_loop');
        });
    }

    public function down(): void
    {
        Schema::table('quinrel_redirects', function (Blueprint $table) {
            $table->dropColumn(['is_chain', 'is_loop', 'chain_details']);
        });
    }
};
