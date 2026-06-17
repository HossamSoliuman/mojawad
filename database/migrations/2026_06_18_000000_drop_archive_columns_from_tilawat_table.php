<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropColumn([
                'archive_url',
                'archive_item_id',
                'archive_filename',
                'migrated_to_archive',
                'upload_status',
                'upload_error',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->string('archive_url')->nullable()->after('audio_path');
            $table->string('archive_item_id')->nullable()->after('archive_url');
            $table->string('archive_filename')->nullable()->after('archive_item_id');
            $table->boolean('migrated_to_archive')->default(false)->after('archive_filename');
            $table->string('upload_status')->default('done')->after('migrated_to_archive');
            $table->text('upload_error')->nullable()->after('upload_status');
        });
    }
};
