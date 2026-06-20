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
        Schema::table('shorts', function (Blueprint $table) {
            $table->string('media_path')->nullable()->change();
            $table->string('source_url')->nullable()->after('poster_path');
            $table->string('import_status')->nullable()->after('source_url');
            $table->text('import_error')->nullable()->after('import_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shorts', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'import_status', 'import_error']);
            $table->string('media_path')->nullable(false)->change();
        });
    }
};
