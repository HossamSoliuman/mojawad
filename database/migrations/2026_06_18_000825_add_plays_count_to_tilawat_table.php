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
        Schema::table('tilawat', function (Blueprint $table) {
            $table->unsignedBigInteger('plays_count')->default(0)->after('downloads_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropColumn('plays_count');
        });
    }
};
