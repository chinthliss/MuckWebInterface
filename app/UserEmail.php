<?php

namespace App;

use Carbon\Carbon;
use stdClass;

/**
 * Utility class to hold details on an account's emails
 */
class UserEmail
{

    public ?Carbon $createdAt = null; // Can be null due to legacy data

    public ?Carbon $verifiedAt = null;

    public bool $isPrimary = false;

    public function __construct(
        public string $email
    )
    {
    }

    public static function fromDatabaseResponse(stdClass $row): UserEmail
    {
        $email = new self($row->email);
        if ($row->verified_at) $email->verifiedAt = new Carbon($row->verified_at);
        if ($row->created_at) $email->created_at = new Carbon($row->created_at);
        return $email;
    }

}
