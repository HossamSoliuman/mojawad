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
        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('published_at');
            $table->string('external_url')->nullable()->after('external_id');
            $table->text('publish_error')->nullable()->after('external_url');
            $table->timestamp('publish_attempted_at')->nullable()->after('publish_error');

            $table->index(['status', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facebook_posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_for']);
            $table->dropColumn(['external_id', 'external_url', 'publish_error', 'publish_attempted_at']);
        });
    }
};
