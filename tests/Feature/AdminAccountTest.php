<?php


use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\User;
use Database\Factories\UserFactory;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_is_working()
    {
        $user = UserFactory::create(['roles' => 'admin']);
        $this->assertTrue($user->isAdmin());
    }

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
        $accountToView = UserFactory::create();
        $admin = UserFactory::create(['roles' => 'admin']);
        $response = $this->actingAs($admin)
            ->get(route('admin.account', ['accountId' => $accountToView->id()]));
        $response->assertSuccessful();
    }

    public function test_admin_account_view_is_not_accessible_to_user()
    {
        $accountToView = UserFactory::create();
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('admin.account', ['accountId' => $accountToView->id()]));
        $response->assertForbidden();
    }

    public function test_locking_account_persists()
    {
        $user = UserFactory::create();
        $admin = UserFactory::create(['roles' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.account.api', [
            "accountId" => $user->id(),
            "operation" => "lock"
        ]));

        $response->assertSuccessful();
        $this->assertDatabaseHas('accounts', [
            'aid' => $user->id(),
            'locked_at' => Carbon::now()
        ]);
        $user = User::find($user->id());
        $this->assertNotNull($user->getLockedAt());
    }
}
