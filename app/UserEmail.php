<?php

namespace App;

use Carbon\Carbon;

/**
 * Utility class to hold details on an account's emails
 */
class UserEmail
{

    public ?Carbon $created_at = null; // Can be null due to legacy data

    public ?Carbon $verified_at = null;

    public bool $isPrimary = false;

    public function __construct(
        public string $email
    )
    {
    }

}
