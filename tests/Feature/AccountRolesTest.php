<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Factories\UserFactory;

class AccountRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_page()
    {
        $user = UserFactory::create(['roles' => 'admin']);
        $response = $this->actingAs($user)->get(route('admin.home'));
        $response->assertSuccessful();
    }

    public function test_regular_user_can_not_access_admin_page()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('admin.home'));
        $response->assertForbidden();
    }

}
