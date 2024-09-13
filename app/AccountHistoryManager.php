<?php

namespace App;

use App\User as User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Handles functionality for the account history table.
 * This is more of an account currency earning/spending log.
 * The actual logging is handled by the muck, so this is effectively a read-only interface
 */
class AccountHistoryManager
{
    /**
     * @return Builder
     */
    private function storageTable(): Builder
    {
        return DB::table('account_history');
    }

    /**
     * @param User $user
     * @return array of {id, game, createdAt, message, balance}
     */
    public function getHistoryFor(User $user): array
    {
        Log::debug(self::class . " getting account history for $user");
        $query = $this->storageTable()
            ->where('aid', '=', $user->id())
            ->orderByDesc('id');
        $rows = $query->get()->toArray();
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => $row->id,
                'game' => $row->game,
                'createdAt' => new Carbon($row->when),
                'message' => $row->message,
                'balance' => (float) $row->balance,
            ];
        }
        return $result;
    }

}
