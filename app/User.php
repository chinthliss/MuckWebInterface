<?php

namespace App;

use App\Muck\MuckDbref;
use App\Notifications\VerifyEmail;
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
     * @var Carbon|null Can be null.
     */
    protected ?Carbon $lockedAt = null;

    public function id() : int
    {
        return $this->id;
    }

    //Used by notifiable
    public function getKey(): ?int
    {
        return $this->id;
    }

    /**
     * Route notifications for the mail channel.
     * Required so notifications know where to find the user's email
     * @param  Notification  $notification
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

    private function loadEmails()
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
        return $this->email->verified_at;
    }

    /**
     * Mark the given user's present primary email as verified.
     * @return bool
     */
    public function markEmailAsVerified(): bool
    {
        $this->getProvider()->setEmailAsVerified($this, $this->email->email);
        $this->email->verified_at = Carbon::now();
        return true;
    }

    /**
     * Determine if the user has verified their email address.
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return $this->getEmailVerifiedAt() != null;
    }

    /**
     * Get the email address that should be used for verification.
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email->email;
    }

    public function sendEmailVerificationNotification()
    {
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

    public function setIsLocked(bool $isLocked)
    {
        $this->getProvider()->setIsLocked($this, $isLocked);
        $this->lockedAt = $isLocked ? Carbon::now() : null;
    }

    public function getLockedAt(): ?Carbon
    {
        return $this->lockedAt;
    }

    #region Account Properties

    public function getAccountProperty(string $property): mixed
    {
        return self::getProvider()->getAccountProperty($this, $property);
    }

    public function setAccountProperty(string $property, $value)
    {
        self::getProvider()->setAccountProperty($this, $property, $value);
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

    /**
     * @param MuckDbref $character
     * @return void
     */
    public function setCharacter(MuckDbref $character): void
    {
        //TODO: Reimplement SetCharacter
        Log::error("User.setcharacter not implemented.");
    }

    public function setPassword(string $password)
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
        if (property_exists($query, 'email_created_at') && $query->email_created_at) $email->created_at = new Carbon($query->email_created_at);
        if (property_exists($query, 'verified_at') && $query->verified_at) $email->verified_at = new Carbon($query->verified_at);
        $email->isPrimary = true;
        $user->email = $email;

        //Legacy support - if password type is set to none, the user is considered locked out
        if ($user->passwordType == 'NONE') $user->lockedAt = Carbon::now();

        return $user;
    }
}
