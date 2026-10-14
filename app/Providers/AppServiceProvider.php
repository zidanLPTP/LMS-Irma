<?php

namespace App\Providers;

use App\Models\Bab;
use App\Models\Kuis;
use App\Models\PercobaanKuis;
use App\Models\User;
use App\Policies\AdminPolicy;
use App\Policies\PenggunaPolicy;
use App\Policies\PercobaanKuisPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, PenggunaPolicy::class);
        Gate::policy(Bab::class, AdminPolicy::class);
        Gate::policy(Kuis::class, AdminPolicy::class);
        Gate::policy(PercobaanKuis::class, PercobaanKuisPolicy::class);
    }
}
