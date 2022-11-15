<?php

namespace Tests\Feature;

use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Database\Factories\UserFactory;

class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_email_not_accessible_without_login()
    {
        $response = $this->get(route('auth.email.change'));
        $response->assertRedirect(route('auth.login'));
    }

    public function test_change_email_accessible_with_login()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('auth.email.change'));
        $response->assertSuccessful();
        $response->assertViewIs('auth.email-change');
    }

    public function test_change_email_works()
    {
        $user = UserFactory::create();
        $userId = $user->id();
        $newEmail = 'newemail@email.com';
        $response = $this->actingAs($user)->post(route('auth.email.change'), [
            'email' => $newEmail,
            'password' => 'password'
        ]);
        $response->assertSuccessful();
        $response->assertViewIs('auth.email-change-processed');
        //Test used can be fetched with updated email
        $user = auth()->guard()->getProvider()->retrieveByCredentials(['email' => $newEmail]);
        $this->assertEquals($userId, $user->id());
        $this->assertEquals($user->getEmail(), $newEmail);
    }

    public function test_email_change_requires_valid_existing_password()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.email.change'), [
            'email' => 'newemail@email.com',
            'password' => 'wrongpassword'
        ]);
        $response->assertInvalid(['password' => 'existing password']);
    }

    public function test_email_change_does_not_work_with_existing_primary_email()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.email.change'), [
            'email' => $user->getEmail(),
            'password' => 'password'
        ]);
        $response->assertInvalid(['email' => 'already in use']);
    }

    public function test_email_change_does_not_work_with_existing_alternative_email()
    {
        $user = UserFactory::create(['alternativeEmails' => 1]);
        $alternativeEmail = null;
        foreach ($user->getEmails() as $email) {
            if (!$email->isPrimary) $alternativeEmail = $email->email;
        }

        $response = $this->actingAs($user)->post(route('auth.email.change'), [
            'email' => $alternativeEmail,
            'password' => 'password'
        ]);
        $response->assertInvalid(['email' => 'already in use']);
    }


    public function test_email_change_sends_verification()
    {
        Notification::fake();
        $user = UserFactory::create();
        $this->actingAs($user)->post(route('auth.email.change'), [
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
}
