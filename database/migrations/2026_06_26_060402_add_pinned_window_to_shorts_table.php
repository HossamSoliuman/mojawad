<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shorts', function (Blueprint $t) {
            $t->timestamp('pinned_starts_at')->nullable()->after('sort_order');
            $t->timestamp('pinned_ends_at')->nullable()->after('pinned_starts_at');
            $t->index(['pinned_starts_at', 'pinned_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('shorts', function (Blueprint $t) {
            $t->dropIndex(['pinned_starts_at', 'pinned_ends_at']);
            $t->dropColumn(['pinned_starts_at', 'pinned_ends_at']);
        });
    }
};
