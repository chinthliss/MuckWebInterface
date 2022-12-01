<?php

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HostLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_host_logged_if_not_logged_in()
    {
        $this->get('/');
        $this->assertDatabaseCount('log_hosts', 0);
    }

    public function test_host_logged_if_logged_in_without_character()
    {
        $user = UserFactory::create();
        $this->actingAs($user)->get('/');
        $this->assertDatabaseHas('log_hosts', ['aid' => $user->id()]);
    }

    public function test_host_logged_if_logged_in_with_character()
    {
        //This test requires the dev dummy data in since there's no factory for muck characters
        $this->seed();
        //Logging in with a character should set that character AND log the host
        $this->json('POST', route('auth.login', [
            'email' => 'TestCharacter',
            'password' => 'muckpassword',
            'action' => 'login'
        ]));
        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertDatabaseHas('log_hosts', ['aid' => $user->id(), 'plyr_ref' => 1234]);
    }

}

