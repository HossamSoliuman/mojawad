<?php

use App\Models\Tilawa;
use App\Models\TilawatSource;
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
        Schema::table('tilawat', function (Blueprint $table) {
            $table->string('production_stage')->nullable()->index()->after('brand_error');
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('error');
        });

        $this->backfillProductionStages();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tilawat', function (Blueprint $table) {
            $table->dropColumn('production_stage');
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }

    /**
     * Keep the recitations that were already inside the old two-tab production
     * queue (Factory sources) visible by seeding each one into the stage that
     * matches its current progress.
     */
    private function backfillProductionStages(): void
    {
        Tilawa::query()
            ->whereHas('source', fn ($q) => $q->whereIn('source_type', TilawatSource::FACTORY_TYPES))
            ->with('publications')
            ->chunkById(200, function ($tilawat) {
                foreach ($tilawat as $tilawa) {
                    $stage = match (true) {
                        $tilawa->publications->contains(fn ($p) => $p->status === 'completed') => 'published',
                        $tilawa->brand_status === 'ready' => 'publishing',
                        default => 'preparing',
                    };

                    $tilawa->update(['production_stage' => $stage]);
                }
            });
    }
};
