<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qari_id')->constrained()->cascadeOnDelete();
            $table->foreignId('surah_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['hosted','linked']);
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();

            $table->enum('status', ['pending','approved','rejected'])->default('pending');

            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->unsignedBigInteger('score')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};