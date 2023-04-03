<?php


namespace App\Payment;

use Illuminate\Support\Carbon;

/**
 * Utility class to hold a Patreon User's membership of a single campaign
 */
class PatreonMember
{
    public int $currentlyEntitledAmountCents = 0;

    /**
     * @var bool Patreon property
     */
    public ?bool $isFollower = null;

    /**
     * @var string|null Patreon property
     */
    public ?string $lastChargeStatus = null;

    /**
     * @var Carbon|null Patreon property
     */
    public ?Carbon $lastChargeDate = null;

    /**
     * @var int|null Patreon property
     */
    public ?int $lifetimeSupportCents = null;

    /**
     * @var string|null Patreon property
     */
    public ?string $patronStatus = null;

    /**
     * @var Carbon|null Patreon property
     */
    public ?Carbon $pledgeRelationshipStart = null;

    /**
     * @var int How many cents we've already processed a reward for.
     */
    public int $rewardedCents = 0;

    /**
     * @var Carbon|null
     */
    public ?Carbon $updatedAt = null;

    /**
     * @var bool Whether to save to the DB
     */
    public bool $updated = false;

    public function __construct(
        public PatreonUser $patron,
        public string      $campaignId
    )
    {
        $this->patron->memberships[$campaignId] = $this;
    }

    public function toDatabase(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'patron_id' => $this->patron->patronId,
            'currently_entitled_amount_cents' => $this->currentlyEntitledAmountCents,
            'is_follower' => $this->isFollower,
            'last_charge_status' => $this->lastChargeStatus,
            'last_charge_date' => $this->lastChargeDate,
            'lifetime_support_cents' => $this->lifetimeSupportCents,
            'patron_status' => $this->patronStatus,
            'pledge_relationship_start' => $this->pledgeRelationshipStart,
            'updated_at' => $this->updatedAt
        ];
    }

    public static function fromDatabase($row, PatreonUser $patreonUser): PatreonMember
    {
        $member = new PatreonMember($patreonUser, $row->campaign_id);
        $member->currentlyEntitledAmountCents = $row->currently_entitled_amount_cents;
        $member->isFollower = $row->is_follower;
        $member->lastChargeStatus = $row->last_charge_status;
        if ($row->last_charge_date) $member->lastChargeDate = new Carbon($row->last_charge_date);
        $member->lifetimeSupportCents = $row->lifetime_support_cents;
        if ($row->rewarded_usd) $member->rewardedCents = $row->rewarded_usd * 100;
        $member->patronStatus = $row->patron_status;
        if ($row->pledge_relationship_start)
            $member->pledgeRelationshipStart = new Carbon($row->pledge_relationship_start);
        $member->updatedAt = $row->updated_at;
        return $member;
    }
}
