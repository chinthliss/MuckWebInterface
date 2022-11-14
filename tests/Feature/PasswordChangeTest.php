<?php

namespace Tests\Feature;

use App\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Database\Factories\UserFactory;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_not_accessible_without_login()
    {
        $response = $this->get(route('auth.password.change'));
        $response->assertRedirect(route('auth.login'));
    }

    public function test_change_password_accessible_with_login()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('auth.password.change'));
        $response->assertSuccessful();
        $response->assertViewIs('auth.password-change');
    }

    public function test_change_password_works()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.password.change'), [
            'oldpassword' => 'password',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ]);
        $response->assertSuccessful();
        $response->assertViewIs('auth.password-change-processed');
    }

    public function test_new_password_must_not_equal_old_password()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.password.change'), [
            'oldpassword' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $response->assertInvalid(['password' => 'existing password']);
    }

    public function test_change_password_requires_valid_existing_password()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post(route('auth.password.change'), [
            'oldpassword' => 'wrongpassword',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $response->assertInvalid(['oldpassword' => 'existing password']);
    }

    //TODO : Other change password tests


}
