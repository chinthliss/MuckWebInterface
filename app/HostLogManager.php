<?php


namespace App;

use App\User as User;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HostLogManager
{

    public function logHost(string $ip, ?User $user): void
    {
        if (!$user) return;
        //Not ideal but we don't want to log proxy entries in production and in testing everything comes from localhost
        if (App::environment() === 'production' && $ip === '127.0.0.1') {
            Log::Debug("Disregarded logging a host because it was from 127.0.0.1, User= $user");
            return;
        }

        $character = $user->getCharacter();
        $hostname = gethostbyaddr($ip);
        DB::table('log_hosts')->updateOrInsert(
            [
                'host_ip' => $ip,
                'aid' => $user->id(),
                'plyr_ref' => $character ? $character->dbref : -1, // To match existing format
                'game_code' => config('muck.code')
            ], [
                'host_name' => $hostname,
                'plyr_name' => $character?->name,
                'plyr_tstamp' => $character?->createdTimestamp->timestamp,
                'tstamp' => Carbon::now()->timestamp
            ]
        );
    }

    public function getLastTimestampForUser(User $user): ?Carbon
    {
        $value = DB::table('log_hosts')->where([
            'aid' => $user->id(),
            'game_code' => config('muck.code')
        ])->value('tstamp');
        return $value ? Carbon::createFromTimestamp($value) : null;
    }
}
