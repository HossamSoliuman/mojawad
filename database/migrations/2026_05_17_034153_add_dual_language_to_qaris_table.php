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
        Schema::table('qaris', function (Blueprint $table) {
            $table->renameColumn('name', 'name_ar');
            $table->string('name_en')->nullable()->after('name');
            $table->renameColumn('biography', 'biography_ar');
            $table->longText('biography_en')->nullable()->after('biography');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qaris', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'biography_en']);
            $table->renameColumn('name_ar', 'name');
            $table->renameColumn('biography_ar', 'biography');
        });
    }
};
