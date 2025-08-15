<?php

namespace Tests\Feature;

use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Database\Factories\UserFactory;

class AccountCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_account_with_valid_credentials()
    {
        Event::fake();
        Event::assertListening(Registered::class, SendEmailVerificationNotification::class);
        Event::except(Registered::class);

        $this->assertDatabaseMissing('accounts', [
            'email' => 'testnew@test.com'
        ]);
        $this->assertDatabaseMissing('account_emails', [
            'email' => 'testnew@test.com'
        ]);

        $response = $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $response->assertRedirect(route('welcome'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('accounts', [
            'email' => 'testnew@test.com'
        ]);
        // Check a record was made in the email table too
        $this->assertDatabaseHas('account_emails', [
            'email' => 'testnew@test.com'
        ]);
    }

    public function test_cannot_create_account_without_email()
    {
        $response = $this->json('POST', route('auth.create', [
            'password' => 'password'
        ]));
        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_cannot_create_account_without_password()
    {
        $response = $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com'
        ]));
        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_cannot_create_account_with_characters_not_supported_by_muck()
    {
        $response = $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'パスワード' //Google-translated 'password'
        ]));
        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_cannot_create_account_with_existing_email()
    {
        $existingUser = UserFactory::create(['alternativeEmails' => 1]);
        $alternativeEmail = null;
        foreach ($existingUser->getEmails() as $email) {
            if (!$email->isPrimary) $alternativeEmail = $email->email;
        }

        //Test primary email
        $response = $this->json('POST', route('auth.create', [
            'email' => $existingUser->getEmail(),
            'password' => 'password'
        ]));
        $response->assertUnprocessable();
        $this->assertGuest();


        //Test an alternative email
        $response = $this->json('POST', route('auth.create', [
            'email' => $alternativeEmail,
            'password' => 'password'
        ]));
        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_verify_email_sent_after_account_creation()
    {
        Notification::fake();
        Notification::assertNothingSent();
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        Notification::assertSentTo([$user], VerifyEmail::class);
        Notification::assertSentTimes(VerifyEmail::class, 1);
    }

    public function test_created_account_has_remember_token_set_by_default()
    {
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        $this->assertNotEmpty($user->getRememberToken());
    }

    public function test_created_account_does_not_set_remember_token_if_requested()
    {
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password',
            'forget' => true
        ]));
        $user = auth()->user();
        $this->assertEmpty($user->getRememberToken());
    }

    /**
     * @depends test_can_create_account_with_valid_credentials
     */
    public function test_new_account_has_timestamps()
    {
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        $this->assertNotNull($user->getCreatedAt());
        $this->assertNotNull($user->getUpdatedAt());
    }

    /**
     * @depends test_can_create_account_with_valid_credentials
     */
    public function test_referred_account_has_referral_set()
    {
        $accountToRefer = UserFactory::create();
        $this->assertEquals(0, $accountToRefer->getReferralCount());

        //Check initial visit sets referral on the session
        $request = $this->get("/?refer=" . $accountToRefer->id());
        $request->assertSessionHas('account.referral', $accountToRefer->id());

        // Create account and ensures such is saved
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        $referredBy = $user->getAccountProperty('tutor');
        $this->assertEquals($accountToRefer->id(), $referredBy, "Referral account wasn't set.");

        //Check account's referral count is correct
        $this->assertEquals(1, $accountToRefer->getReferralCount());
    }

    /**
     * @depends test_can_create_account_with_valid_credentials
     */
    public function test_registered_and_login_event_fires_on_success()
    {
        Event::fake();
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        Event::assertDispatchedTimes(Registered::class, 1);
        Event::assertDispatchedTimes(Login::class, 1);
    }
}
