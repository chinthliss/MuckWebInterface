<?php

namespace Database\Factories;

use App\Helpers\MuckInterop;
use App\User;
use App\MuckWebInterfaceUserProvider;
use Illuminate\Support\Str;

/**
 * Utility class to create a new user for testing purposes
 */
class UserFactory
{
    public static function create(array $attributes = [])
    {
        $provider = User::getProvider();

        $email = fake()->unique()->safeEmail();
        $password = 'password';

        $user = $provider->createUser($email, $password);

        return $user;
    }
}
