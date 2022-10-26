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
        $accountQuery = $this->baseRetrievalQuery()->where('accounts.email', $email)->first();
        $user = User::fromDatabaseResponse($accountQuery);
        DB::table('account_emails')->insert([
            'email' => $email,
            'aid' => $user->id(),
            'created_at' => Carbon::now()
        ]);
        return $user;
    }

    #region Email Related

    public function setEmailAsVerified(User $user, string $email)
    {
        // Verify email - this may not exist if it was created from outside
        DB::table('account_emails')->updateOrInsert(
            ['aid' => $user->id(), 'email' => $email],
            ['verified_at' => Carbon::now()]
        );
        // And make it active email
        DB::table('accounts')->where([
            'aid' => $user->id()
        ])->update([
            'email' => $email,
            'updated_at' => Carbon::now()
        ]);
    }

    /**
     * Sets the user's email.
     * Setting an email always makes it the primary email.
     * @param User $user
     * @param string $email
     * @return UserEmail
     */
    public function setEmail(User $user, string $email): UserEmail
    {
        Log::debug("UserProvider setting email of $user to $email");
        //Because historic code may not have made an entry for the user's existing mail, check on such
        $existing = $user->getEmail();
        if ($existing) {
            $query = DB::table('account_emails')->where([
                'email' => $existing
            ])->first();
            if (!$query) {
                Log::debug("UserProvider fixing missing EXISTING email of $user: " . $existing);
                DB::table('account_emails')->insert([
                    'email' => $existing,
                    'aid' => $user->id()
                ]);
            }
        }

        //Need to make sure there's a reference in account_emails for the new email
        $newEmailQuery = DB::table('account_emails')->where([
            'email' => $email
        ])->first();
        if (!$newEmailQuery) {
            DB::table('account_emails')->insert([
                'email' => $email,
                'aid' => $user->id(),
                'created_at' => Carbon::now()
            ]);
        }

        // Set primary email to this new one
        DB::table('accounts')->where([
            'aid' => $user->id()
        ])->update([
            'email' => $email,
            'updated_at' => Carbon::now()
        ]);
        $result = new UserEmail($email);
        $result->verified_at = $newEmailQuery ? new Carbon($newEmailQuery->verified_at) : null;
        $result->created_at = $newEmailQuery ? new Carbon($newEmailQuery->created_at) : Carbon::now();
        $result->isPrimary = true;
        return $result;
    }

    /**
     * Get all emails to do with a user in the form
     * @param User $user
     * @return UserEmail[]
     */
    public function getEmails(User $user): array
    {
        Log::debug("UserProvider getEmails query for $user");

        $emails = [];
        $rows = DB::table('account_emails')->select([
            'email', 'created_at', 'verified_at'
        ])->where([
            'aid' => $user->id()
        ])->get();
        foreach ($rows as $row) {
            $nextEmail = new UserEmail($row->email);
            $nextEmail->created_at = $row->created_at ? new Carbon($row->created_at) : null;
            $nextEmail->verified_at = $row->verified_at ? new Carbon($row->verified_at) : null;
            $nextEmail->isPrimary = false;
            $emails[] = $nextEmail;
        }

        // Historical system didn't always put primary email into the emails table
        $primaryEmail = DB::table('accounts')
            ->where('aid', '=', $user->id())
            ->value('email');
        $needToAddPrimary = true;
        foreach($emails as $email) {
            if ($email->email == $primaryEmail) {
                $needToAddPrimary = false;
                $email->isPrimary = true;
            }
        }
        if ($needToAddPrimary) {
            $nextEmail = new UserEmail($primaryEmail);
            $nextEmail->isPrimary = true;
            $emails[] = $nextEmail;
        }

        Log::debug("UserProvider getEmails result for $user: " . json_encode($emails));
        return $emails;

    }

    #endregion Email Related

    public function setIsLocked(User $user, bool $isLocked)
    {
        DB::table('accounts')->where([
            'aid' => $user->id()
        ])->update([
            'locked_at' => $isLocked ? Carbon::now() : null,
            'updated_at' => Carbon::now()
        ]);
    }
}
