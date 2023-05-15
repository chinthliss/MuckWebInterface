<?php


namespace App\Payment;

use Illuminate\Support\Carbon;

/**
 * Utility class to hold a subscription's details
 */
class PaymentSubscription
{
    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var int|null
     */
    public ?int $accountId = null;

    /**
     * Actual vendor used.
     * @var string|null
     */
    public ?string $vendor = null;

    /**
     * @var string|null
     */
    public ?string $vendorProfileId = null;

    /**
     * @var string|null
     */
    public ?string $vendorSubscriptionId = null;

    /**
     * @var string|null
     */
    public ?string $vendorSubscriptionPlanId = null;

    /**
     * @var float|null The USD value associated with the subscription
     */
    public ?float $amountUsd = null;

    /**
     * @var int|null Frequency in days between payments
     */
    public ?int $recurringInterval = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $createdAt = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $closedAt = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $nextChargeAt = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $lastChargeAt = null;

    /**
     * @var string One of: approval_pending, user_declined, active, suspended, cancelled, expired
     */
    public string $status = 'UNDEFINED';

    /**
     * Non-vendor-specific payment type, such as Card or PayPal
     * @return string
     */
    public function type(): string
    {
        if ($this->vendor === 'paypal') return 'Paypal';
        if ($this->vendor === 'authorizenet') return 'Card';
        return 'Unknown';

    }

    /**
     * @return bool Whether stuff can be done to this subscription
     */
    public function open(): bool
    {
        return !$this->closedAt;
    }

    /**
     * @return Carbon When a subscription will expire unless renewed.
     */
    public function expires(): Carbon
    {
        return $this->lastChargeAt ? $this->lastChargeAt->copy()->addDays($this->recurringInterval + 1)->startOfDay()
            : $this->createdAt;
    }

    /**
     * @return bool Whether a subscription covers 'now' (even if no longer renewing)
     */
    public function active(): bool
    {
        return $this->expires() >= Carbon::now();
    }

    public function renewing(): bool
    {
        return $this->status == 'active';
    }

    /**
     * Produces the array used to offer a user the chance to accept/decline the transaction
     * @return array
     */
    public function toSubscriptionOfferArray(): array
    {
        return [
            "token" => $this->id,
            "purchase" => $this->recurringInterval . " day subscription.",
            "price" => "$" . round($this->amountUsd, 2),
            "note" => "$" . round($this->amountUsd, 2)
                . ' will be recharged every ' . $this->recurringInterval . ' days.'
        ];
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "account_id" => $this->accountId,
            "type" => $this->type(),
            "amount_usd" => $this->amountUsd,
            "recurring_interval" => $this->recurringInterval,
            "status" => $this->status,
            "created_at" => $this->createdAt,
            "next_charge_at" => $this->nextChargeAt,
            "last_charge_at" => $this->lastChargeAt,
            "closed_at" => $this->closedAt,
            "url" => route('account.subscription', ['id' => $this->id])
        ];
    }

    public function toDatabaseArray(): array
    {
        $array = [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'amount_usd' => $this->amountUsd,
            'recurring_interval' => $this->recurringInterval,
            'created_at' => $this->createdAt ?: Carbon::now(),
            'status' => $this->status
        ];
        if ($this->vendor) $array['vendor'] = $this->vendor;
        if ($this->vendorProfileId) $array['vendor_profile_id'] = $this->vendorProfileId;
        if ($this->vendorSubscriptionId) $array['vendor_subscription_id'] = $this->vendorSubscriptionId;
        if ($this->vendorSubscriptionPlanId) $array['vendor_subscription_plan_id'] = $this->vendorSubscriptionPlanId;
        if ($this->closedAt) $array['closed_at'] = $this->closedAt;
        return $array;
    }

}
