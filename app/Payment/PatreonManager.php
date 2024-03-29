<?php


namespace App\Payment;


use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Patreon\API;
use Exception;

class PatreonManager
{
    /**
     * Loaded on demand
     * Indexed in the form [patronId:PatreonPatron]
     * @var PatreonUser[]|null
     */
    private ?array $patrons = null;

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $creatorAccessToken
     * @param string $creatorRefreshToken
     * @param string[] $campaigns
     * @param bool $processRewards Whether to actually process rewards
     */
    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $creatorAccessToken,
        private string $creatorRefreshToken,
        private array  $campaigns,
        private bool   $processRewards
    )
    {
    }

    private function loadFromDatabaseIfRequired(): void
    {
        if ($this->patrons) return;

        Log::debug(self::class . " loading patrons from DB.");
        $this->patrons = [];
        $rows = DB::table('patreon_users')->get();
        foreach ($rows as $row) {
            $patron = PatreonUser::fromDatabase($row);
            $this->patrons[$patron->patronId] = $patron;
        }

        // Join to transactions, where subscriptionId is the campaign and vendorProfileId is the patronId
        $transactionJoin = DB::table('billing_transactions')
            ->select(['subscription_id', 'vendor_profile_id', DB::raw('SUM(amount_usd) as rewarded_usd')])
            ->where('vendor', '=', 'patreon')
            ->whereNotNull('paid_at')
            ->groupBy(['subscription_id', 'vendor_profile_id']);

        $rows = DB::table('patreon_members')
            ->leftJoinSub($transactionJoin, 'transactions', function ($join) {
                $join->on('transactions.subscription_id', '=', 'patreon_members.campaign_id');
                $join->on('transactions.vendor_profile_id', '=', 'patreon_members.patron_id');
            })
            ->get();

        foreach ($rows as $row) {
            $patreonUser = $this->patrons[$row->patron_id];
            $member = PatreonMember::fromDatabase($row, $patreonUser);
            $patreonUser->memberships[$member->campaignId] = $member;
        }


    }

    public function getUserForPatron(PatreonUser $patreonUser): ?User
    {
        return $patreonUser->email ? User::findByEmail($patreonUser->email, true) : null;
    }

    public function getPatronForUser(User $user): ?PatreonUser
    {
        $emails = array_map(function ($emailDetails) {
            return strtolower($emailDetails->email);
        }, $user->getEmails());

        $this->loadFromDatabaseIfRequired();
        foreach ($this->getPatrons() as $patron) {
            if ($patron->email && in_array(strtolower($patron->email), $emails)) return $patron;
        }

        return null;
    }

    private function updateOrCreatePatronFromArray(string $campaignId, string $patronId, array $data): void
    {
        $patron = $this->getPatron($patronId);

        if (!$patron) {
            $patron = new PatreonUser($patronId);
            $this->patrons[$patronId] = $patron;
            $patron->updated = true;
        }

        if (array_key_exists($campaignId, $patron->memberships)) {
            $membership = $patron->memberships[$campaignId];
        } else {
            $membership = new PatreonMember($patron, $campaignId);
            $membership->updated = true;
        }

        // Fix datetimes - only two
        if (array_key_exists('last_charge_date', $data) && $data['last_charge_date'])
            $data['last_charge_date'] = new Carbon($data['last_charge_date']);
        if (array_key_exists('pledge_relationship_start', $data) && $data['pledge_relationship_start'])
            $data['pledge_relationship_start'] = new Carbon($data['pledge_relationship_start']);


        // Potential Patron values
        // NOTE: As of writing, email comes in under the membership instead of on the patron
        $fieldTranslation = [
            'email' => 'email',
            'full_name' => 'fullName',
            'vanity' => 'vanity',
            'hide_pledges' => 'hidePledges',
            'url' => 'url',
            'thumb_url' => 'thumbUrl'
        ];
        foreach ($fieldTranslation as $patreonKey => $ourKey) {
            if (array_key_exists($patreonKey, $data) && $data[$patreonKey] != $patron->$ourKey) {
                //echo "Patron Change {$data[$patreonKey]} vs {$patron->$ourKey} \r\n";
                $patron->$ourKey = $data[$patreonKey];
                $patron->updated = true;
            }
        }

        // Potential Membership values
        $fieldTranslation = [
            'currently_entitled_amount_cents' => 'currentlyEntitledAmountCents',
            'is_follower' => 'isFollower',
            'last_charge_status' => 'lastChargeStatus',
            'last_charge_date' => 'lastChargeDate',
            'lifetime_support_cents' => 'lifetimeSupportCents',
            'patron_status' => 'patronStatus',
            'pledge_relationship_start' => 'pledgeRelationshipStart'
        ];
        foreach ($fieldTranslation as $patreonKey => $ourKey) {
            if (array_key_exists($patreonKey, $data) && $data[$patreonKey] != $membership->$ourKey) {
                //echo "Membership Change {$data[$patreonKey]} vs {$membership->$ourKey} \r\n";
                $membership->$ourKey = $data[$patreonKey];
                $membership->updated = true;
                $patron->updated = true;
            }
        }
    }

    public function updateFromPatreon(): void
    {
        $this->loadFromDatabaseIfRequired();

        Log::info(self::class . ' updating Patreon details from Patreon.');
        $apiClient = new API($this->creatorAccessToken);

        foreach ($this->campaigns as $campaignId) {
            // API has a function for this (fetch_page_of_members_from_campaign) but it doesn't allow scoping.
            $parameters = http_build_query([
                "include" => "user", //Took out currently_entitled_tier
                "fields[member]" => "is_follower,last_charge_date,last_charge_status"
                    . ",lifetime_support_cents,currently_entitled_amount_cents,patron_status,pledge_relationship_start,email",
                "fields[user]" => "email,is_email_verified,thumb_url,hide_pledges,url,vanity,full_name",
                "page[count]" => "100"
            ]);
            $url = "campaigns/$campaignId/members?$parameters";
            while ($url) {
                $response = $apiClient->get_data($url);
                //Go through 'included' which should be the user list
                foreach ($response["included"] as $patron_details) {
                    if ($patron_details["type"] == "user") {
                        $patronId = $patron_details["id"];
                        $this->updateOrCreatePatronFromArray($campaignId, $patronId, $patron_details["attributes"]);
                    } else {
                        Log::warning("Non-user object in 'included' collection. Type=" . $patron_details["type"]);
                    }
                }

                //Go through 'data' which is actually pledges, which we'll now flatten into users.
                foreach ($response["data"] as $pledge_details) {
                    if ($pledge_details["type"] == "member") {
                        $patronId = $pledge_details["relationships"]["user"]["data"]["id"];
                        $this->updateOrCreatePatronFromArray($campaignId, $patronId, $pledge_details["attributes"]);
                    } else {
                        Log::warning("Non-member object in 'data' collection. Type=" . $pledge_details["type"]);
                    }
                }

                if (isset($response["links"]["next"])) {
                    $url = str_replace($apiClient->api_endpoint, '', $response["links"]["next"]);
                } else {
                    $url = null;
                }
            }
        }

        // Look for updated entries
        foreach ($this->patrons as $patron) {
            if ($patron->updated) {
                Log::debug(self::class . " updating/creating " . $patron->patronId);
                $this->savePatron($patron);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function processRewards(): void
    {
        $this->loadFromDatabaseIfRequired();
        $transactionManager = resolve(PaymentTransactionManager::class);
        $phrase = $this->processRewards ? 'is being' : 'would have been';
        Log::debug(self::class . " started reward processing.");
        if (!$this->processRewards) Log::info(self::class . " reward processing is disabled, so only checking for eligibility.");

        foreach ($this->patrons as $patron) {
            $user = $this->getUserForPatron($patron);
            if (!$user) continue;

            foreach ($patron->memberships as $membership) {
                $rewardCents = $membership->lifetimeSupportCents - $membership->rewardedCents;
                if ($rewardCents > 0) {
                    Log::info(self::class . " Reward of $rewardCents cents for Patron#$patron->patronId, campaign#$membership->campaignId $phrase given to user#{$user->id()}.");
                    if ($this->processRewards) {
                        $transaction = $transactionManager->createTransactionForOtherReason(
                            $user,
                            'patreon',
                            $patron->patronId,
                            round($rewardCents / 100.0, 2),
                            round($rewardCents / 100.0, 2) * 2, [],
                            $membership->campaignId
                        );
                        $transactionManager->setPaid($transaction, true);
                        $transaction->accountCurrencyRewarded = $transaction->accountCurrencyQuoted;
                        $transactionManager->fulfillAndCloseTransaction($transaction);
                        $membership->rewardedCents += $rewardCents;
                    }
                }
            }
        }
        Log::debug(self::class . " processRewards finished");
    }

    /**
     * Loads presently known pledges from the database or from memory.
     * Does not update from Patreon.
     * @return PatreonUser[]
     */
    public function getPatrons(): array
    {
        $this->loadFromDatabaseIfRequired();
        return $this->patrons;
    }

    /**
     * @param $patronId
     * @return PatreonUser|null
     */
    public function getPatron($patronId): ?PatreonUser
    {
        Log::debug(self::class . " looking up patron: $patronId");
        $this->loadFromDatabaseIfRequired();
        if (array_key_exists($patronId, $this->patrons)) return $this->patrons[$patronId];
        return null;
    }

    public function savePatron(PatreonUser $patron): void
    {
        Log::debug(self::class . " saving patron to DB: $patron");
        $patron->updatedAt = Carbon::now();
        DB::table('patreon_users')->updateOrInsert(
            ['patron_id' => $patron->patronId],
            $patron->toDatabase()
        );
        $patron->updated = false;

        foreach ($patron->memberships as $membership) {
            if ($membership->updated) {
                $membership->updatedAt = Carbon::now();
                DB::table('patreon_members')->updateOrInsert(
                    ['patron_id' => $patron->patronId, 'campaign_id' => $membership->campaignId],
                    $membership->toDatabase()
                );
                $membership->updated = false;
            }
        }
    }

    /**
     * Utility function to wipe the cache first and force a reload
     */
    public function clearCache(): void
    {
        Log::debug(self::class . " cleared patron cache to force reload.");
        $this->patrons = null;
    }

    /**
     * Loads historic way of saving claims - returned in the form [patronId:[CampaignId:Amount]]
     */
    public function getLegacyClaims(): array
    {
        $results = [];
        $rows = DB::table('patreon_claims')->get();
        foreach ($rows as $row) {
            if (!array_key_exists($row->patron_id, $results)) $results[$row->patron_id] = [];
            $results[$row->patron_id][$row->campaign_id] = $row->claimed_cents;
        }
        return $results;
    }

}
