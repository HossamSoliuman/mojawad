<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->enum('review_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            $table->text('rejection_note')->nullable()->after('review_status');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_note')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        DB::table('tilawat')->where('status', 'active')->update(['review_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'rejection_note', 'reviewed_at']);
        });
    }
};
