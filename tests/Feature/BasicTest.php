<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Factories\UserFactory;

class BasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_works()
    {
        $user = UserFactory::create();
        $this->assertNotNull($user);
    }
}
