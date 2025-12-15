<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;
    public function test_can_start_to_delete_account()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $user = auth()->user();

        $response = $this->post(route('account.delete'));
        $this->assertDatabaseHas('account_properties', [
            'aid' => $user->id(),
            'propname' => 'lastDeleteRequest'
        ]);
        $response->assertRedirect();

    }

    public function test_cannot_delete_account_before_required_wait()
    {
        $this->seed();
        $this->loginAsValidatedUser();

        $firstResponse = $this->post(route('account.delete'));
        $firstResponse->assertRedirect();

        $this->travel(1)->minute();

        $secondResponse = $this->post(route('account.delete'));
        $secondResponse->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_can_delete_account_after_required_wait()
    {
        $this->seed();
        $this->loginAsValidatedUser();

        $firstResponse = $this->post(route('account.delete'));
        $firstResponse->assertRedirect();

        $this->travel(1)->day();

        $secondResponse = $this->post(route('account.delete'));
        $secondResponse->assertRedirect();

        $this->assertGuest();
    }

    public function test_cannot_delete_account_after_window_expires()
    {
        $this->seed();
        $this->loginAsValidatedUser();

        $firstResponse = $this->post(route('account.delete'));
        $firstResponse->assertRedirect();

        $this->travel(5)->days();

        $secondResponse = $this->post(route('account.delete'));
        $secondResponse->assertRedirect();

        $this->assertAuthenticated();
    }

}
