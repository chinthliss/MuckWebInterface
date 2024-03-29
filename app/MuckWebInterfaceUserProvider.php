<?php

namespace App;

use App\Admin\AccountNote;
use App\Muck\MuckDbref;
use App\Muck\MuckObjectService;
use App\Muck\MuckService;
use MuckInterop;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Error;

class MuckWebInterfaceUserProvider implements UserProvider
{
    /**
     * @var array<int, User>
     */
    private array $cachedUserById = [];

    /**
     * @param MuckService $muckService
     * @param MuckObjectService $muckObjectService
     */
    public function __construct(
        private MuckService       $muckService,
        private MuckObjectService $muckObjectService)
    {
    }

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

    #region Retrieval

    /**
     * @inheritDoc
     */
    public function retrieveById($identifier): User|null
    {
        if (array_key_exists($identifier, $this->cachedUserById)) {
            Log::debug("UserProvider RetrieveById using cached entry for $identifier");
            return $this->cachedUserById[$identifier];
        }

        Log::debug("UserProvider RetrieveById looking up User with id of $identifier");
        $user = null;
        $accountQuery = $this->baseRetrievalQuery()
            ->where('accounts.aid', $identifier)
            ->first();
        if ($accountQuery) {
            $user = User::fromDatabaseResponse($accountQuery);
        }

        $this->cachedUserById[$identifier] = $user;
        Log::debug("UserProvider RetrieveById result for $identifier: [$user]");
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
        if (array_key_exists('email', $credentials)) {
            $accountQuery = $this->baseRetrievalQuery()
                ->where('accounts.email', $credentials['email'])
                ->first();
            if ($accountQuery) return User::fromDatabaseResponse($accountQuery);
        }

        //If it's a character we try the muck
        if (array_key_exists('character', $credentials)) {
            $character = $this->muckObjectService->getByPlayerName($credentials['character']);
            if ($character) {
                $accountQuery = $this->baseRetrievalQuery()
                    ->where('accounts.aid', $character->accountId())
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
                $accountQuery = $this->baseRetrievalQuery()
                    ->where('accounts.aid', $character->accountId())
                    ->first();
                if (!$accountQuery) return null; //Account referenced by muck but wasn't found in DB!
                $user = User::fromDatabaseResponse($accountQuery);
                $user->setCharacter($character);
                return $user;
            }

        }

        return null;
    }

    /**
     * Function to find an account by any email, not just the primary one.
     * @param string $email
     * @return User|null User
     */
    public function retrieveByAnyEmail(string $email): ?User
    {
        Log::debug('UserProvider RetrieveByAnyEmail attempt for ' . $email);
        $accountId = DB::table('account_emails')
            ->where('account_emails.email', '=', $email)
            ->value('aid');

        if ($accountId)
            return $this->retrieveById($accountId);

        return null;
    }

    #endregion Retrieval

    #region Searches

    /**
     * @param string $name
     * @return User[]
     */
    public function searchByCharacterName(string $name): array
    {
        return $this->muckService->findAccountsByCharacterName($name);
    }

    /**
     * @param string $email
     * @return User[]
     */
    public function searchByEmail(string $email): array
    {
        $result = [];

        $rows = $this->baseRetrievalQuery()
            ->where(function ($query) use ($email) {
                $query->where('account_emails.email', 'like', '%' . $email . '%')
                    ->orWhere('accounts.email', 'like', '%' . $email . '%');
            })
            ->get();

        foreach ($rows as $row) {
            $user = User::fromDatabaseResponse($row);
            $result[] = $user;
        }

        return $result;
    }

    /**
     * Both dates are inclusive in this query
     * @param Carbon|null $createdAfter
     * @param Carbon|null $createdBefore
     * @return User[]
     */
    public function searchByCreationDateRange(?Carbon $createdAfter = null, ?Carbon $createdBefore = null): array
    {
        $result = [];

        $query = $this->baseRetrievalQuery();

        if ($createdAfter) $query->whereDate('accounts.created_at', '>=', $createdAfter->toDateString());
        if ($createdBefore) $query->whereDate('accounts.created_at', '<=', $createdBefore->toDateString());

        $rows = $query->get();

        foreach ($rows as $row) {
            $user = User::fromDatabaseResponse($row);
            $result[] = $user;
        }

        return $result;
    }
    #endregion

    /**
     * Validate a user against the given credentials.
     * Actual validation depends on the context of what's provided
     */
    public function validateCredentials($user, array $credentials): bool
    {
        // return Hash::check($credentials['password'], $user->getAuthPassword());
        Log::debug("UserProvider ValidateCredentials attempt for $user with " . json_encode($this->redactCredentials($credentials)));

        // If we have a character set, validate via the muck
        if (array_key_exists('character', $credentials)) {
            return method_exists($user, 'getCharacter')
                && $user->getCharacter()
                && $this->muckService->validateCredentials($user->getCharacter(), $credentials);
        }

        // Otherwise try the database
        return method_exists($user, 'getPasswordType')
            && $user->getPasswordType() == 'SHA1SALT'
            && MuckInterop::verifySHA1SALTPassword($credentials['password'], $user->getAuthPassword());

    }

    /**
     * @inheritDoc
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        DB::table('accounts')
            ->where('aid', $user->getAuthIdentifier())
            ->update([$user->getRememberTokenName() => $token]);
    }

    public function updatePassword(User $user, string $password, $password_type): void
    {
        DB::table('accounts')->where([
            'aid' => $user->id()
        ])->update([
            'password' => $password,
            'password_type' => $password_type,
            'updated_at' => Carbon::now()
        ]);
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

    public function setEmailAsVerified(User $user, string $email): void
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
        if ($newEmailQuery) {
            $result = UserEmail::fromDatabaseResponse($newEmailQuery);
        } else {
            DB::table('account_emails')->insert([
                'email' => $email,
                'aid' => $user->id(),
                'created_at' => Carbon::now()
            ]);
            $result = new UserEmail($email);
            $result->createdAt = Carbon::now();
        }

        // Set primary email to this new one
        DB::table('accounts')->where([
            'aid' => $user->id()
        ])->update([
            'email' => $email,
            'updated_at' => Carbon::now()
        ]);
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
            $nextEmail = UserEmail::fromDatabaseResponse($row);
            $nextEmail->isPrimary = false;
            $emails[] = $nextEmail;
        }

        // Historical system didn't always put primary email into the emails table
        $primaryEmail = DB::table('accounts')
            ->where('aid', '=', $user->id())
            ->value('email');
        $needToAddPrimary = true;
        foreach ($emails as $email) {
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

    public function isEmailAvailable(string $email): bool
    {
        $aid = DB::table('accounts')->where([
            'email' => $email
        ])->value('aid');
        if ($aid) return false;
        $aid = DB::table('account_emails')->where([
            'email' => $email
        ])->value('aid');
        return $aid ? false : true;
    }

    #endregion Email Related

    #region Properties

    public function getAccountProperty(User $user, string $property): mixed
    {
        $row = DB::table('account_properties')
            ->where(['aid' => $user->id(), 'propname' => $property])
            ->first();
        if (!$row) return null;
        switch ($row->proptype) {
            case 'INTEGER':
                return (int)$row->propdata;
            case 'FLOAT':
                return (float)$row->propdata;
            case 'OBJECT':
                return $this->muckObjectService->getByDbref($row->propdata);
            // Other values are 'STRING'
            default:
                return $row->propdata;
        }
    }


    public function setAccountProperty(User $user, string $propertyName, mixed $propertyValue): void
    {
        switch (gettype($propertyValue)) {
            case 'integer':
                $propertyType = 'INTEGER';
                break;
            case 'double':
                $propertyType = 'FLOAT';
                break;
            case 'string':
                $propertyType = 'STRING';
                break;
            case 'boolean':
                $propertyType = 'STRING';
                $propertyValue = $propertyValue ? 'Y' : 'N';
                break;
            case 'object':
                if (is_a($propertyValue, MuckDbref::class)) {
                    $propertyType = 'Object';
                    $propertyValue = $propertyValue->dbref;
                } else throw new Error('Attempt to set account property to unknown value: ' . $propertyValue);
                break;
            default:
                throw new Error('Unknown property type to save: ' . typeof($propertyValue));
        }
        DB::table('account_properties')->updateOrInsert(
            ['aid' => $user->id(), 'propname' => $propertyName],
            ['propdata' => $propertyValue, 'proptype' => $propertyType]
        );
    }

    public function getAccountPropertyDirectory(User $user, string $directory): array
    {
        //Make sure there's not a trailing slashes
        $directory = rtrim($directory, '/');
        $rows = DB::table('account_properties')
            ->where('aid', '=', $user->id())
            ->where('propname', 'like', $directory . '/%')
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $propname = ltrim(substr($row->propname, strlen($directory)), '/');
            switch ($row->proptype) {
                case 'INTEGER':
                    $result[$propname] = (int)$row->propdata;
                    break;
                case 'FLOAT':
                    $result[$propname] = (float)$row->propdata;
                    break;
                case 'OBJECT':
                    $result[$propname] = $this->muckObjectService->getByDbref($row->propdata);
                    break;
                // Other values are 'STRING'
                default:
                    $result[$propname] = $row->propdata;
            }
        }
        return $result;
    }

    #endregion Properties

    public function setIsLocked(User $user, bool $isLocked): void
    {
        DB::table('accounts')->where([
            'aid' => $user->id()
        ])->update([
            'locked_at' => $isLocked ? Carbon::now() : null,
            'updated_at' => Carbon::now()
        ]);
    }

    public function getReferralCount(User $user): int
    {
        return DB::table('account_properties')
            ->where(['propname' => 'tutor', 'propdata' => $user->id()])
            ->count();
    }

    /**
     * Get characters for user
     * @param User $user
     * @return MuckDbref[]
     */
    public function getCharacters(User $user): array
    {
        Log::debug("UserProvider getCharacters looking up: $user");
        return $this->muckObjectService->getCharactersOf($user);
    }

    /**
     * @param User $user
     * @return string[] Roles user has
     */
    public function getRolesFor(User $user): array
    {
        $row = DB::table('account_roles')
            ->where('aid', $user->id())
            ->first();
        return $row ? explode(',', $row->roles) : [];
    }

    /**
     * @return User[] All users that have some sort of role
     */
    public function getAllUsersWithRoles(): array
    {
        $rows = DB::table('account_roles')->get();
        $users = [];
        foreach ($rows as $row) {
            $users[] = User::find($row->aid);
        }
        return $users;
    }

    /**
     * @param User $user
     * @return AccountNote[]
     */
    public function getAccountNotes(User $user): array
    {
        $rows = DB::table('account_notes')
            ->where('aid', '=', $user->id())
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $nextNote = new AccountNote();
            $nextNote->accountId = $row->aid;
            $nextNote->whenAt = Carbon::createFromTimestamp($row->when);
            $nextNote->body = $row->message;
            $nextNote->staffMember = $row->staff_member;
            $nextNote->game = $row->game;
            $result[] = $nextNote;
        }
        return $result;
    }

    public function addAccountNote(User $account, string $authorName, string $note): void
    {
        Log::debug("Adding account note to $account, by $authorName: $note");
        DB::table('account_notes')->insert([
            'when' => Carbon::now()->timestamp,
            'aid' => $account->id(),
            'staff_member' => $authorName,
            'message' => $note,
            'game' => config('muck.name')
        ]);
    }
}
