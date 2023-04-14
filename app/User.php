<?php

namespace App;

use App\Muck\MuckDbref;
use App\Notifications\VerifyEmail;
use App\Payment\PatreonManager;
use App\Payment\PaymentSubscriptionManager;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Error;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use stdClass;
use MuckInterop;

class User implements Authenticatable, MustVerifyEmail
{
    use Notifiable;

    /**
     * @var int Primary key. This is the accountId / aid.
     */
    protected int $id;

    /**
     * @var UserEmail Users primary/active email.
     */
    protected UserEmail $email;

    /**
     * All emails. Loaded on demand.
     * @var array<string, UserEmail>|null
     */
    protected ?array $emails = null;

    /**
     * @var string|null Encrypted password.
     */
    protected ?string $password = null;

    /**
     * @var string|null The type of encryption.
     */
    protected ?string $passwordType = null;

    /**
     * @var string|null Remember token.
     */
    protected ?string $rememberToken = null;

    /**
     * @var Carbon|null Loaded on demand
     */
    protected ?Carbon $lastConnect = null;

    /**
     * @var Carbon|null Can be null in the case of older accounts
     */
    protected ?Carbon $createdAt = null;

    /**
     * @var Carbon|null Can be null in the case of older accounts
     */
    protected ?Carbon $updatedAt = null;

    /**
     * Characters of this user. Null if they haven't been loaded yet.
     * @var array<int, MuckDbref>|null
     */
    private ?array $characters = null;

    /**
     * Active character, if set. Set by middleware if present.
     * @var MuckDbref|null
     */
    private ?MuckDbref $character = null;

    /**
     * @var Carbon|null Can be null.
     */
    protected ?Carbon $lockedAt = null;

    public function id(): int
    {
        return $this->id;
    }

    /**
     * Checks whether two user objects share the same accountId
     * @param User|null $otherUser
     * @return bool
     */
    public function is(?User $otherUser): bool
    {
        return $this->id === $otherUser?->id();
    }


    //Used by notifiable
    public function getKey(): ?int
    {
        return $this->id;
    }

    /**
     * Route notifications for the mail channel.
     * Required so notifications know where to find the user's email
     * @param Notification $notification
     * @return array|string
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        return $this->email->email;
    }

    public function __construct(int $accountId)
    {
        $this->id = $accountId;
    }

    public function __toString()
    {
        return "User#" . $this->id;
    }

    /**
     * Shortcut to getting the user provider
     * @return MuckWebInterfaceUserProvider
     */
    public static function getProvider(): MuckWebInterfaceUserProvider
    {
        return App::make(MuckWebInterfaceUserProvider::class);
    }

    #region Authenticatable methods

    /**
     * @inheritDoc
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * @inheritDoc
     */
    public function getAuthIdentifier(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getAuthPassword(): string
    {
        //This should only be called during authentication where it will have already been loaded.
        if (!$this->password) throw new Error("Attempt to query User's password when it hasn't been loaded.");
        return $this->password;
    }

    /**
     * Get the type of encryption used for a user's password.
     * @return string
     */
    public function getPasswordType(): string
    {
        //This should only be called during authentication where it will have already been loaded.
        if (!$this->passwordType) throw new Error("Attempt to query User's passwordType when it hasn't been loaded.");
        return $this->passwordType;
    }

    /**
     * @inheritDoc
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * @inheritDoc
     */
    public function getRememberToken(): ?string
    {
        //This can be genuinely null from the DB if it isn't set.
        return $this->rememberToken;
    }

    /**
     * @inheritDoc
     */
    public function setRememberToken($value): void
    {
        $this->rememberToken = $value;
    }

    #endregion Authenticatable methods

    #region Email functionality

    /**
     * Gets a user's primary email
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email->email;
    }

    private function loadEmails(): void
    {
        $this->emails = $this->getProvider()->getEmails($this);
    }

    /**
     * @return UserEmail[]
     */
    public function getEmails(): array
    {
        if (is_null($this->emails)) {
            $this->loadEmails();
        }
        return $this->emails;
    }

    public function getEmailVerifiedAt(): ?Carbon
    {
        return $this->email->verifiedAt;
    }

    /**
     * Mark the given user's present primary email as verified.
     * @return bool
     */
    public function markEmailAsVerified(): bool
    {
        $this->getProvider()->setEmailAsVerified($this, $this->email->email);
        $this->email->verifiedAt = Carbon::now();
        return true;
    }

    /**
     * Determine if the user has verified their email address.
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->getEmailVerifiedAt() != null;
    }

    /**
     * Get the email address that should be used for verification.
     * @return string
     */
    public function getEmailForVerification(): string
    {
        return $this->email->email;
    }

    public function sendEmailVerificationNotification(): void
    {
        Log::debug("AUTH Sending Email Verification email to $this");
        //TODO: Maybe queue this later
        $this->notify(new VerifyEmail());
    }

    /**
     * Updates email.
     * If the record already exists, it will switch to it otherwise create a new entry
     * @param string $email
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $this->getProvider()->setEmail($this, $email);
        // Refresh emails because an alternative one might already be set up as primary
        $this->loadEmails();
    }

    #endregion Email functionality

    #region Account Properties

    public function getAccountProperty(string $property): mixed
    {
        return self::getProvider()->getAccountProperty($this, $property);
    }

    public function setAccountProperty(string $property, $value): void
    {
        self::getProvider()->setAccountProperty($this, $property, $value);
    }

    /**
     * Returns all the properties under an account property directory
     * @param string $directory
     * @return array<string, mixed>
     */
    public function getAccountPropertyDirectory(string $directory): array
    {
        return self::getProvider()->getAccountPropertyDirectory($this, $directory);
    }

    #endregion Account Properties

    #region Terms of service

    protected ?bool $agreedToTermsOfService = null; // Loaded on demand

    public function getAgreedToTermsOfService(): bool
    {
        if ($this->agreedToTermsOfService === null) {
            $hash = $this->getAccountProperty('tos-hash-viewed');
            $this->agreedToTermsOfService = ($hash == TermsOfService::getTermsOfServiceHash());
        }
        return $this->agreedToTermsOfService;
    }

    public function setAgreedToTermsOfService(string $hash): void
    {
        $this->setAccountProperty('tos-hash-viewed', $hash);
    }

    #endregion Terms of service

    #region Retrieval

    /**
     * Utility function to lookup user
     * @param $id
     * @return User|null
     */
    public static function find($id): ?User
    {
        return self::getProvider()->retrieveById($id);
    }

    /**
     * Utility function to lookup user by email.
     * If true is passed to $allowAlternative will return any match, otherwise will only return primary emails.
     * @param string $email
     * @param bool $allowAlternative
     * @return User|null
     */
    public static function findByEmail(string $email, bool $allowAlternative = false): ?User
    {
        if ($allowAlternative)
            return self::getProvider()->retrieveByAnyEmail($email);
        else
            return self::getProvider()->retrieveByCredentials(['email' => $email]);
    }

    #endregion Retrieval

    #region Roles

    /**
     * @var array|null Roles this user has. Loaded on demand.
     */
    protected ?array $roles = null;

    /**
     * @return void
     */
    private function loadRolesIfRequired(): void
    {
        if ($this->roles == null) $this->roles = $this->getProvider()->getRolesFor($this);
    }

    /**
     * Tests if a user has a role or counts as having a role
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        $this->loadRolesIfRequired();

        // Site admin has every role
        if (in_array('siteadmin', $this->roles)) return true;

        // Admin can be granted by the logged in character
        if ($role == 'admin') {
            return in_array('admin', $this->roles) || ($this->character && $this->character->isAdmin());
        }

        // Staff can be granted by the logged in character AND the admin role
        if ($role == 'staff') {
            return in_array('staff', $this->roles) || in_array('admin', $this->roles)
                || ($this->character && ($this->character->isAdmin() || $this->character->isStaff()));
        }

        //Normal handling
        return in_array($role, $this->roles);
    }

    /**
     * Helper to check if a user has a staff role.
     * This can be true on a non-permanent basis if they're logged in as a staff (W1 - W2) character.
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    /**
     * Helper to check if a user has an admin role.
     * This can be true on a non-permanent basis if they're logged in as an admin (W3+) character.
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Helper to checking if the user has the site admin role.
     * This can only be granted by the database at account level.
     * @return bool
     */
    public function isSiteAdmin(): bool
    {
        return $this->hasRole('siteadmin');
    }

    #endregion Roles

    #region Characters

    /**
     * This only sets the character on the User object at this point
     * Preserving it between sessions is done by middleware
     * @param MuckDbref $character
     * @return void
     */
    public function setCharacter(MuckDbref $character): void
    {
        $this->character = $character;
    }

    /**
     * Get present active character, if set.
     * @return MuckDbref|null
     */
    public function getCharacter(): ?MuckDbref
    {
        return $this->character;
    }

    /**
     * @return MuckDbref[]
     */
    public function getCharacters(): array
    {
        if (!$this->characters) $this->characters = $this->getProvider()->getCharacters($this);
        return $this->characters;
    }

    /**
     * @return array<int,MuckDbref>
     */
    public function getCharactersIndexedByDbref(): array
    {
        $characters = [];
        foreach($this->getCharacters() as $character) {
            $characters[$character->dbref] = $character;
        }
        return $characters;
    }

    #endregion Characters

    public function setIsLocked(bool $isLocked): void
    {
        $this->getProvider()->setIsLocked($this, $isLocked);
        $this->lockedAt = $isLocked ? Carbon::now() : null;
    }

    public function getLockedAt(): ?Carbon
    {
        return $this->lockedAt;
    }

    public function setPassword(string $password): void
    {
        $password = MuckInterop::createSHA1SALTPassword($password);
        $this->password = $password;
        $this->passwordType = 'SHA1SALT';
        $this->getProvider()->updatePassword($this, $password, 'SHA1SALT');
        //$this->updateLastUpdated(); //Done automatically with update
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getReferralCount(): int
    {
        return $this->getProvider()->getReferralCount($this);
    }

    public function getAdminUrl(): string
    {
        return route('admin.account', ['accountId' => $this->id]);
    }

    public function getAccountNotes(): array
    {
        return $this->getProvider()->getAccountNotes($this);
    }

    public function addAccountNote(string $authorName, string $note): void
    {
        $this->getProvider()->addAccountNote($this, $authorName, $note);
    }

    /**
     * Can return null if never connected
     * @return Carbon|null
     */
    public function getLastConnect(): ?Carbon
    {
        if ($this->lastConnect) return $this->lastConnect;

        //TODO: getLastConnect should also check when ACCOUNT last connected, not just character last connects
        $lastConnect = null;
        foreach ($this->getCharacters() as $character) {
            if ($character->lastUsedTimestamp) {
                $lastConnect = max($lastConnect, $character->lastUsedTimestamp);
            }
        }

        $this->lastConnect = $lastConnect;
        return $lastConnect;
    }

    /**
     * Utility function to get present account currency
     * This is NOT cached.
     * @return int accountCurrency
     */
    public function getAccountCurrency(): int
    {
        $value = $this->getAccountProperty('mako');
        return $value ?? 0;
    }

    public function getAccountFlags(): array
    {
        return array_keys($this->getAccountPropertyDirectory('Flags'));
    }

    public function getSupporterPoints(): int
    {
        $value = $this->getAccountProperty('supporter');
        return $value ?? 0;
    }

    public static function fromDatabaseResponse(stdClass $query): User
    {
        if (
            !property_exists($query, 'aid') ||
            !property_exists($query, 'email') ||
            !property_exists($query, 'password')
        ) {
            throw new InvalidArgumentException('Database response must at least contain aid, password and email');
        }
        $user = new self(intval($query->aid));
        $user->password = $query->password;
        if (property_exists($query, 'password_type')) $user->passwordType = $query->password_type;

        if (property_exists($query, 'created_at') && $query->created_at) $user->createdAt = new Carbon($query->created_at);
        if (property_exists($query, 'updated_at') && $query->updated_at) $user->updatedAt = new Carbon($query->updated_at);
        if (property_exists($query, 'locked_at') && $query->locked_at) $user->lockedAt = new Carbon($query->locked_at);
        if (property_exists($query, 'remember_token') && $query->remember_token) $user->rememberToken = $query->remember_token;

        $email = new UserEmail($query->email);
        if (property_exists($query, 'email_created_at') && $query->email_created_at) $email->createdAt = new Carbon($query->email_created_at);
        if (property_exists($query, 'verified_at') && $query->verified_at) $email->verifiedAt = new Carbon($query->verified_at);
        $email->isPrimary = true;
        $user->email = $email;

        //Legacy support - if password type is set to none, the user is considered locked out
        if ($user->passwordType == 'NONE') $user->lockedAt = Carbon::now();

        return $user;
    }

    /**
     * Supported scopes, defaults to 'user':
     *   basic - Only the values required for the site to operate
     *   admin - Admin only, basic but with additional info for admin searches / links
     *   user - Everything a user is permitted to see
     *   all - Admin only, includes everything it can
     * @param string $scope
     * @return array
     */
    public function toArray(string $scope = 'basic'): array
    {
        $this->loadRolesIfRequired();
        $array = [
            'id' => $this->id(),
            'createdAt' => $this->createdAt,
            'verifiedAt' => $this->getEmailVerifiedAt(),
            'lockedAt' => $this->getLockedAt(),
            'roles' => $this->roles
        ];

        if ($scope != 'basic') {
            $characters = [];
            foreach ($this->getCharacters() as $character) {
                $characters[] = $character->toArray();
            }
            $array['characters'] = $characters;
            $array['lastConnected'] = $this->getLastConnect();
            $array['primaryEmail'] = $this->getEmail();
            $array['emails'] = $this->getEmails();
        }

        if ($scope != 'basic' && $scope != 'user') {
            $array['url'] = $this->getAdminUrl();
        }

        if ($scope == 'user' || $scope == 'all') {
            $array['veterancy'] = $this->createdAt?->diffInMonths(Carbon::now()) ?? 0;
            $array['currency'] = $this->getAccountCurrency();
            $array['flags'] = $this->getAccountFlags();
            $array['subscriptionStatus'] = 'TODO: Subscription status';
            $array['referrals'] = $this->getReferralCount();
            $array['supporterPoints'] = $this->getSupporterPoints();

            /** @var PaymentSubscriptionManager $subscriptionManager */
            $subscriptionManager = App::make(PaymentSubscriptionManager::class);
            $subscriptions = [];
            $subscriptionActive = false; // A subscription covers 'now'
            $subscriptionRenewing = false; // A subscription is renewing
            $subscriptionExpires = null; // latest date a subscription expires
            foreach ($subscriptionManager->getSubscriptionsFor($this->id()) as $subscription) {
                if ($subscription->status === 'user_declined' || $subscription->status === 'approval_pending') continue;
                if ($subscription->renewing()) $subscriptionRenewing = true;
                if ($subscription->active()) {
                    $subscriptionActive = true;
                    if (!$subscriptionExpires || $subscription->expires() > $subscriptionExpires)
                        $subscriptionExpires = $subscription->expires();
                }
                $subscriptions[] = $subscription->toArray();
            }
            $array['subscriptions'] = $subscriptions;
            $array['subscriptionActive'] = $subscriptionActive;
            $array['subscriptionRenewing'] = $subscriptionRenewing;
            $array['subscriptionExpires'] = $subscriptionExpires;
        }

        if ($scope == 'all') {
            $array['notes'] = $this->getAccountNotes();

            /** @var PatreonManager $patreonManager */
            $patreonManager = App::make(PatreonManager::class);
            $patron = $patreonManager->getPatronForUser($this);
            if ($patron) {
                $array['patreon'] = $patron->toAdminArray();
            }

        }

        return $array;
    }
}
