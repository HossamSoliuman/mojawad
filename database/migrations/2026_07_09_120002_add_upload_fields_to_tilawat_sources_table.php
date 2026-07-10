<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tilawat_sources', function (Blueprint $table) {
            $table->unsignedTinyInteger('surah_number')->nullable()->after('qari_id');
        });
    }

    public function down(): void
    {
        Schema::table('tilawat_sources', function (Blueprint $table) {
            $table->dropColumn('surah_number');
        });
    }
};
