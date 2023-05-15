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
        $response = $this->actingAs($user)->post(route('account.subscription.accept', [
            'id' => $subscription->id
        ]));
        $response->assertForbidden();
    }

    public function test_user_can_accept_new_subscription()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);
        $response = $this->actingAs($user)->post(route('account.subscription.accept', [
            'id' => $subscription->id
        ]));
        $response->assertRedirect(route('account.subscription', [
            'id' => $subscription->id
        ]));
    }

    public function test_user_can_decline_new_subscription()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12, 'approval_pending');
        $response = $this->actingAs($user)->post(route('account.subscription.decline', [
            'id' => $subscription->id
        ]));
        $response->assertSuccessful();
    }

    public function test_user_can_not_accept_another_users_subscription()
    {
        $firstUser = UserFactory::create();
        $secondUser = UserFactory::create();
        $subscription = BillingFactory::createSubscription($secondUser, 5, 12, 'approval_pending');
        $response = $this->actingAs($firstUser)->post(route('account.subscription.accept', [
            'id' => $subscription->id
        ]));
        $response->assertForbidden();
    }

    public function test_user_can_not_decline_active_subscription()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12 );
        $response = $this->actingAs($user)->post(route('account.subscription.decline', [
            'id' => $subscription->id
        ]));
        $response->assertForbidden();
    }

    public function test_user_can_cancel_active_subscription()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);
        $response = $this->actingAs($user)->post(route('account.subscription.cancel', [
            'id' => $subscription->id
        ]));
        $response->assertSuccessful();
    }

    public function test_user_can_not_cancel_another_users_active_subscription()
    {
        $firstUser = UserFactory::create();
        $secondUser = UserFactory::create();
        $subscription = BillingFactory::createSubscription($secondUser, 5, 12);

        $response = $this->actingAs($firstUser)->post(route('account.subscription.cancel', [
            'id' => $subscription->id
        ]));
        $response->assertForbidden();
    }


    public function test_user_gets_subscriptions_in_list()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);

        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscriptions = $subscriptionManager->getSubscriptionsFor($user->id());
        $this->assertArrayHasKey($subscription->id, $subscriptions);
    }

    public function test_user_does_not_get_another_users_subscription_in_list()
    {
        $firstUser = UserFactory::create();
        $secondUser = UserFactory::create();
        $subscription = BillingFactory::createSubscription($secondUser, 5, 12);

        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscriptions = $subscriptionManager->getSubscriptionsFor($firstUser->id());
        $this->assertArrayNotHasKey($subscription->id, $subscriptions);
    }

    public function test_user_can_view_their_subscription()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);

        $response = $this->actingAs($user)->followingRedirects()->get(route('account.subscription', [
            'id' => $subscription->id
        ]));
        $response->assertSuccessful();
    }

    public function test_user_can_not_view_another_users_subscription()
    {
        $firstUser = UserFactory::create();
        $secondUser = UserFactory::create();
        $subscription = BillingFactory::createSubscription($secondUser, 5, 12);

        $response = $this->actingAs($firstUser)->followingRedirects()->get(route('account.subscription', [
            'id' => $subscription->id
        ]));
        $response->assertForbidden();
    }

    public function test_updated_vendor_profile_id_updates_and_persists()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);

        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($subscription->id);
        $subscriptionManager->updateVendorProfileId($subscription, 'NEWTEST');
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not updated.');
        //Refetch
        $subscription = $subscriptionManager->getSubscription($subscription->id);
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not persisted');
    }

    public function test_active_and_due_subscription_is_processed()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);

        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($subscription->id);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) < 5,
            "Subscription's last charge should be approximately now but is: {$subscription->lastChargeAt}");
    }

    public function test_active_subscription_that_has_failed_recently_does_not_run_immediately()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);

        Config::set('app.process_automated_payments', true);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($subscription->id);
        $this->assertNull($subscription->lastChargeAt,
            "Subscription's last charge should be null but is: {$subscription->lastChargeAt}");
    }

    public function test_active_and_due_subscription_is_not_processed_if_disabled()
    {
        $user = UserFactory::create();
        $subscription = BillingFactory::createSubscription($user, 5, 12);

        Config::set('app.process_automated_payments', false);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($subscription->id);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) > 5,
            "Subscription's last charge should not be approximately now but is: {$subscription->lastChargeAt}");
    }

}
