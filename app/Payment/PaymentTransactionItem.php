<?php


namespace App\Payment;

/**
 * Holds the information for an item bought as part of a transaction
 * @package App\Payment
 */
class PaymentTransactionItem
{
    /**
     * @param string $code Short code for the item
     * @param string $name Name for an item. Saved in case the name changes at some point.
     * @param int $quantity Price used for ONE item
     * @param float $priceUsd
     * @param int $accountCurrencyValue How much this would be worth - IF the item rewards such. Recorded since even if it doesn't earn account currency, it may earn supporter points for such.
     */
    public function __construct(
        public string $code,
        public string $name,
        public int    $quantity,
        public float  $priceUsd,
        public int    $accountCurrencyValue
    )
    {
    }

    public static function fromArray($array): PaymentTransactionItem
    {
        return new self(
            $array->code,
            $array->name,
            $array->quantity,
            $array->priceUsd,
            $array->accountCurrencyValue
        );
    }

    public function toArray(): array
    {
        return [
            "code" => $this->code,
            "name" => $this->name,
            "priceUsd" => $this->priceUsd,
            "quantity" => $this->quantity,
            "accountCurrencyValue" => $this->accountCurrencyValue
        ];
    }
}
