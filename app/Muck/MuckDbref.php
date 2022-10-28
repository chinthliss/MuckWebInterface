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
     * @param int|null $accountId This is just an int to allow lazy loading the account details
     * @param int $staffLevel 1 for 'staff', 2 for 'admin'
     * @param bool $approved
     * @param int|null $level
     */
    public function __construct(
        public int    $dbref,
        public string $name,
        public string $typeFlag,
        public Carbon $createdTimestamp,
        // The rest are only set on certain types
        public ?int   $accountId = null,
        public int    $staffLevel = 0,
        public bool   $approved = true,
        public ?int   $level = null
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
        return $this->staffLevel > 0;
    }

    /**
     * Is this an admin?
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->staffLevel > 1;
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
     * Returns an array representing a player object
     * @return array
     */
    public function toPlayerArray(): array
    {
        if ($this->typeFlag !== 'p') throw new Error("Attempt to get a PlayerArray out of something that isn't a player");
        $array = [
            'dbref' => $this->dbref,
            'name' => $this->name,
            'level' => $this->level,
            'approved' => $this->approved
        ];
        if ($this->staffLevel) $array['staffLevel'] = $this->staffLevel;
        return $array;
    }

    public function is(MuckDbref $otherDbref): bool
    {
        return ($otherDbref->dbref === $this->dbref && $otherDbref->createdTimestamp === $this->createdTimestamp);
    }

}
