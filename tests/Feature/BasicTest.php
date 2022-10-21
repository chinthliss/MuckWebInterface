<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\User;
use Database\Factories\UserFactory;

class BasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_works()
    {
        $user = UserFactory::create();
        $this->assertNotNull($user);
    }

    public function test_welcome_opens_without_login()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }

    public function test_welcome_opens_with_login()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }
}
