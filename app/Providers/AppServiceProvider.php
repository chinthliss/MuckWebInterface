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

        // Add blade directive for whether a site notice is set
        Blade::if('SiteNoticeExists', function(){
            $filePath = public_path('site-notice.txt');
            return file_exists($filePath);
        });
    }
}
