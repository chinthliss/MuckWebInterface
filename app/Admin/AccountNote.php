<?php

namespace App\Admin;

use Carbon\Carbon;

/**
 * Shallow utility class to hold account note details
 */
class AccountNote
{
    /**
     * @var int
     */
    public int $id;

    /**
     * @var int
     */
    public int $accountId;

    /**
     * @var Carbon
     */
    public Carbon $whenAt;

    /**
     * @var string
     */
    public string $body;

    /**
     * @var string
     */
    public string $staffMember;

    /**
     * @var string
     */
    public string $game;
}
