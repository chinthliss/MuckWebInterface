<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_character_password_works()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'account_password' => 'password',
            'character' => 1234,
            'character_password' => 'newpassword'
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('message-success');
        $response->assertRedirect(route('multiplayer.home'));
    }

    public function test_change_character_passwords_does_not_allow_changing_another_users()
    {
        $this->seed();
        $this->loginAsOtherValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'account_password' => 'password',
            'character' => 1234,
            'character_password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_change_character_password_does_not_work_with_invalid_account_password()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'account_password' => 'wrongpassword',
            'character' => 1234,
            'character_password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_change_character_password_does_not_work_with_invalid_character()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'account_password' => 'password',
            'character' => 2,
            'character_password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_change_character_password_does_not_work_with_bad_character_password()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'account_password' => 'password',
            'character' => 1234,
            'character_password' => 'test'
        ]);
        $response->assertSessionHasErrors();
    }

}
