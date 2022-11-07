<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\TermsOfService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use MuckInterop;

class DatabaseSeeder extends Seeder
{
    //These are public statics to allow the muck connection faker to ensure it uses the same IDs.
    public static int $normalUserAccountId = 1;
    public static int $adminUserAccountId = 2;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // *************************************
        // Regular User Account - user@test.com
        DB::table('accounts')->insert([
            'aid' => self::$normalUserAccountId,
            'uuid' => 'fake-uuid-1',
            'email' => 'user@test.com',
            'password' => MuckInterop::createSHA1SALTPassword('password'),
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => self::$normalUserAccountId,
            'email' => 'user@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_emails')->insert([
            'aid' => self::$normalUserAccountId,
            'email' => 'unverifiedAlternative@test.com'
        ]);

        DB::table('account_properties')->insert([
            'aid' => self::$normalUserAccountId,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);

        // *************************************
        // Admin Account - admin@test.com
        DB::table('accounts')->insert([
            'aid' => self::$adminUserAccountId,
            'uuid' => 'fake-uuid-2',
            'email' => 'admin@test.com',
            'password' => MuckInterop::createSHA1SALTPassword('password'),
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => self::$adminUserAccountId,
            'email' => 'admin@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_properties')->insert([
            'aid' => self::$adminUserAccountId,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);
    }
}
