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
        Schema::create('video_clips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tilawa_id')->nullable()->constrained('tilawat')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('template')->default('dark-waveform');
            $table->unsignedInteger('clip_start')->default(0);
            $table->unsignedInteger('clip_duration')->default(30);
            $table->string('overlay_path')->nullable();
            $table->text('caption_ar')->nullable();
            $table->string('output_path')->nullable();
            $table->string('poster_path')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_clips');
    }
};
