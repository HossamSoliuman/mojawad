<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-tilawa video card settings edited in Production → Create: the card
     * texts, the animation toggles, and the path of the last rendered card
     * image. Kept as JSON so the card design can grow without new columns.
     */
    public function up(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->json('brand_card')->nullable()->after('brand_error');
        });
    }

    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropColumn('brand_card');
        });
    }
};
