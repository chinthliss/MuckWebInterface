<?php


namespace App;

use App\User as User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Handles most functionality related to AccountNotifications.
 * The actual sending functions are on the notification itself, so they can be used anywhere.
 */
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
     * Gets all notifications for a user, marking them as read in doing so by default
     * @param User $user
     * @param bool $markAsRead Whether to set notifications as read as par tof this
     * @return array Array of [character, created_at, read_at, message]
     */
    public function getNotificationsFor(User $user, bool $markAsRead = true): array
    {
        Log::debug(self::class . " getting notifications for $user");
        $characters = $user->getCharactersIndexedByDbref();
        $query = $this->storageTable()
            ->where('aid', '=', $user->id())
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->orderByDesc('created_at');
        $rows = $query->get()->toArray();

        if ($markAsRead) $query->update(['read_at' => Carbon::now()]);

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
        Log::debug(self::class . " got " . count($result) . " notifications got for $user");
        return $result;
    }

    /**
     * Get a single notification by ID
     * @param int $id
     * @return object
     */
    public function getNotification(int $id): object
    {
        Log::debug(self::class . " getting notification with the id of $id");
        return $this->storageTable()->where('id', '=', $id)->first();
    }

    /**
     * Delete a single notification by ID
     * @param $id
     * @return void
     */
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
        Log::debug(self::class . " deleting all notifications for $user, up to ID $highestId");
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
        Log::debug(self::class . " getting unread notifications for $user");
        $count = $this->storageTable()
            ->where('aid', '=', $user->id())
            ->whereNull('read_at')
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->count();
        Log::debug(self::class . " found $count unread for $user");
        return $count;
    }
}
