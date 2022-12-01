<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterPasswordChangeTest extends TestCase
{
    public function testChangeCharacterPasswordWorks()
    {
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'password',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertRedirect(route('multiplayer.character.select'));
        $response->assertSessionHas('message-success');
        $response->assertSessionHasNoErrors();
    }

    public function testChangeCharacterPasswordDoesNotAllowChangingAnotherUsers()
    {
        $this->loginAsOtherValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'password',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }

    public function testChangeCharacterPasswordRequiresAccountPassword()
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
