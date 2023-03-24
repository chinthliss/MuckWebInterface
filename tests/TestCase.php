<?php

namespace Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Login as one of the fixed seeded users used for muck testing (seeding needs to have been done first)
     * @return void
     */
    public function loginAsValidatedUser() {
        auth()->loginUsingId(DatabaseSeeder::$normalUserAccountId);
    }

    /**
     * Login as one of the fixed seeded users used for muck testing (seeding needs to have been done first)
     * @return void
     */
    public function loginAsOtherValidatedUser() {
        auth()->loginUsingId(DatabaseSeeder::$secondNormalUserAccountId);
    }

}
