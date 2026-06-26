<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_views', function (Blueprint $t) {
            $t->id();
            $t->foreignId('short_id')->constrained()->cascadeOnDelete();
            $t->string('viewer_key');
            $t->unsignedInteger('views')->default(0);
            $t->timestamp('last_viewed_at')->nullable();
            $t->timestamps();

            $t->unique(['short_id', 'viewer_key']);
            $t->index('viewer_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_views');
    }
};
