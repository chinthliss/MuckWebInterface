<?php


use App\Payment\PaymentTransactionItem;
use App\Payment\PaymentTransactionManager;
use Database\Factories\UserFactory;
use Database\Factories\BillingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_transaction_is_retrieved_okay()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user);

        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertnotnull($transaction);
    }

    public function test_user_can_view_own_transaction()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user);

        $response = $this->actingAs($user)->get(route('account.transaction', [
            'id' => $transactionId
        ]));
        $response->assertSuccessful();
    }

    public function test_user_can_not_view_other_users_transaction()
    {
        $firstUser = UserFactory::create();
        $secondUser = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($secondUser);

        $response = $this->actingAs($firstUser)->get(route('account.transaction', [
            'id' => $transactionId
        ]));
        $response->assertForbidden();
    }

    public function test_user_gets_own_transactions_in_list()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user);
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transactions = $transactionManager->getTransactionsFor($user->id());
        $this->assertArrayHasKey($transactionId, $transactions);
    }

    public function test_user_does_not_see_another_users_transaction_in_list()
    {
        $firstUser = UserFactory::create();
        $secondUser = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($secondUser);
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transactions = $transactionManager->getTransactionsFor($firstUser->id());
        $this->assertArrayNotHasKey($transactionId, $transactions);
    }

    public function test_invalid_transaction_retrieves_null()
    {
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction('00000000-0000-0000-0000-00000000000A');
        $this->assertNull($transaction);
    }

    public function test_cannot_accept_another_users_transaction()
    {
        $firstUser = UserFactory::create();
        $secondUser = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($secondUser);

        $response = $this->actingAs($firstUser)->get(route('account.transaction', [
            'id' => $transactionId
        ]));
        $response->assertForbidden();
    }

    public function test_paid_transaction_can_not_be_accepted()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user, 10, 'paid');

        $response = $this->actingAs($user)->post(route('account.transaction.accept', [
            'id' => $transactionId
        ]));
        $response->assertForbidden();
    }

    public function test_open_transaction_can_be_accepted()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user, 10, 'open');

        $response = $this->actingAs($user)->post(route('account.transaction.accept', [
            'id' => $transactionId
        ]));
        $response->assertSuccessful();
    }

    public function test_open_transaction_can_be_declined()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user, 10, 'open');

        $response = $this->actingAs($user)->post(route('account.transaction.decline', [
            'id' => $transactionId
        ]));
        $response->assertSuccessful();
    }

    public function test_paid_transaction_can_not_be_declined()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user, 10, 'paid');

        $response = $this->actingAs($user)->post(route('account.transaction.decline', [
            'id' => $transactionId
        ]));
        $response->assertSuccessful();
    }

    public function test_completed_transaction_rewards_amount_recorded()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user, 10);

        $response = $this->actingAs($user)->post(route('account.transaction.accept', [
            'id' => $transactionId
        ]));
        $response->assertSuccessful();
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals('fulfilled', $transaction->result, "Transaction status should have been fulfilled");
        $this->assertNotNull($transaction->accountCurrencyRewarded);
    }

    public function test_completed_transaction_with_items_has_items_rewarded()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user, 10, items: ['testItem']);

        $response = $this->actingAs($user)->post(route('account.transaction.accept', [
            'id' => $transactionId
        ]));
        $response->assertSuccessful();
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals('fulfilled', $transaction->result, "Transaction status should have been fulfilled");
        $this->assertNotNull($transaction->accountCurrencyRewardedForItems,
            "Rewarded amount for items not set.");
        $this->assertNotEquals(0, $transaction->accountCurrencyRewardedForItems,
            "Rewarded amount for items shouldn't be 0.");
    }

    public function verifyBaseAmountSavesCorrectly($transactionId): void
    {
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals(10, $transaction->accountCurrencyPriceUsd, "Amount didn't save");
        $this->assertEquals(0, $transaction->itemPriceUsd, "Item price should be 0");
        $this->assertCount(0, $transaction->items, "Items should have been empty");
    }

    private function verifyItemSavesCorrectly($transactionId): void
    {
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals(0, $transaction->accountCurrencyPriceUsd, "Amount should be 0");
        $this->assertNotNull($transaction->itemPriceUsd, "Item price should be set");
        $this->assertNotEquals(0.0, $transaction->itemPriceUsd, "Item price should have a value");
        $this->assertNotCount(0, $transaction->items, "Items array should have an item");
        $item = $transaction->items[0];
        $this->assertTrue(is_a($item, PaymentTransactionItem::class), "Item isn't the right class");
        $this->assertEquals(1, $item->quantity, "Item should have a quantity of 1 set.");
        $this->assertNotEquals(0, $item->accountCurrencyValue, "Item should have a quoted value.");
    }

    public function test_base_amount_on_card_saves_correctly()
    {
        $user = UserFactory::create();
        $profileId = BillingFactory::createBillingProfileFor($user);
        $cardId = BillingFactory::createBillingPaymentProfileFor($profileId);
        $response = $this->actingAs($user)->post('accountcurrency/newCardTransaction', [
            'cardId' => $cardId,
            'amountUsd' => 10.0
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['id'];
        $this->verifyBaseAmountSavesCorrectly($transactionId);
    }

    public function test_base_amount_on_paypal_saves_correctly()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post('accountcurrency/newPayPalTransaction', [
            'amountUsd' => 10.0
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['id'];
        $this->verifyBaseAmountSavesCorrectly($transactionId);
    }

    public function test_items_on_card_save_correctly()
    {
        $user = UserFactory::create();
        $profileId = BillingFactory::createBillingProfileFor($user);
        $cardId = BillingFactory::createBillingPaymentProfileFor($profileId);
        $response = $this->actingAs($user)->post('accountcurrency/newCardTransaction', [
            'cardId' => $cardId,
            'amountUsd' => 0.0,
            'items' => ['TESTITEM']
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['id'];
        $this->verifyItemSavesCorrectly($transactionId);
    }

    public function test_items_on_paypal_save_correctly()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->post('accountcurrency/newPayPalTransaction', [
            'amountUsd' => 0.0,
            'items' => ['TESTITEM']
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['id'];
        $this->verifyItemSavesCorrectly($transactionId);
    }


    public function test_updating_vendor_transaction_id_works_and_persists()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user);

        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $transactionManager->updateVendorTransactionId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->vendorTransactionId == 'NEWTEST', 'VendorTransactionId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertTrue($transaction->vendorTransactionId == 'NEWTEST', 'VendorTransactionId not persisted');
    }

    public function test_updating_vendor_profile_id_works_and_persists()
    {
        $user = UserFactory::create();
        $transactionId = BillingFactory::createPaymentTransactionFor($user);

        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $transactionManager->updateVendorProfileId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->vendorProfileId == 'NEWTEST', 'VendorProfileId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertTrue($transaction->vendorProfileId == 'NEWTEST', 'VendorProfileId not persisted');
    }

    public function test_user_can_view_their_transaction_history()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('account.transactions'));
        $response->assertSuccessful();

    }

    public function test_regular_user_can_not_access_admin_transactions()
    {
        $user = UserFactory::create();
        $response = $this->actingAs($user)->get(route('admin.transactions'));
        $response->assertForbidden();
    }

    public function test_admin_user_can_access_admin_transactions()
    {
        $user = UserFactory::create(['roles' => 'siteadmin']);
        $response = $this->actingAs($user)->get(route('admin.transactions'));
        $response->assertSuccessful();
    }


}
