<?php

namespace App\Providers;

use App\User as User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Add blade directive for whether a character is set
        Blade::if('Character', function() {
            /** @var User $user */
            $user = auth()->user();
            return $user && $user->getCharacter();
        });
    }
}
