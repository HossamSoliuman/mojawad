<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single recitation file can span several surahs ("ما تيسر من سورة التوبة
     * ويونس") or be one part of a surah split across files ("البقرة 1" / "البقرة
     * 2"). `surahs` keeps the ordered list of surahs when there is more than one;
     * `part` distinguishes multi-file cuts of the same surah.
     */
    public function up(): void
    {
        Schema::table('tilawat_sources', function (Blueprint $table) {
            $table->json('surahs')->nullable()->after('surah_number');
            $table->unsignedTinyInteger('part')->nullable()->after('surahs');
        });

        Schema::table('tilawat', function (Blueprint $table) {
            $table->json('surahs')->nullable()->after('surah_number');
        });
    }

    public function down(): void
    {
        Schema::table('tilawat_sources', function (Blueprint $table) {
            $table->dropColumn(['surahs', 'part']);
        });

        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropColumn('surahs');
        });
    }
};
