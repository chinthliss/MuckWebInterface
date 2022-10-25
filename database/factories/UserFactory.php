<?php

namespace Database\Factories;

use App\User;

/**
 * Utility class to create a new user for testing purposes
 */
class UserFactory
{
    /**
     * Returns a user in good standing unless given options to change this
     * @param array $options
     * @return User
     */
    public static function create(array $options = [])
    {
        $provider = User::getProvider();

        $email = fake()->unique()->safeEmail();
        $password = 'password';

        $user = $provider->createUser($email, $password);

        if (!array_key_exists('unverified', $options)) {
            $user->setEmailAsVerified();
        }

        if (array_key_exists('locked', $options)) {
            $user->setIsLocked(true);
        }

        return $user;
    }

}
