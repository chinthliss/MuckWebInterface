<?php


namespace App\Payment;


/**
 * Customer profile used for card transactions
 */
class CardPaymentCustomerProfile
{
    protected int $id;

    /**
     * @var Card[] Stored as {profileId:Card}
     */
    protected array $cards = [];

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getCustomerProfileId(): int
    {
        return $this->id;
    }

    /**
     * @return array<int, Card[]>
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    public function addCard(Card $card): void
    {
        $this->cards[$card->id] = $card;
    }

    public function removeCard(Card $card): void
    {
        unset($this->cards[$card->id]);
    }

    public function getCard(string $cardId): ?Card
    {
        if (array_key_exists($cardId, $this->cards))
            return $this->cards[$cardId];
        else return null;
    }

    public function getDefaultCard(): ?Card
    {
        foreach ($this->cards as $card) {
            if ($card->isDefault) return $card;
        }
        return null;
    }
}
