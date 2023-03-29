<?php


namespace App;

use App\User as User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AccountNotificationManager
{

    /**
     * @return Builder
     */
    private function storageTable(): Builder
    {
        return DB::table('account_notifications');
    }

    /**
     * @param User $user
     * @return array Array of [character, created_at, read_at, message]
     */
    public function getNotificationsFor(User $user): array
    {
        $characters = $user->getCharacters();
        $query = $this->storageTable()
            ->where('aid', '=', $user->id())
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->orderByDesc('created_at');
        $rows = $query->get()->toArray();
        $query->update(['read_at' => Carbon::now()]);

        $result = [];
        foreach ($rows as $row) {
            $character = array_key_exists($row->character_dbref, $characters) ? $characters[$row->character_dbref] : null;
            $result[] = [
                'id' => $row->id,
                'character' => $character,
                'created_at' => $row->created_at,
                'read_at' => $row->read_at,
                'message' => $row->message
            ];
        }
        Log::debug("Account Notifications - getNotificationsFor Account#{$user->id()}: " . count($result));
        return $result;
    }

    public function getNotification(int $id): object
    {
        return $this->storageTable()->where('id', '=', $id)->first();
    }

    public function deleteNotification($id): void
    {
        $this->storageTable()->delete($id);
    }

    /**
     * Takes a parameter for 'highest seen ID' to prevent deleting notifications that may have arrived since the user last looked
     * @param User $user
     * @param int $highestId
     * @return void
     */
    public function deleteAllNotificationsFor(User $user, int $highestId): void
    {
        $this->storageTable()
            ->where('aid', '=', $user->id())
            ->where('id', '<=', $highestId)
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->delete();
    }

    /**
     * Returns the count of unread notifications, for the navbar and the likes.
     * @param User $user
     * @return int
     */
    public function getUnreadNotificationsCountFor(User $user): int
    {
        $count = $this->storageTable()
            ->where('aid', '=', $user->id())
            ->whereNull('read_at')
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->count();
        Log::debug("AccountNotifications - getNotificationCountFor Account#{$user->id()} = $count");
        return $count;
    }
}
