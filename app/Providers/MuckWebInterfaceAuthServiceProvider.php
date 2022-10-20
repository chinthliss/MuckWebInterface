<?php

namespace App\Providers;

use App\MuckWebInterfaceUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Auth;

class MuckWebInterfaceAuthServiceProvider extends AuthServiceProvider
{

    public function boot()
    {
        $this->registerPolicies();
        Auth::provider('accounts', function($app, array $config) {
            return new MuckWebInterfaceUserProvider();
        });

    }

}
