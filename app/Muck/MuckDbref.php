<?php


namespace App\Muck;

use Error;
use Carbon\Carbon;

/**
 * Utility class to represent a loaded muck dbRef.
 */
class MuckDbref
{
    /**
     * @var string[] Valid type flags
     */
    public static array $typeFlags = [
        'p' => 'player',
        'z' => 'zombie',
        'r' => 'room',
        't' => 'thing'
    ];

    /**
     * @param int $dbref Object's dbref as an integer
     * @param string $name
     * @param string $typeFlag
     * @param Carbon $createdTimestamp The created timestamp - in conjunction with the dbref acts as a signature since dbrefs can be reused
     * @param array $properties Additional properties, normally unique to the type of object
     */
    public function __construct(
        public int    $dbref,
        public string $name,
        public string $typeFlag,
        public Carbon $createdTimestamp,
        public array  $properties = []
    )
    {
        if (!array_key_exists($this->typeFlag, self::$typeFlags)) {
            throw new Error('Unrecognized type flag specified: ' . $this->typeFlag);
        }
    }

    public function __toString(): string
    {
        return $this->name . '(#' . $this->dbref . $this->typeFlag . ')';
    }

    /**
     * Is this a player?
     * @return bool
     */
    public function isPlayer(): bool
    {
        return $this->typeFlag == 'p';
    }

    /**
     * Is this staff?
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->isPlayer() && array_key_exists('staffLevel', $this->properties) && $this->properties['staffLevel'] > 0;
    }

    /**
     * Is this an admin?
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isPlayer() && array_key_exists('staffLevel', $this->properties) && $this->properties['staffLevel'] > 1;
    }

    /**
     * This object's level, if applicable and set
     * @return int
     */
    public function level(): int
    {
        return array_key_exists('level', $this->properties) ? (int)$this->properties['level'] : 0;
    }

    /**
     * Account id owning this object, if applicable and set
     * @return int|null
     */
    public function accountId(): ?int
    {
        return array_key_exists('accountId', $this->properties) ? (int)$this->properties['accountId'] : null;
    }

    /**
     * If this is a player, have they finished character generation?
     * @return bool
     */
    public function approved(): bool
    {
        return array_key_exists('approved', $this->properties) && $this->properties['approved'] == '1';
    }

    /**
     * Basic array implement of a muck object
     * @return array
     */
    public function toArray(): array
    {
        return [
            'dbref' => $this->dbref,
            'type' => $this->typeFlag,
            'name' => $this->name,
            'created' => $this->createdTimestamp
        ];
    }

    /**
     * Returns a public array representing a player object
     * @return array
     */
    public function toPlayerArray(): array
    {
        if (!$this->isPlayer()) throw new Error("Attempt to get a PlayerArray out of something that isn't a player");
        $array = [
            'dbref' => $this->dbref,
            'name' => $this->name,
            'level' => $this->level(),
            'approved' => $this->approved()
        ];
        if (array_key_exists('staffLevel', $this->properties)) $array['staffLevel'] = (int)$this->properties['staffLevel'];
        return $array;
    }

    public function is(MuckDbref $otherDbref): bool
    {
        return ($otherDbref->dbref === $this->dbref && $otherDbref->createdTimestamp === $this->createdTimestamp);
    }

}
