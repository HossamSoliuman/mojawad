<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('type')->default('manual')->after('slug');
            $table->string('auto_rule')->nullable()->after('type');
            $table->unsignedInteger('auto_limit')->default(10)->after('auto_rule');
            $table->string('icon')->nullable()->after('cover_image');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['type', 'auto_rule', 'auto_limit', 'icon']);
        });
    }
};
