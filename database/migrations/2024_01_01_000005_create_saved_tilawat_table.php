<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('saved_tilawat',function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $t->foreignId('tilawa_id')->constrained('tilawat')->onDelete('cascade');
            $t->timestamps(); $t->unique(['user_id','tilawa_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('saved_tilawat'); }
};