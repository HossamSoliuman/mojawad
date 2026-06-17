<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shorts', function (Blueprint $t) {
            $t->id();
            $t->string('title_ar');
            $t->string('title_en')->nullable();
            $t->enum('type', ['audio', 'video']);
            $t->string('media_path');
            $t->string('poster_path')->nullable();
            $t->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $t->unsignedInteger('sort_order')->default(0);
            $t->enum('status', ['active', 'inactive'])->default('active');
            $t->timestamps();

            $t->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shorts');
    }
};
