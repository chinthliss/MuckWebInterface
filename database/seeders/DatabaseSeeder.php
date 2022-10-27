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
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // *************************************
        // Regular User Account - user@test.com
        $accountId = 1;
        DB::table('accounts')->insert([
            'aid' => $accountId,
            'uuid' => 'fake-uuid-1',
            'email' => 'user@test.com',
            'password' => MuckInterop::createSHA1SALTPassword('password'),
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => $accountId,
            'email' => 'user@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_properties')->insert([
            'aid' => $accountId,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);

        // *************************************
        // Admin Account - admin@test.com
        $accountId = 2;
        DB::table('accounts')->insert([
            'aid' => $accountId,
            'uuid' => 'fake-uuid-2',
            'email' => 'admin@test.com',
            'password' => MuckInterop::createSHA1SALTPassword('password'),
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => $accountId,
            'email' => 'admin@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_properties')->insert([
            'aid' => $accountId,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);
    }
}
