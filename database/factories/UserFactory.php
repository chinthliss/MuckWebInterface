<?php

namespace Database\Factories;

use App\TermsOfService;
use App\User;
use Illuminate\Support\Facades\DB;

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
     * legacyEmail - If true, the account_emails record is removed to represent an account created before such was used.
     * accountCurrency - if present, sets the given amount of accountCurrency
     * accountFlags - if present, sets account flags to the given array
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
            $user->markEmailAsVerified();
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

        if (array_key_exists('legacyEmail', $options)) {
            // Remove the account_emails record to simulate an old record without one.
            DB::table('account_emails')->where('email', $user->getEmail())->delete();
        }

        if (array_key_exists('roles', $options)) {
            DB::table('account_roles')->insert([
                'aid' => $user->id(),
                'roles' => $options['roles']
            ]);
        }

        if (array_key_exists('accountCurrency', $options)) {
            DB::table('account_properties')->insert([
                'aid' => $user->id(),
                'propname' => 'mako',
                'proptype' => 'INTEGER',
                'propdata' => $options['accountCurrency']
            ]);
        };

        if (array_key_exists('accountFlags', $options)) {
            foreach ($options['accountFlags'] as $flag) {
                DB::table('account_properties')->insert([
                    'aid' => $user->id(),
                    'propname' => 'flags/' . $flag,
                    'proptype' => 'INTEGER',
                    'propdata' => 1
                ]);
            }
        };


        return $user;
    }

}
