<?php

namespace Database\Factories;

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
        $password = '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589'; // Encrypted version of 'password'

        $user = $provider->createUser($email, $password);

        return $user;
    }
}
