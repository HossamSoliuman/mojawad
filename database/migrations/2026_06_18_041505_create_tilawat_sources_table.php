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
        Schema::create('tilawat_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tilawa_id')->nullable()->constrained('tilawat')->nullOnDelete();
            $table->string('source_type')->default('youtube');
            $table->text('source_url');
            $table->string('source_video_id')->nullable();
            $table->string('source_title')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->foreignId('qari_id')->constrained('qaris')->onDelete('cascade');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('source_video_id');
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tilawat_sources');
    }
};
