<?php

namespace Database\Factories;

use App\Muck\MuckDbref;
use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\DB;

/**
 * Utility class to create a new MuckObject for testing purposes
 */
class MuckObjectFactory
{
    /**
     * Returns a muck object, after populating necessary data for other calls
     * @param string $name
     * @return MuckDbref
     */
    public static function createPlayer(string $name): MuckDbref
    {
        $gameCode = config('muck.code');
        if (!$gameCode) throw new Error("muck.code needs to be set during testing.");

        $fixedTime = Carbon::create(2000,1,1, 0, 0, 0 );
        $dbref = fake()->unique()->numberBetween(10000,50000);
        DB::table('muck_objects')->insert([
            'id' => 1,
            'game_code' => $gameCode,
            'dbref' => $dbref,
            'created_at' => $fixedTime,
            'type' => 'player',
            'name' => $name
        ]);
        return new MuckDbref($dbref, $name, 'p', $fixedTime);
    }

}
