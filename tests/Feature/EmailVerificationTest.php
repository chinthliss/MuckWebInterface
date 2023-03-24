<?php

namespace Tests\Feature;

use App\Notifications\VerifyEmail;
use App\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Database\Factories\UserFactory;

class EmailVerificationTest extends TestCase
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
        $response = $this->actingAs($user)->get(route('multiplayer.guide.starting'));
        $response->assertStatus(200);
        $response->assertViewIs('multiplayer.getting-started');
    }

    public function test_cannot_open_page_that_requires_verification_if_not_verified()
    {
        $user = UserFactory::create(['unverified' => true]);
        $response = $this->actingAs($user)->get(route('multiplayer.home'));
        $response->assertRedirect(route('auth.email.verify'));
    }

    public function test_cannot_access_verification_page_without_account()
    {
        $response = $this->get(route('auth.email.verify'));
        $response->assertRedirect(route('auth.login'));
    }

    public function test_can_access_verification_page_with_account()
    {
        $user = UserFactory::create(['unverified' => true]);
        $response = $this->actingAs($user)->get(route('auth.email.verify'));
        $response->assertViewIs('auth.email-verify');
    }

    public function test_verification_page_redirects_if_verified()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('auth.email.verify'));
        $response->assertRedirect(route('welcome'));
    }

    public function test_verification_email_has_link()
    {
        Notification::fake();
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $this->assertStringContainsStringIgnoringCase('signature=', $mail['actionUrl']);
            return true;
        });
    }

    public function test_new_user_is_not_verified()
    {
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        /** @var User $user */
        $user = auth()->user();
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_new_user_is_verified_after_using_link()
    {
        Notification::fake();
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        /** @var User $user */
        $user = auth()->user();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->get($mail['actionUrl']);
            $response->assertRedirect();
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertEquals($user->getEmail(), 'testnew@test.com');
            return true;
        });
    }

    public function test_verification_link_can_be_resent()
    {
        Notification::fake();
        $user = UserFactory::create(['unverified' => true]);
        $this->actingAs($user)->get(route('auth.email.resendVerification'));
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $this->assertStringContainsStringIgnoringCase('signature=', $mail['actionUrl']);
            return true;
        });
    }

    public function test_verification_works_on_legacy_account_with_incomplete_email_record()
    {
        Notification::fake();
        $user = UserFactory::create(['legacyEmail' => true, 'unverified' => true]);
        $this->assertDatabaseMissing('account_emails', [
            'email' => $user->getEmail()
        ]);
        $this->actingAs($user)->get(route('auth.email.resendVerification'));
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->get($mail['actionUrl']);
            $response->assertRedirect();
            $this->assertDatabaseHas('account_emails', [
                'email' => $user->getEmail()
            ]);
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertEquals($user->getEmailForVerification(), $user->getEmail());
            return true;
        });
    }

    /**
     * @depends test_new_user_is_verified_after_using_link
     */
    public function test_verification_event_fires_on_verification()
    {
        Notification::fake();
        $this->json('POST', route('auth.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        /** @var User $user */
        $user = auth()->user();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, $channels) use ($user) {
            Event::fake();
            $mail = $notification->toMail($user)->toArray();
            $response = $this->get($mail['actionUrl']);
            Event::assertDispatchedTimes(Verified::class, 1);
            return true;
        });
    }

}
