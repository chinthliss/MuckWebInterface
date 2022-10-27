<?php

namespace App\Providers;

use App\Muck\MuckConnection;
use App\Muck\MuckConnectionFaker;
use App\Muck\MuckConnectionHttp;
use App\Muck\MuckObjectService;
use App\Muck\MuckObjectsProvider;
use App\Muck\MuckService;
use Error;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class MuckServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(MuckConnection::class, function($app) {

            $connection = null;

            if (!config()->has('muck'))
                throw new Error('Muck configuration not set. Check the MUCK_* variables are set in .env and that the configuration cache has been cleared.');
            $config = config('muck');

            $driver = $config['driver'];
            if (!$driver) throw new Error("Driver hasn't been set in Muck connection config. Ensure MUCK_DRIVER is set.");

            $salt = $config['salt'];
            if (!$salt) throw new Error("Salt hasn't been set in Muck connection config. Ensure MUCK_SALT is set.");

            if (!$config['host'] || !$config['port'] || !$config['uri'])
                throw new Error('Configuration for muck is missing host, port or uri');
            $baseUrl = ($config['useHttps'] ? 'https' : 'http') . '://' . $config['host'] . ':' . $config['port'];
            $uri = $config['uri'];

            if ($driver == 'fake') $connection = new MuckConnectionFaker();
            if ($driver == 'http') $connection = new MuckConnectionHttp($baseUrl, $uri, $salt);
            if (!$connection) throw new Error('Unrecognized muck driver: ' . $driver);

            return $connection;
        });

        $this->app->singleton(MuckService::class, function($app) {
            return new MuckService($app->make(MuckConnection::class));
        });

        $this->app->singleton(MuckObjectService::class, function($app) {
            return new MuckObjectService($app->make(MuckService::class), new MuckObjectsProvider());
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
        return [MuckConnection::class, MuckService::class, MuckObjectService::class];
    }
}
