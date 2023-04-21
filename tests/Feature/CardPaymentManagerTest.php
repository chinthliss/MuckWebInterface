<?php


use App\Payment\CardPaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Database\Factories\UserFactory;

class CardPaymentManagerTest extends TestCase
{
    private CardPaymentManager $cardPaymentManager;

    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->cardPaymentManager = $this->app->make(CardPaymentManager::class);
    }

    public function test_can_get_customer_id_for_user()
    {
        $user = UserFactory::create();
        $customerId = $this->cardPaymentManager->getCustomerIdFor($user);
        $this->assertNotNull($customerId);
    }

    public function test_user_can_not_add_invalid_card()
    {
        $user = UserFactory::create();
        $startingCardCount = count($this->cardPaymentManager->getCardsFor($user));
        $response = $this->actingAs($user)->json('POST', route('payment.cardmanagement.add', [
            'cardnumber' => '1'
        ]));
        $response->assertStatus(422);
        $this->assertEquals(count($this->cardPaymentManager->getCardsFor($user)), $startingCardCount,
            "Number of cards changed");
    }

    public function test_user_can_add_valid_Card()
    {
        $user = UserFactory::create();
        $monthAhead = Carbon::now()->addMonth();
        $response = $this->actingAs($user)->json('POST', route('payment.cardmanagement.add'), [
            'cardNumber' => '4111111111111111',
            'expiryDate' => $monthAhead->format('m/Y'),
            'securityCode' => '123'
        ]);
        $response->assertSuccessful();
        $this->assertNotEmpty($this->cardPaymentManager->getCardsFor($user));
        $card = $this->cardPaymentManager->getDefaultCardFor($user);
        $this->assertNotNull($card, 'Default card should have been set.');
    }

    public function test_user_can_delete_card()
    {
        $user = UserFactory::create();
        $monthAhead = Carbon::now()->addMonth();
        $this->actingAs($user)->json('POST', route('payment.cardmanagement.delete'), [
            'cardNumber' => '4111111111111111',
            'expiryDate' => $monthAhead->format('m/Y'),
            'securityCode' => '123'
        ]);
        $cards = $this->cardPaymentManager->getCardsFor($user);
        $this->assertNotEmpty($cards, "Failed to add a card to test deleting with");

        $response = $this->json('DELETE', route('payment.cardmanagement.delete'), [
            'id' => $cards[0]->id
        ]);
        $response->assertSuccessful();
        $this->assertEmpty($this->cardPaymentManager->getCardsFor($user));
    }


}
