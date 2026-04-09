<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tilawat', function (Blueprint $t) {
            $t->id();
            $t->foreignId('qari_id')->constrained('qaris')->onDelete('cascade');
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->date('recorded_at')->nullable();
            $t->string('recorded_place')->nullable();
            $t->string('audio_path');
            $t->unsignedInteger('duration')->default(0);
            $t->string('cover_image')->nullable();
            $t->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $t->boolean('is_featured')->default(false);
            $t->unsignedBigInteger('downloads_count')->default(0);
            $t->unsignedBigInteger('likes_count')->default(0);
            $t->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('tilawat');
    }
};
