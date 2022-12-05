<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

/**
 * Tests for whether the active character is saved / loaded between requests
 * These tests frequently require the dev dummy data since there's no factory for muck characters
 */
class ActiveCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_character_set_initially()
    {
        $this->seed();
        $response = $this->get(route('multiplayer.home'));
        $response->assertRedirect();
        $response->assertDontSee('<meta name="character-dbref"');
        //$response->assertSessionMissing('character-dbref');
        $response->assertCookieMissing('character-dbref');
    }

    public function test_saving_active_character_works()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.set'), ['dbref' => '1234']);
        $response->assertRedirect();
        $user = auth()->user();
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
        $response->assertCookie('character-dbref', '1234');

        //On the next call, the character should have loaded again and the header should be set
        $response = $this->withCookie('character-dbref', 1234)->get(route('multiplayer.home'));
        $response->assertOk();
        /** @var User $user */
        $user = auth()->user();
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
        $response->assertCookie('character-dbref');
        $response->assertSee('<meta name="character-dbref" content="1234">', false);
    }

    /**
     * @depends test_saving_active_character_works
     */
    public function test_saving_does_not_save_invalid_character()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.set'), ['dbref' => 2]);
        $response->assertRedirect();
        /** @var User $user */
        $user = auth()->user();
        $this->assertNull($user->getCharacter(), "Character was set on User");
        $response->assertCookieMissing('character-dbref');
    }

    public function test_loading_character_works_from_cookie()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->withCookie('character-dbref', 1234)
            ->get(route('multiplayer.home'));
        $response->assertOk();
        // If the cookie was rejected, it will have been set to expired
        $response->assertCookieNotExpired('character-dbref');
        /** @var User $user */
        $user = auth()->user();
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
    }

    public function test_invalid_character_on_cookie_is_removed()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->withCookie('character-dbref', 2)->get(route('multiplayer.home'));
        $response->assertOk();
        // 'Unset' cookies are set to null but expire, so we need to test it's expired rather than missing
        $response->assertCookieExpired('character-dbref');
    }

    public function test_character_is_set_if_used_to_login()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.login', [
            'email' => 'testCharacter',
            'password' => 'muckpassword',
            'action' => 'login'
        ]));
        $response->assertRedirect();
        $this->assertAuthenticated();
        /** @var User $user */
        $user = auth()->user();
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
        $response->assertCookie('character-dbref');
    }

    public function test_page_that_requires_character_shows_prompt_to_select_character()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->get(route('multiplayer.character'));
        $response->assertRedirect(route('multiplayer.character.required'));
    }

    /**
     * @depends test_page_that_requires_character_shows_prompt_to_select_character
     */
    public function test_redirected_to_intended_page_after_character_selection()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        // Need to try to get intended redirect set
        $response = $this->get(route('multiplayer.character'));
        $response->assertSessionHas('url.intended');
        $secondResponse = $this->post(route('multiplayer.character.set'));
        $secondResponse->assertRedirect(route('multiplayer.character'));
    }

    public function test_page_that_requires_character_works_if_character_set()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => 1234]);
        $response = $this->get(route('multiplayer.character'));
        $response->assertOk();
    }

    public function test_unapproved_character_is_redirected_to_character_generation()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => 1236]);
        $response = $this->get(route('multiplayer.character'));
        $response->assertRedirect(route('multiplayer.character.generate'));
    }

}
