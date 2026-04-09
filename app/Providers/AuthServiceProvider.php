<?php

namespace App\Providers;

use App\Models\Qari;
use App\Models\Tilawa;
use App\Policies\QariPolicy;
use App\Policies\TilawaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [Qari::class => QariPolicy::class, Tilawa::class => TilawaPolicy::class];
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
