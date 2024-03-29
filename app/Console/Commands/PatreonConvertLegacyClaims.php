<?php

namespace App\Console\Commands;

use App\Payment\PatreonManager;
use App\Payment\PaymentTransactionManager;
use App\User;
use Illuminate\Console\Command;

class PatreonConvertLegacyClaims extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patreon:convertlegacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts old patreon_claims entries into billing_transactions';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(PatreonManager $patreonManager, PaymentTransactionManager $transactionManager)
    {
        // This logic should be in the service but this is a one-off transition function
        $this->info('Processing...');
        $patrons = $patreonManager->getPatrons();
        $legacyClaimsByPatronAndCampaign = $patreonManager->getLegacyClaims();
        foreach ($legacyClaimsByPatronAndCampaign as $patronId => $campaignClaims) {
            foreach ($campaignClaims as $campaignId => $cents) {
                $this->info("Campaign $campaignId / Patron $patronId / Cents $cents");
                if (!array_key_exists($patronId, $patrons)) {
                    $this->error("Patron $patronId was missing. Make sure an update from Patreon has actually occurred.");
                    continue;
                }
                $patron = $patrons[$patronId];
                $user = $patron->email ? User::findByEmail($patron->email, true) : null;
                if (!$user) {
                    $this->info('  No associated User, skipping');
                    continue;
                }
                $previousCents = array_key_exists($campaignId, $patron->memberships) ? $patron->memberships[$campaignId]->rewardedCents : 0;
                $dueCents = $cents - $previousCents;
                $this->info("Identified as User#{$user->id()}. Previous claims = $previousCents. Due cents = $dueCents");
                if ($dueCents > 0) {
                    $transaction = $transactionManager->createTransactionForOtherReason(
                        $user,
                        'patreon',
                        $patron->patronId,
                        round($dueCents / 100.0, 2),
                        round($dueCents / 100.0, 2) * 2, [],
                        $campaignId
                    );
                    $transactionManager->setPaid($transaction, true);
                    $transaction->accountCurrencyRewarded = $transaction->accountCurrencyQuoted;
                    $transactionManager->closeTransaction($transaction, 'fulfilled');
                    $patron->memberships[$campaignId]->rewardedCents += $dueCents;
                }
            }
        }
        $this->info('Completed.');
    }
}
