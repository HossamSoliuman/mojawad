<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            // Phase 1 — ingest & enrich
            $table->unsignedSmallInteger('ayah_from')->nullable();   // filled by the AI step
            $table->unsignedSmallInteger('ayah_to')->nullable();
            $table->string('ayah_confidence')->nullable();            // high | low | manual

            // Phase 2 — brand & publish (derived assets, cached so re-publish never re-renders)
            $table->string('subtitle_path')->nullable();
            $table->string('master_audio_path')->nullable();
            $table->string('brand_cover_path')->nullable();
            $table->string('brand_video_path')->nullable();
            $table->string('brand_status')->default('none');          // none|processing|ready|failed
            $table->text('brand_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropColumn([
                'ayah_from',
                'ayah_to',
                'ayah_confidence',
                'subtitle_path',
                'master_audio_path',
                'brand_cover_path',
                'brand_video_path',
                'brand_status',
                'brand_error',
            ]);
        });
    }
};
