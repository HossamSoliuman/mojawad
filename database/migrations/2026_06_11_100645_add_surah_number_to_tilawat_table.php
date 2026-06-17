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
            $table->unsignedTinyInteger('surah_number')->nullable()->after('recorded_place')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropIndex(['surah_number']);
            $table->dropColumn('surah_number');
        });
    }
};
