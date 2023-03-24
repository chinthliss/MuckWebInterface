<?php

namespace App;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Class MuckWebInterfaceChannel
 * Stores a notification in a database table, to be managed via web or muck.
 */
class MuckWebInterfaceChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        DB::table('account_notifications')->insert($notification->toDatabase($notifiable));
    }
}
