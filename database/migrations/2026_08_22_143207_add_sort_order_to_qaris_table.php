<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qaris', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_featured')->index();
        });
    }

    public function down(): void
    {
        Schema::table('qaris', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
