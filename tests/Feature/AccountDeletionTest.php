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
        $response = $this->post(route('account.delete'));
        $response->assertRedirect();

    }

    public function test_cannot_delete_account_before_required_wait()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('account.delete'));
    }

    public function test_can_delete_account_after_required_wait()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('account.delete'));
        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_cannot_delete_account_after_window_expires()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->post(route('account.delete'));

    }

}
