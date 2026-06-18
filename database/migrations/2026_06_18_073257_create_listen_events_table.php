<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listen_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $t->foreignId('tilawa_id')->constrained('tilawat')->onDelete('cascade');
            $t->foreignId('qari_id')->constrained('qaris')->onDelete('cascade');
            $t->unsignedSmallInteger('seconds')->default(0);
            $t->timestamp('listened_at');
            $t->timestamps();
            $t->index(['user_id', 'listened_at']);
            $t->index(['user_id', 'qari_id']);
            $t->index(['user_id', 'tilawa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listen_events');
    }
};
