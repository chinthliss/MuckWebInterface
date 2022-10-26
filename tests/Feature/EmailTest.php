<?php

namespace Tests\Feature;

use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Database\Factories\UserFactory;

class EmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_opens_without_verified_email()
    {
        $user = UserFactory::create(['unverified' => true]);
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('welcome');
    }

    public function test_can_open_page_that_requires_verification_if_verified()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('multiplayer.home'));
        $response->assertStatus(200);
        $response->assertViewIs('multiplayer.home');
    }

    public function test_cannot_open_page_that_requires_verification_if_not_verified()
    {
        $user = UserFactory::create(['unverified' => true]);
        $response = $this->actingAs($user)->get(route('multiplayer.home'));
        $response->assertRedirect(route('auth.email.verify'));
    }



}
