<?php


use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\User;
use Database\Factories\UserFactory;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_browser_is_accessible_to_admin()
    {
        $response = $this->actingAs(UserFactory::create(['roles' => 'admin']))->get(route('admin.accounts'));
        $response->assertSuccessful();
    }

    public function test_account_browser_is_not_accessible_to_user()
    {
        $response = $this->actingAs(UserFactory::create())->get(route('admin.accounts'));
        $response->assertForbidden();
    }

    public function test_admin_account_view_is_accessible_to_admin()
    {
        $response = $this->actingAs(UserFactory::create(['roles' => 'admin']))->get(route('admin.account', ['accountId' => 1]));
        $response->assertSuccessful();
    }

    public function test_admin_account_view_is_not_accessible_to_user()
    {
        $response = $this->actingAs(UserFactory::create())->get(route('admin.account', ['accountId' => 1]));
        $response->assertForbidden();
    }
}
