<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The writing brief the post generator works from, plus the log of what
     * editors changed by hand. Both live beside the campaign so the whole
     * instruction set can be handed to the generator in one copy.
     */
    public function up(): void
    {
        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->longText('post_instructions')->nullable()->after('image_workflow');
            $table->longText('edit_lessons')->nullable()->after('post_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_campaigns', function (Blueprint $table) {
            $table->dropColumn(['post_instructions', 'edit_lessons']);
        });
    }
};
