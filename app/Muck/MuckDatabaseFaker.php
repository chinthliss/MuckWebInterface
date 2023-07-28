<?php

namespace App\Muck;

use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;

/**
 * Provides a single point to hold faker muck database during location development
 * Used by the MuckConnectionFaker and Websocket faker service.
 */
class MuckDatabaseFaker
{
    /**
     * Collection of fixed fake data to be used as required
     * @var MuckDbref[]
     */
    private static array $database = [];

    private static function populateDatabaseIfRequired(): void
    {
        if (count(self::$database)) return;
        self::$database[] = new MuckDbref(1234, 'TestCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '10',
            'approved' => '1'
        ]);
        self::$database[] = new MuckDbref(1235, 'TestAltCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '2',
            'approved' => '1'
        ]);
        self::$database[] = new MuckDbref(1236, 'unapprovedCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '1'
        ]);
        self::$database[] = new MuckDbref(1240, 'otherUsersCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$secondNormalUserAccountId),
            'level' => '1'
        ]);
        self::$database[] = new MuckDbref(1300, 'adminCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$adminUserAccountId),
            'staffLevel' => '2'
        ]);
    }

    /**
     * Gets a copy of the present faker database so that it can be passed to the websocket (or analyzed for testing)
     * @return MuckDbref[]
     */
    public static function getDatabase(): array
    {
        self::populateDatabaseIfRequired();
        return self::$database;
    }


}
