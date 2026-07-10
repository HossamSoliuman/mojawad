<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tilawa_id')->constrained('tilawat')->cascadeOnDelete();
            $table->string('platform');            // youtube | facebook | podcast
            $table->string('status')->default('pending'); // pending|processing|completed|failed|skipped
            $table->string('external_id')->nullable();
            $table->string('external_url')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tilawa_id', 'platform']);
            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
