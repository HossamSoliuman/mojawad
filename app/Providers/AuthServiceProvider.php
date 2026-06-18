<?php

namespace App\Providers;

use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\TilawatSource;
use App\Policies\QariPolicy;
use App\Policies\TilawaPolicy;
use App\Policies\TilawatSourcePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [Qari::class => QariPolicy::class, Tilawa::class => TilawaPolicy::class, TilawatSource::class => TilawatSourcePolicy::class];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
