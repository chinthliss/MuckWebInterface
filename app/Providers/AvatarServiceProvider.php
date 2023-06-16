<?php

namespace App\Providers;

use App\Avatar\AvatarProvider;
use App\Avatar\AvatarProviderViaDatabase;
use App\Avatar\AvatarService;
use App\Muck\MuckService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AvatarServiceProvider extends ServiceProvider  implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(AvatarService::class, function($app) {
            $provider = new AvatarProviderViaDatabase();
            $muckService = $this->app->make(MuckService::class);
            return new AvatarService($provider, $muckService);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [AvatarService::class, AvatarProvider::class];
    }
}
