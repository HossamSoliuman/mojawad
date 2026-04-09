<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('downloads_log',function(Blueprint $t){
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $t->foreignId('tilawa_id')->constrained('tilawat')->onDelete('cascade');
            $t->string('ip_address')->nullable();
            $t->timestamp('downloaded_at')->useCurrent();
        });
    }
    public function down(): void { Schema::dropIfExists('downloads_log'); }
};