<?php


use App\Payment\PatreonManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Database\Seeders\PatreonSeeder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatreonTest extends TestCase
{
    use refreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed()->seed(PatreonSeeder::class);
    }

    public function test_loading_from_database_works()
    {
        $patreonManager = resolve(PatreonManager::class);
        $pledges = $patreonManager->getPatrons();
        $this->assertNotEmpty($pledges);
    }

    public function test_previous_contributions_calculated_correctly()
    {
        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $previousAmountCents = $patron->memberships[1]->rewardedCents;
        $this->assertTrue($previousAmountCents == 150,
            "Previous claims didn't total correctly. Should have been 150, was $previousAmountCents.");
    }

    public function test_rewards_process_correctly()
    {
        Config::set('app.process_automated_payments', true);

        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals(150, $patron->memberships[1]->rewardedCents);

        $this->artisan('patreon:processrewards')
            ->assertSuccessful();

        $patreonManager->clearCache();
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals($patron->memberships[1]->lifetimeSupportCents, $patron->memberships[1]->rewardedCents);
    }

    /**
     * @depends test_rewards_process_correctly
     */
    public function test_rewards_do_not_process_when_disabled()
    {
        Config::set('app.process_automated_payments', false);

        $this->artisan('patreon:processrewards')
            ->assertSuccessful();

        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $this->assertNotEquals($patron->memberships[1]->lifetimeSupportCents, $patron->memberships[1]->rewardedCents);
    }

    #region Legacy Claims

    public function test_loading_legacy_claims_from_database_works()
    {
        $patreonManager = resolve(PatreonManager::class);
        $claims = $patreonManager->getLegacyclaims();
        $this->assertNotEmpty($claims);
    }

    public function test_legacy_claims_do_not_send_notifications()
    {
        Notification::fake();
        $this->artisan('patreon:convertlegacy')
            ->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_legacy_claims_total_correctly()
    {
        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals(150, $patron->memberships[1]->rewardedCents);

        $this->artisan('patreon:convertlegacy')
            ->assertSuccessful();

        $patreonManager->clearCache();
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals(250, $patron->memberships[1]->rewardedCents);
    }

    #endregion Legacy Claims

}
