<?php


namespace App\Payment;

use App\User;

interface CardPaymentManager
{
    /**
     * @param User $user
     * @param string $cardNumber
     * @param string $expiryDate in the form MM/YYYY
     * @param string $securityCode
     * @return Card
     */
    public function createCardFor(User $user, string $cardNumber, string $expiryDate, string $securityCode): Card;

    /**
     * @param User $user
     * @param Card $card
     */
    public function deleteCardFor(User $user, Card $card): void;

    /**
     * @param User $user
     * @param Card $defaultCard
     * @return void
     */
    public function setDefaultCardFor(User $user, Card $defaultCard): void;

    /**
     * @param User $user
     * @param Card $card
     * @param PaymentTransaction $transaction
     */
    public function chargeCardFor(User $user, Card $card, PaymentTransaction $transaction): void;

    /**
     * @param User $user
     * @return Card|null
     */
    public function getDefaultCardFor(User $user): ?Card;

    /**
     * @param User $user
     * @param int $cardId
     * @return Card|null
     */
    public function getCardFor(User $user, int $cardId): ?Card;

    /**
     * @param User $user
     * @return Card[]
     */
    public function getCardsFor(User $user): array;

    /**
     * @param User $user
     * @return int|null
     */
    public function getCustomerIdFor(User $user): ?int;

}
