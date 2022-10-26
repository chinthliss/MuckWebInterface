<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Factories\UserFactory;

class TermsOfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_of_service_viewable_Without_login()
    {
        $response = $this->get('/account/termsofservice');
        $response->assertSuccessful();
        $response->assertViewIs('auth.terms-of-service');
    }

    public function test_terms_of_service_viewable_with_login()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get('/account/termsofservice');
        $response->assertSuccessful();
        $response->assertViewIs('auth.terms-of-service');
    }

    public function test_user_who_has_accepted_terms_of_service_is_not_redirected()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('multiplayer.home'));
        $response->assertStatus(200);
        $response->assertViewIs('multiplayer.home');
    }

    public function test_user_who_has_not_accepted_terms_of_service_is_redirected()
    {
        $user = UserFactory::create(['notagreedtotos' => true]);
        $response = $this->actingAs($user)->get(route('multiplayer.home'));
        $response->assertRedirect(route('auth.terms-of-service'));
    }

    /**
     * @depends test_user_who_has_not_accepted_terms_of_service_is_redirected
     */
    public function test_terms_of_service_can_be_accepted()
    {
        $user = UserFactory::create(['notagreedtotos' => true]);
        $termsOfService = $this->app->make('App\TermsOfService');
        $hash = $termsOfService::getTermsOfServiceHash();
        $this->post(route('auth.termsofservice'),
            [
                '_token' => csrf_token(),
                '_hash' => $hash
            ]);
        $this->assertDatabaseHas('account_properties', [
            'aid' => $user->id(),
            'propname' => 'tos-hash-viewed',
            'propdata' => $hash
        ]);
    }


}
