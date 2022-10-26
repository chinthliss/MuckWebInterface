<?php

namespace Database\Factories;

use App\TermsOfService;
use App\User;

/**
 * Utility class to create a new user for testing purposes
 */
class UserFactory
{
    /**
     * Returns a user in good standing unless given options to change this. Present options:
     * unverified - Prevents flagging the account as verified
     * locked - Sets user as locked if true
     * alternativeEmails - If true, gives the user 1 alternativeEmail. If a number, adds that many.
     * notAgreedToTOS - If true, terms of service agreement isn't set.
     * @param array $options
     * @return User
     */
    public static function create(array $options = []): User
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

        if (!array_key_exists('notAgreedToTOS', $options)) {
            $user->setAgreedToTermsOfService(TermsOfService::getTermsOfServiceHash());
        }

        if (array_key_exists('alternativeEmails', $options)) {
            $initialEmail = $user->getEmail();
            if ($options['alternativeEmails'] === true) $options['alternativeEmails'] = 1;
            for ($i = 0; $i < $options['alternativeEmails']; $i++) {
                $user->setEmail(fake()->unique()->safeEmail());
            }
            $user->setEmail($initialEmail);
        }

        return $user;
    }

}
