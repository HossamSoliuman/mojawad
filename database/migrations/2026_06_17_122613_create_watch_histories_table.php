<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $t->foreignId('tilawa_id')->constrained('tilawat')->onDelete('cascade');
            $t->unsignedInteger('position')->default(0);
            $t->unsignedInteger('duration')->default(0);
            $t->boolean('completed')->default(false);
            $t->timestamp('last_watched_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'tilawa_id']);
            $t->index(['user_id', 'last_watched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
    }
};
