<?php

namespace Tests\Feature;

use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Database\Factories\UserFactory;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_works_for_valid_email()
    {
        $user = UserFactory::create();
        $response = $this->json('POST', route('auth.password.forgot', [
            'email' => $user->getEmail()
        ]));
        $response->assertSuccessful();
        $response->assertViewIs('auth.password-reset-sent');
    }

    public function test_password_reset_request_works_for_invalid_email()
    {
        $response = $this->json('POST', route('auth.password.forgot', [
            'email' => 'invalidemail@test.com'
        ]));
        $response->assertSuccessful();
        $response->assertViewIs('auth.password-reset-sent');
    }

    public function test_password_reset_cannot_be_accessed_directly()
    {
        $response = $this->followingRedirects()->get(route('auth.password.reset', ['id' => 1, 'hash' => '1']));
        $response->assertForbidden();
    }

    public function test_password_reset_request_requires_email()
    {
        $response = $this->json('POST', route('auth.password.forgot', []));
        $response->assertUnprocessable();
    }

    public function test_password_reset_request_is_throttled()
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->json('POST', route('auth.password.forgot', []));
        }
        $response->assertStatus(429);
    }

    public function test_reset_password_email_sent_after_requested()
    {
        Notification::fake();
        Notification::assertNothingSent();
        $user = UserFactory::create();
        $this->json('POST', route('auth.password.forgot', [
            'email' => $user->getEmail()
        ]));
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertTimesSent(1, ResetPassword::class);
    }

    /**
     * @depends test_reset_password_email_sent_after_requested
     */
    public function test_reset_password_email_contains_link_to_reset_password()
    {
        Notification::fake();
        $user = UserFactory::create();
        $this->json('POST', route('auth.password.forgot', [
            'email' => $user->getEmail()
        ]));
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $this->assertStringContainsStringIgnoringCase('signature=', $mail['actionUrl']);
            return true;
        });
    }

    /**
     * @depends test_reset_password_email_contains_link_to_reset_password
     */
    public function test_reset_password_link_from_email_provides_access_to_reset_password()
    {
        Notification::fake();
        $user = UserFactory::create();
        $this->json('POST', route('auth.password.forgot', [
            'email' => $user->getEmail()
        ]));
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertSuccessful();
            return true;
        });
    }

    /**
     * @depends test_reset_password_email_contains_link_to_reset_password
     */
    public function test_reset_password_works()
    {
        Notification::fake();
        $user = UserFactory::create();
        $this->json('POST', route('auth.password.forgot', [
            'email' => $user->getEmail()
        ]));
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('POST', $mail['actionUrl'],
                ['password' => 'passwordchanged', 'password_confirmation' => 'passwordchanged']);
            $response->assertSuccessful();
            //Need to re-fetch user to see updated password
            $user = auth()->guard()->getProvider()->retrieveByCredentials(['email' => $user->getEmail()]);
            $this->assertTrue(auth()->guard()->getProvider()->validateCredentials($user, ['password' => 'passwordchanged']));
            $response->assertSuccessful();
            return true;
        });
    }

}
