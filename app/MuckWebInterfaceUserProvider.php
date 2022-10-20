<?php

namespace App;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MuckInterop;

class MuckWebInterfaceUserProvider implements UserProvider
{
    /**
     * @var array<int, User>
     */
    private array $cachedUserById = [];

    /**
     * Gets a base query that contains the required columns for creating a User object.
     * @return Builder
     */
    protected function baseRetrievalQuery(): Builder
    {
        return DB::table('accounts')
            ->select('accounts.*', 'account_emails.verified_at', 'account_emails.created_at as email_created_at')
            ->leftJoin('account_emails', 'account_emails.email', '=', 'accounts.email');
    }

    private function redactCredentials(array $credentials): array
    {
        if (array_key_exists('password', $credentials)) $credentials['password'] = '********';
        return $credentials;
    }

    /**
     * @inheritDoc
     */
    public function retrieveById($identifier): User|null
    {
        if (array_key_exists($identifier, $this->cachedUserById)) {
            Log::debug("UserProvider retrieveById using cached entry for $identifier");
            return $this->cachedUserById[$identifier];
        }
        Log::debug("UserProvider retrieveById looking up User with id of $identifier");
        //Retrieve account details from database first
        $accountQuery = $this->baseRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();
        if (!$accountQuery) return null;
        $user = User::fromDatabaseResponse($accountQuery);
        $this->cachedUserById[$identifier] = $user;
        Log::debug("UserProvider RetrieveById result for $identifier: $user");
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function retrieveByToken($identifier, $token): User|null
    {
        Log::debug("UserProvider RetrieveByToken attempt for $identifier with token $token");
        $accountQuery = $this->baseRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();

        if (!$accountQuery
            || !$accountQuery->remember_token
            || !hash_equals($accountQuery->remember_token, $token)) return null;

        $user = User::fromDatabaseResponse($accountQuery);
        $this->cachedUserById[$identifier] = $user;
        Log::debug("UserProvider RetrieveByToken result for $identifier: $user");
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function retrieveByCredentials(array $credentials): User|null
    {
        Log::debug('UserProvider RetrieveByCredentials attempt with: ' . json_encode($this->redactCredentials($credentials)));

        //If it's an email address we can try the database
        if (array_key_exists('email', $credentials) && strpos($credentials['email'], '@')) {
            $accountQuery = $this->baseRetrievalQuery()
                ->where('accounts.email', $credentials['email'])
                ->first();
            if ($accountQuery) return User::fromDatabaseResponse($accountQuery);
        }

        /* To be reimplemented later
        //If it's an email that might be a character name we try the muck
        if (array_key_exists('email', $credentials) && !strpos($credentials['email'], '@')) {
            $character = $this->muckObjectService->getByPlayerName($credentials['email']);
            if ($character) {
                $accountQuery = $this->baseRetrievalQuery()
                    ->where('accounts.aid', $character->aid())
                    ->first();
                if (!$accountQuery) return null; //Account referenced by muck but wasn't found in DB!
                $user = User::fromDatabaseResponse($accountQuery);
                $user->setCharacter($character);
                return $user;
            }
        }

        //If it's an api_token we try the muck
        if (array_key_exists('api_token', $credentials)) {
            $character = $this->muckObjectService->getByApiToken($credentials['api_token']);
            if ($character) {
                $accountQuery = $this->getRetrievalQuery()
                    ->where('accounts.aid', $character->aid())
                    ->first();
                if (!$accountQuery) return null; //Account referenced by muck but wasn't found in DB!
                $user = User::fromDatabaseResponse($accountQuery);
                $user->setCharacter($character);
                return $user;
            }

        }
        */

        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials($user, array $credentials): bool
    {
        // return Hash::check($credentials['password'], $user->getAuthPassword());
        Log::debug("UserProvider ValidateCredentials attempt for $user with " . json_encode($this->redactCredentials($credentials)));

        //Try the database retrieved details first
        if (method_exists($user, 'getPasswordType')
            && $user->getPasswordType() == 'SHA1SALT'
            && MuckInterop::verifySHA1SALTPassword($credentials['password'], $user->getAuthPassword()))
            return true;

        //Otherwise try the muck
        if (method_exists($user, 'getCharacter') && $user->getCharacter()) {
            throw new \Error("To be reimplemented.");
            // return $this->muckConnection->validateCredentials($user->getCharacter(), $credentials);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        DB::table('accounts')
            ->where('aid', $user->getAuthIdentifier())
            ->update([$user->getRememberTokenName() => $token]);
    }

    /**
     * Creates an account and returns the User object representing it
     * @param string $email
     * @param string $password
     * @return User
     */
    public function createUser(string $email, string $password): User
    {
        // Need to insert into DB first in order to get the id assigned
        DB::table('accounts')->insert([
            'email' => $email,
            'uuid' => Str::uuid(),
            'password' => MuckInterop::createSHA1SALTPassword($password),
            'password_type' => 'SHA1SALT',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $accountQuery = DB::table('accounts')->where('email', $email)->first();
        $user = User::fromDatabaseResponse($accountQuery);
        DB::table('account_emails')->insert([
            'email' => $email,
            'aid' => $user->id(),
            'created_at' => Carbon::now()
        ]);
        return $user;
    }
}
