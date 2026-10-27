<?php

namespace App\Muck;

use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;

/**
 * Provides a single point to hold faker muck database during local development
 * Used by the MuckConnectionFaker and Websocket faker service.
 */
class MuckDatabaseFaker
{
    /**
     * Collection of fixed fake data to be used as required
     * @var MuckDbref[]
     */
    private static array $database = [];

    // Some constants to help testing muck ANSI
    private const ESCAPE_CHAR = "\x1b";
    private const ANSI_RED = self::ESCAPE_CHAR . "[31m";
    private const ANSI_PURPLE = self::ESCAPE_CHAR . "[35m";
    private const ANSI_RESET = self::ESCAPE_CHAR . "[0m";

    private static function populateDatabaseIfRequired(): void
    {
        if (count(self::$database)) return;
        self::$database[] = new MuckDbref(1234, 'TestCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '10',
            'approved' => '1',
            // Data for character profile testing
            'height' => "5'9\"",
            'sex' => 'Test Gender',
            'species' => '100% Test Species',
            'role' => 'Test Role',
            'faction' => 'Test Faction',
            'group' => 'Test Group',
            'shortDescription' => 'Test Short Description with ' . self::ANSI_PURPLE . 'color' . self::ANSI_RESET . '.',
            'whatIs' => 'Test WI',
            'views' => [ // Ansi allowed
                ['view' => 'Test View with ANSI', 'content' => 'This should be ' . self::ANSI_RED . 'red' . self::ANSI_RESET . '.'],
                ['view' => 'Test View with HTML', 'content' => '<button class="btn btn-primary">This should not be a button</button>']

            ],
            'custom' => [ // No ansi allowed
                ['field' => 'Test Field with ANSI', 'value' => 'This should NOT be ' . self::ANSI_RED . 'red' . self::ANSI_RESET . '.'],
                ['field' => 'Test Field with HTML', 'value' => '<button class="btn btn-primary">This should not be a button</button>']
            ],
            'equipment' => [
                ['name' => 'Test Equipment', 'description' => 'Test Equipment Description']
            ],
            'badges' => [
                ['name' => 'Test Badge 1', 'description' => ['Test Badge Description'], 'awarded' => '2023-06-12 08:49:35 CDT'],
                ['name' => 'Test Badge 2', 'description' => ['Test Badge Description'], 'awarded' => '2023-09-18 23:02:41 CDT'],
                ['name' => 'Test Badge 3', 'description' => ['Test Badge Description'], 'awarded' => '2021-08-04 00:00:00 CDT']
            ]
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
