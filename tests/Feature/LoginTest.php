<?php

namespace Tests\Feature;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\User;
use Database\Factories\UserFactory;

class LoginTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_page_that_requires_login_does_not_opens_without_login()
    {
        $response = $this->get(route('multiplayer.home'));
        $response->assertRedirect(route('auth.login'));
    }

    public function test_page_that_requires_login_opens_with_login()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('multiplayer.home'));
        $response->assertStatus(200);
        $response->assertViewIs('multiplayer.home');
    }

    public function test_login_redirects_if_logged_in()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('auth.login'));
        $response->assertRedirect(route('welcome'));
    }

    public function test_can_login_with_correct_credentials()
    {
        $user = UserFactory::create();
        $response = $this->json('POST', route('auth.login', [
            'email' => $user->getEmail(),
            'password' => 'password',
            'action' => 'login'
        ]));
        $response->assertRedirect(route('welcome'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_cannot_login_with_incorrect_credentials()
    {
        $response = $this->json('POST', route('auth.login', [
            'email' => 'test@test.com',
            'password' => 'wrong',
            'action' => 'login'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_can_logout()
    {
        $user = UserFactory::create();
        $this->actingAs($user)->get(route('multiplayer.home'));
        $response = $this->post(route('auth.logout'));
        $response->assertRedirect(route('auth.login'));
        $this->assertGuest();
    }

    /**
     * @depends test_can_login_with_correct_credentials
     */
    public function test_login_sets_remember_token()
    {
        $user = UserFactory::create();
        $this->json('POST', route('auth.login', [
            'email' => $user->getEmail(),
            'password' => 'password',
            'action' => 'login'
        ]));
        //Re-fetch user
        $user = auth()->user();
        $this->assertNotNull($user->getRememberToken(), "Remember token was not set.");
    }

    /**
     * @depends test_can_login_with_correct_credentials
     */
    public function test_login_does_not_set_remember_token_if_opted_out()
    {
        $user = UserFactory::create();
        $this->json('POST', route('auth.login', [
            'email' => $user->getEmail(),
            'password' => 'password',
            'forget' => true,
            'action' => 'login'
        ]));
        //Re-fetch user
        $user = auth()->user();
        $this->assertNull($user->getRememberToken(), "Remember token was set.");
    }

    /**
     * @depends test_can_login_with_correct_credentials
     */
    public function test_login_redirect_returns_to_originally_intended_page()
    {
        $this->get(route('account'));
        $user = UserFactory::create();
        $response = $this->json('POST', route('auth.login', [
            'email' => $user->getEmail(),
            'password' => 'password',
            'forget' => true,
            'action' => 'login'
        ]));
        $response->assertRedirect(route('account'));
    }

    public function test_too_many_login_requests_are_throttled()
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->json('POST', route('auth.login', [
                'email' => 'fake@test.com',
                'password' => 'fake'
            ]));
        }
        $response->assertStatus(429);
    }

    public function test_locked_user_is_redirected_to_locked_page()
    {
        $user = UserFactory::create(['locked' => true]);
        $response = $this->actingAs($user)->get(route('multiplayer.home'));
        $response->assertRedirect(route('auth.locked'));
    }

    public function test_unlocked_user_is_redirected_away_from_locked_page()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('auth.locked'));
        $response->assertRedirect(route('welcome'));
    }

    /**
     * @depends test_can_login_with_correct_credentials
     */
    public function test_can_not_use_alternative_email_to_login()
    {
        $user = UserFactory::create(['alternativeEmails' => true]);
        $alternativeEmail = null;
        foreach ($user->getEmails() as $email) {
            if (!$email->isPrimary) $alternativeEmail = $email->email;
        }
        $response = $this->json('POST', route('auth.login', [
            'email' => $alternativeEmail,
            'password' => 'password',
            'action' => 'login'
        ]));
        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_can_login_with_muck_character_and_credentials()
    {
        //This test requires the dev dummy data in since there's no factory for muck characters
        $this->seed();
        $response = $this->json('POST', route('auth.login', [
            'email' => 'TestCharacter',
            'password' => 'muckpassword',
            'action' => 'login'
        ]));
        $response->assertRedirect(route('welcome'));
        $this->assertAuthenticated();
    }

    public function test_cannot_login_with_muck_character_and_incorrect_credentials()
    {
        //This test requires the dev dummy data in since there's no factory for muck characters
        $this->seed();
        $response = $this->json('POST', route('auth.login', [
            'email' => 'TestCharacter',
            'password' => 'wrongmuckpassword',
            'action' => 'login'
        ]));
        $response->assertUnprocessable();
        $this->assertGuest();
    }

    /**
     * @depends test_can_login_with_correct_credentials
     */
    public function test_login_and_attempting_events_fire_on_login()
    {
        Event::fake();
        $user = UserFactory::create();
        $this->json('POST', route('auth.login', [
            'email' => $user->getEmail(),
            'password' => 'password',
            'action' => 'login'
        ]));
        Event::assertDispatchedTimes(Attempting::class, 1);
        Event::assertDispatchedTimes(Login::class, 1);
    }

    /**
     * @depends test_can_login_with_correct_credentials
     */
    public function test_failed_event_fires_on_failed_login()
    {
        Event::fake();
        $user = UserFactory::create();
        $this->json('POST', route('auth.login', [
            'email' => $user->getEmail(),
            'password' => 'wrongpassword',
            'action' => 'login'
        ]));
        Event::assertDispatchedTimes(Failed::class, 1);
    }

    /**
     * @depends test_can_logout
     */
    public function test_logout_event_fires_on_logout()
    {
        Event::fake();
        $user = UserFactory::create();
        $this->actingAs($user);
        $this->post(route('auth.logout'));
        Event::assertDispatchedTimes(Logout::class, 1);
    }
}
