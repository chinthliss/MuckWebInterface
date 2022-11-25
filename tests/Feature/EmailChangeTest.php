<?php

namespace Tests\Feature;

use App\Notifications\VerifyEmail;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Database\Factories\UserFactory;

class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_email_not_accessible_without_login()
    {
        $response = $this->get(route('auth.email.new'));
        $response->assertRedirect(route('auth.login'));
    }

    public function test_new_email_accessible_with_login()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('auth.email.new'));
        $response->assertSuccessful();
        $response->assertViewIs('auth.email-new');
    }

    public function test_new_email_works()
    {
        $user = UserFactory::create();
        $userId = $user->id();
        $newEmail = 'newemail@email.com';
        $response = $this->actingAs($user)->post(route('auth.email.new'), [
            'email' => $newEmail,
            'password' => 'password'
        ]);
        $response->assertSuccessful();
        $response->assertViewIs('auth.email-change-processed');
        //Test user can be fetched with updated email
        /** @var User $user */
        $user = auth()->guard()->getProvider()->retrieveByCredentials(['email' => $newEmail]);
        $this->assertEquals($userId, $user->id());
        $this->assertEquals($user->getEmail(), $newEmail);
    }

    public function test_new_email_request_requires_valid_existing_password()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.email.new'), [
            'email' => 'newemail@email.com',
            'password' => 'wrongpassword'
        ]);
        $response->assertInvalid(['password' => 'existing password']);
    }

    public function test_new_email_request_does_not_work_with_existing_primary_email()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.email.new'), [
            'email' => $user->getEmail(),
            'password' => 'password'
        ]);
        $response->assertInvalid(['email' => 'already in use']);
    }

    public function test_new_email_request_does_not_work_with_existing_alternative_email()
    {
        $user = UserFactory::create(['alternativeEmails' => 1]);
        $alternativeEmail = null;
        foreach ($user->getEmails() as $email) {
            if (!$email->isPrimary) $alternativeEmail = $email->email;
        }

        $response = $this->actingAs($user)->post(route('auth.email.new'), [
            'email' => $alternativeEmail,
            'password' => 'password'
        ]);
        $response->assertInvalid(['email' => 'already in use']);
    }


    public function test_new_email_request_sends_verification()
    {
        Notification::fake();
        $user = UserFactory::create();
        $this->actingAs($user)->post(route('auth.email.new'), [
            'email' => 'newemail@test.com',
            'password' => 'password'
        ]);
        $user = auth()->user();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $this->assertStringContainsStringIgnoringCase('signature=', $mail['actionUrl']);
            return true;
        });
    }

    public function test_change_email_request_works()
    {
        $user = UserFactory::create(['alternativeEmails' => 1]);
        $alternativeEmail = null;
        foreach ($user->getEmails() as $email) {
            if (!$email->isPrimary) $alternativeEmail = $email->email;
        }
        $response = $this->actingAs($user)->post(route('auth.email.change'), [
            'email' => $alternativeEmail,
        ]);
        $response->assertViewIs('auth.email-change-processed');
    }

    public function test_change_email_requires_email_existing()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.email.change'), [
            'email' => 'someotheremail@test.com',
        ]);
        $response->assertServerError();
    }

    public function test_change_email_requires_email_on_same_account()
    {
        $user = UserFactory::create();
        $otherUser = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.email.change'), [
            'email' => $otherUser->getEmail(),
        ]);
        $response->assertServerError();
    }

}
