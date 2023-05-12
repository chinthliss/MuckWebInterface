<?php


use App\Payment\PaymentSubscriptionManager;
use Database\Factories\UserFactory;
use Database\Factories\BillingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AccountSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_subscription_can_not_be_accepted()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12, 'expired');
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptSubscription', [
            'id' => $subscription->id
        ]);
        $response->assertForbidden();
    }

    public function test_user_can_decline_new_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'id' => $this->validOwnedNewSubscription
        ]);
        $response->assertSuccessful();
    }

    public function test_user_can_accept_new_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptSubscription', [
            'id' => $this->validOwnedNewSubscription
        ]);
        $response->assertSuccessful();
    }

    public function test_user_can_not_accept_another_users_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptSubscription', [
            'id' => $this->validUnownedSubscription
        ]);
        $response->assertForbidden();
    }

    public function test_user_can_not_decline_active_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'id' => $this->validOwnedActiveSubscription
        ]);
        $response->assertForbidden();
    }

    public function test_user_can_cancel_active_card_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/cancelSubscription', [
            'id' => $this->validOwnedActiveSubscription
        ]);
        $response->assertSuccessful();
    }

    public function test_user_can_not_cancel_another_users_active_card_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/cancelSubscription', [
            'id' => $this->validUnownedSubscription
        ]);
        $response->assertForbidden();
    }


    public function test_user_gets_subscriptions_in_list()
    {
        $user = $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscriptions = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $this->assertArrayHasKey($this->validOwnedActiveSubscription, $subscriptions);
        $this->assertArrayHasKey($this->validOwnedNewSubscription, $subscriptions);
        $this->assertArrayHasKey($this->validOwnedClosedSubscription, $subscriptions);
    }

    public function test_user_does_not_get_another_users_subscription_in_list()
    {
        $user = $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscriptions = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $this->assertArrayNotHasKey($this->validUnownedSubscription, $subscriptions);
    }

    public function test_user_can_view_their_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.subscription', [
            'id' => $this->validOwnedActiveSubscription
        ]));
        $response->assertSuccessful();
    }

    public function test_user_can_not_view_another_users_subscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.subscription', [
            'id' => $this->validUnownedSubscription
        ]));
        $response->assertForbidden();
    }

    public function test_updated_vendor_profile_id_updates_and_persists()
    {
        $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwnedActiveSubscription);
        $subscriptionManager->updateVendorProfileId($subscription, 'NEWTEST');
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not updated.');
        //Refetch
        $subscription = $subscriptionManager->getSubscription($this->validOwnedActiveSubscription);
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not persisted');
    }

    public function test_active_and_due_subscription_is_processed()
    {
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndDueSubscription);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) < 5,
            "Subscription's last charge should be approximately now but is: {$subscription->lastChargeAt}");
    }

    public function test_active_subscription_that_has_failed_recently_does_not_run_immediately()
    {
        Config::set('app.process_automated_payments', true);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndFailedSubscription);
        $this->assertNull($subscription->lastChargeAt,
            "Subscription's last charge should be null but is: {$subscription->lastChargeAt}");
    }

    public function test_active_and_due_subscription_is_not_processed_if_disabled()
    {
        Config::set('app.process_automated_payments', false);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndDueSubscription);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) > 5,
            "Subscription's last charge should not be approximately now but is: {$subscription->lastChargeAt}");
    }

}
