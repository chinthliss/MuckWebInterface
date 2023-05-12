<?php

namespace Database\Factories;

use App\Payment\FakeCardPaymentManager;
use App\Payment\PaymentTransactionItem;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Faker\Factory;

/**
 * Utility class to create billing payment entries for testing
 */
class BillingFactory
{
    public static function createBillingProfileFor(User $user): int
    {
        $faker = Factory::create();
        $id = $faker->unique()->numberBetween(1, 10000);
        DB::table('billing_profiles')->insert([
            'aid' => $user->id(),
            'profileid' => $id,
            'defaultcard' => 1,
            'spendinglimit' => 0
        ]);

        return $id;
    }

    public static function createBillingPaymentProfileFor(int $paymentProfileId): int
    {
        $faker = Factory::create();
        $ourId = $faker->unique()->numberBetween(1, 10000);
        $vendorId = $faker->unique()->numberBetween(1, 10000);
        $fakeCard = $faker->creditCardDetails();
        $maskedNum = $fakeCard['number'];
        $date = new Carbon($fakeCard['expirationDate']);
        DB::table('billing_paymentprofiles')->insert([
            'id' => $ourId,
            'profileid' => $paymentProfileId,
            'paymentid' => $vendorId,
            'cardtype' => $fakeCard['type'],
            'maskedcardnum' => $maskedNum,
            'expdate' => $date,
            'firstname' => 'Test',
            'lastname' => 'Test'
        ]);

        return $ourId;
    }

    /**
     * @param User $user
     * @param int|null $usd
     * @param string|null $status
     * @param PaymentTransactionItem[]|null $items
     * @return string
     */
    public static function createPaymentTransactionFor(User    $user,
                                                       ?int    $usd = 0,
                                                       ?string $status = null,
                                                       ?array  $items = []): string
    {
        $faker = Factory::create();
        $manager = resolve(FakeCardPaymentManager::class);
        $id = $faker->uuid();
        $profileId = $manager->getCustomerIdFor($user);
        $array = [
            'id' => $id,
            'account_id' => $user->id(),
            'vendor' => 'fake',
            'vendor_profile_id' => $profileId,
            'amount_usd' => $usd,
            'amount_usd_items' => 0,
            'accountcurrency_rewarded' => 0,
            'accountcurrency_rewarded_items' => 0,
            'accountcurrency_quoted' => $usd * 2,
            'purchase_description' => 'Test Purchase',
            'created_at' => Carbon::now()
        ];
        if ($status === 'paid') {
            $array['paid_at'] = Carbon::now();
        }
        if ($status === 'fulfilled') {
            $array['paid_at'] = Carbon::now();
            $array['result'] = 'fulfilled';
            $array['accountcurrency_rewarded'] = $array['accountcurrency_quoted'];
            $array['completed_at'] = Carbon::now();
        }

        if ($items) {
            $array['items_json'] = json_encode(array_map(function ($item) {
                return $item->toArray();
            }, $items));
        }

        DB::table('billing_transactions')->insert($array);

        return $id;
    }

    public static function createPaymentTransactionItem(string $code, string $name, int $quantity, float $priceUsd): PaymentTransactionItem
    {
        $faker = Factory::create();
        DB::table('billing_itemcatalogue')->insert([
            'code' => $code,
            'name' => $name,
            'description' => $faker->text(),
            'amount_usd' => $priceUsd,
            'supporter' => 0
        ]);
        $item = new PaymentTransactionItem($code, $name, $quantity, $priceUsd * $quantity, $priceUsd * $quantity * 3);
        return $item;
    }
}
