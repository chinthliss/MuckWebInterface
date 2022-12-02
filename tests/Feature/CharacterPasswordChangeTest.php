<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterPasswordChangeTest extends TestCase
{
    public function test_change_character_password_works()
    {
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'password',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertRedirect(route('multiplayer.home'));
        $response->assertSessionHas('message-success');
        $response->assertSessionHasNoErrors();
    }

    public function test_change_character_passwords_does_not_allow_changing_another_users()
    {
        $this->loginAsOtherValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'password',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_change_character_password_does_not_work_with_invalid_password()
    {
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'wrongpassword',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }


}
