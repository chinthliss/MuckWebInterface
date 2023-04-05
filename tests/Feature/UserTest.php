<?php

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use refreshDatabase;

    public function test_get_id_works()
    {
        $user = UserFactory::create();
        $this->assertNotNull($user->id());
    }

    public function test_get_account_currency_works()
    {
        $user = UserFactory::create(['accountCurrency' => 5]);
        $this->assertEquals(5, $user->getAccountCurrency());
    }

    public function test_get_account_flags_works_when_there_are_no_flags()
    {
        $user = UserFactory::create();
        $flags = $user->getAccountFlags();
        $this->assertEmpty($flags);
    }

    public function test_get_account_flags_works()
    {
        $user = UserFactory::create(['accountFlags' => ['test_flag']]);
        $flags = $user->getAccountFlags();
        $this->assertNotEmpty($flags);
        $this->assertContains('test_flag', $flags);
    }
}
