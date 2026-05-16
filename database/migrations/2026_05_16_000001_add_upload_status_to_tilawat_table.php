<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->string('upload_status')->default('done')->after('migrated_to_archive');
            $table->text('upload_error')->nullable()->after('upload_status');
        });
    }

    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropColumn(['upload_status', 'upload_error']);
        });
    }
};
