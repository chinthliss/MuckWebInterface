<?php

namespace App\Providers;

use App\Muck\MuckObjectService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class MuckServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $connection = null;

        if (!config()->has('muck'))
            throw new \Error('Muck configuration not set.');

        $config = config('muck');
        $driver = $config['driver'];
        if ($driver == 'fake') $connection = new FakeMuckConnection($config);
        if ($driver == 'http') $connection = new HttpMuckConnection($config);
        if (!$driver) throw new Error('Unrecognized muck driver: ' . $driver);

        $provider = new MuckObjectsProvider();

        $this->app->singleton(MuckConnection::class, function($app) use ($connection) {
            return $connection;
        });

        $this->app->singleton(MuckObjectService::class, function($app) use ($connection, $provider) {
            return new MuckObjectService($connection, $provider);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
