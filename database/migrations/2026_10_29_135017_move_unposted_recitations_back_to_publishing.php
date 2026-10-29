<?php

use App\Models\Tilawa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Published now means posted on YouTube or Facebook. Recitations that
     * reached the Published stage through the retired podcast option go back
     * to Publishing so they can be posted by hand.
     */
    public function up(): void
    {
        Tilawa::query()
            ->where('production_stage', 'published')
            ->whereDoesntHave('publications', fn (Builder $query) => $query
                ->whereIn('platform', ['youtube', 'facebook'])
                ->where('status', 'completed'))
            ->update(['production_stage' => 'publishing']);
    }

    public function down(): void
    {
        //
    }
};
