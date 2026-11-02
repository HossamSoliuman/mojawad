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
        Schema::table('video_clips', function (Blueprint $table) {
            $table->decimal('clip_start', 10, 2)->default(0)->change();
            $table->json('subtitles')->nullable()->after('caption_ar');
            $table->string('background_path')->nullable()->after('subtitles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_clips', function (Blueprint $table) {
            $table->dropColumn(['subtitles', 'background_path']);
            $table->unsignedInteger('clip_start')->default(0)->change();
        });
    }
};
