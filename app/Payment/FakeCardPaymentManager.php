<?php


namespace App\Payment;

use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FakeCardPaymentManager implements CardPaymentManager
{

    /**
     * @var array<int, CardPaymentCustomerProfile>
     */
    private array $customerProfiles = [];

    private function transactionManager(): PaymentTransactionManager
    {
        return resolve(PaymentTransactionManager::class);
    }

    private function subscriptionManager(): PaymentSubscriptionManager
    {
        return resolve(PaymentSubscriptionManager::class);
    }

    private function loadOrCreateProfileFor(User $user): CardPaymentCustomerProfile
    {
        $accountId = $user->id();
        //Return if already fetched
        if (array_key_exists($accountId, $this->customerProfiles)) return $this->customerProfiles[$accountId];

        //Try to actually load one
        $row = DB::table('billing_profiles')->where([
            'aid' => $accountId
        ])->first();

        if ($row?->profileid) {
            $profile = new CardPaymentCustomerProfile($row->profileid);
            $defaultAssigned = false;
            //Try to load cards
            $cardRows = DB::table('billing_paymentprofiles')->where([
                'profileid' => $profile->getCustomerProfileId()
            ])->get();
            foreach ($cardRows as $row) {
                $card = new Card();
                $card->id = $row->id;
                $card->cardType = $row->cardtype;
                $card->cardNumber = $row->maskedcardnum;
                $card->expiryDate = new Carbon($row->expdate);
                $card->isDefault = !$defaultAssigned;
                $profile->addCard($card);
                $defaultAssigned = true;
            }
            // In the faker, first card is the default
        } else $profile = new CardPaymentCustomerProfile(count($this->customerProfiles));

        $this->customerProfiles[$accountId] = $profile;
        return $profile;
    }

    /**
     * @inheritDoc
     */
    public function createCardFor(User $user, string $cardNumber, string $expiryDate, string $securityCode): Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        $card = new Card();
        $card->id = count($profile->getCards());
        $card->cardNumber = $cardNumber;
        // $expiryDate is in the form MM/YYYY
        $parts = explode('/', $expiryDate);
        $card->expiryDate = Carbon::createFromDate($parts[1], $parts[0], 1)->startOfDay();
        $card->cardType = 'Fake';
        $profile->addCard($card);
        $this->setDefaultCardFor($user, $card);
        return $card;
    }

    /**
     * @inheritDoc
     */
    public function deleteCardFor(User $user, Card $card): void
    {
        $profile = $this->loadOrCreateProfileFor($user);
        $profile->removeCard($card);
    }

    /**
     * @inheritDoc
     */
    public function setDefaultCardFor(User $user, Card $defaultCard): void
    {
        foreach ($this->getCardsFor($user) as $card) {
            $card->isDefault = false;
        }
        $defaultCard->isDefault = true;
    }

    /**
     * @inheritDoc
     */
    public function chargeCardFor(User $user, Card $card, PaymentTransaction $transaction): void
    {
        $transactionManager = $this->transactionManager();
        $transactionManager->updateVendorTransactionId($transaction, 'FAKE');
        $transactionManager->setPaid($transaction);
    }

    /**
     * @inheritDoc
     */
    public function getDefaultCardFor(User $user): ?Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile->getDefaultCard();
    }

    /**
     * @inheritDoc
     */
    public function getCardFor(User $user, int $cardId): ?Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile->getCard($cardId);
    }

    /**
     * @inheritDoc
     */
    public function getCardsFor(User $user): array
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile->getCards();
    }

    /**
     * @inheritDoc
     */
    public function getCustomerIdFor(User $user): ?int
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile->getCustomerProfileId();
    }
}
