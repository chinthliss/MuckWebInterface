<?php

namespace App\Muck;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\ArrayShape;

class MuckObjectsProvider
{

    /**
     * Returns the details to allow the MuckObjectService to retrieve a validated dbref
     * @param int $id
     * @return null|array
     */
    #[ArrayShape([
        'dbref' => 'int',
        'created' => 'Carbon\Carbon::class',
        'name' => 'string',
        'deleted' => 'bool'
    ])]
    public function getById(int $id): ?array
    {
        Log::debug("MuckObjectDB - Retrieving object with the id of $id");
        $row = DB::table('muck_objects')
            ->where('id', '=', $id)
            ->first();

        if (!$row) return null;

        // Because we don't presently have a means to handle dbrefs from another game
        if ($row->game_code != config('muck.code')) return null;

        return [
            'dbref' => $row->dbref,
            'created' => new Carbon($row->created_at),
            'name' => $row->name,
            'deleted' => ($row->deleted_at != null)
        ];
    }

    /**
     * Retrieves or creates the associated MuckObjectId for a given dbref
     * @param MuckDbref $muckDbref
     * @return int
     */
    public function getIdFor(MuckDbref $muckDbref): int
    {
        Log::debug("MuckObjectDB - Fetching ID for $muckDbref");

        // Try to find it first
        $row = DB::table('muck_objects')
            ->where('game_code', '=', config('muck.code'))
            ->where('dbref', '=', $muckDbref->dbref)
            ->where('created_at', '=', $muckDbref->createdTimestamp)
            ->first();
        if ($row) {
            Log::debug("MuckObjectDB - Found existing ID of $row->id for $muckDbref");
            return $row->id;
        }

        //Otherwise create an entry
        $type = 'thing';
        switch ($muckDbref->typeFlag) {
            case 'p':
                $type = 'player';
                break;
            case 'z':
                $type = 'zombie';
                break;
            case 'r':
                $type = 'room';
                break;
        }
        $databaseArray = [
            'game_code' => config('muck.code'),
            'dbref' => $muckDbref->dbref,
            'created_at' => $muckDbref->createdTimestamp,
            'type' => $type,
            'name' => $muckDbref->name
        ];

        $id = DB::table('muck_objects')
            ->insertGetId($databaseArray);
        Log::debug("MuckObjectDB - Created new ID of $id for $muckDbref");
        return $id;
    }

    public function removeById(int $id): void
    {
        Log::debug("MuckObjectDB - Remove request for ID: $id");
        $type = DB::table('muck_objects')
            ->where('id', '=', $id)
            ->value('type');
        if ($type) {
            if ($type == 'player') {
                DB::table('muck_objects')
                    ->where('id', '=', $id)
                    ->update(['deleted_at' => Carbon::now()]);
                Log::debug("MuckObjectDB - Flagged player entry as deleted, ID: $id");
            } else {
                DB::table('muck_objects')
                    ->where('id', '=', $id)
                    ->delete();
                Log::debug("MuckObjectDB - Deleted row with ID: $id");
            }
        }
    }

    public function updateName(int $id, string $name): void
    {
        Log::debug("MuckObjectDB - Updating name for $id to: $name");
        DB::table('muck_objects')
            ->where('id', '=', $id)
            ->update(['name' => $name]);
    }
}
