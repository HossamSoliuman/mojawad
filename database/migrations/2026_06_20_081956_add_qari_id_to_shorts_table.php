<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shorts', function (Blueprint $table) {
            $table->foreignId('qari_id')->nullable()->after('poster_path')
                ->constrained('qaris')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shorts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('qari_id');
        });
    }
};
