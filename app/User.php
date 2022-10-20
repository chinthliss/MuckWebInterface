<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\App;

class User implements Authenticatable
{
    /**
     * @var int Primary key. This is the accountId / aid.
     */
    protected int $id;

    /**
     * @var UserEmail|null Users primary/active email. Loaded on demand.
     */
    protected ?UserEmail $email;

    /**
     * All emails. Loaded on demand.
     * @var array<string, UserEmail>|null
     */
    protected ?array $emails = null;

    /**
     * @var string|null Encrypted password. Loaded on demand.
     */
    protected ?string $password = null;

    /**
     * @var string|null The type of encryption. Loaded on demand.
     */
    protected ?string $passwordType = null;

    /**
     * @var string|null Remember token. Loaded on demand.
     */
    protected ?string $rememberToken = null;

    /**
     * @var Carbon|null Loaded on demand
     */
    protected ?Carbon $lastConnect = null;

    /**
     * @var Carbon|null Loaded on demand
     */
    protected ?Carbon $createdAt = null;

    /**
     * @var Carbon|null Loaded on demand
     */
    protected ?Carbon $updatedAt = null;

    /**
     * @var Carbon|null Loaded on demand
     */
    protected ?Carbon $lockedAt = null;

    public function id() : int
    {
        return $this->id;
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
        if (!$this->password) throw new \Error("Attempt to query User's password when it hasn't been loaded.");
        return $this->password;
    }

    /**
     * Get the type of encryption used for a user's password.
     * @return string
     */
    public function getPasswordType(): string
    {
        //This should only be called during authentication where it will have already been loaded.
        if (!$this->passwordType) throw new \Error("Attempt to query User's passwordType when it hasn't been loaded.");
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
        //This should only be called during authentication where it will have already been loaded.
        if (!$this->rememberToken) throw new \Error("Attempt to query User's rememberToken when it hasn't been loaded.");
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

    public static function fromDatabaseResponse(\stdClass $query): User
    {
        if (
            !property_exists($query, 'aid') ||
            !property_exists($query, 'email') ||
            !property_exists($query, 'password')
        ) {
            throw new \InvalidArgumentException('Database response must at least contain aid, password and email');
        }
        $user = new self(intval($query->aid));
        $user->password = $query->password;
        if (property_exists($query, 'password_type')) $user->passwordType = $query->password_type;

        if (property_exists($query, 'created_at') && $query->created_at) $user->createdAt = new Carbon($query->created_at);
        if (property_exists($query, 'updated_at') && $query->updated_at) $user->updatedAt = new Carbon($query->updated_at);
        if (property_exists($query, 'locked_at') && $query->locked_at) $user->lockedAt = new Carbon($query->locked_at);

        $email = new UserEmail($query->email);
        if (property_exists($query, 'email_created_at') && $query->email_created_at) $email->created_at = new Carbon($query->email_created_at);
        if (property_exists($query, 'verified_at') && $query->verified_at) $email->verified_at = new Carbon($query->verified_at);
        $email->isPrimary = true;
        $user->email = $email;

        return $user;
    }
}
