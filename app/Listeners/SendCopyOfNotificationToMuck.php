<?php

namespace App\Listeners;

use App\Muck\MuckService;
use App\Notifications\MuckWebInterfaceNotification;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Used to send a notification to the muck when a notification occurs.
 * This is to allow the muck to notify a connected player immediately of notifications sent via the web interface
 */
class SendCopyOfNotificationToMuck
{

    public function __construct(
        private MuckService $muck
    )
    {

    }

    /**
     * Handle the event.
     * @param NotificationSent $event
     * @return void
     */
    public function handle(NotificationSent $event): void
    {
        if (!is_a($event, MuckWebInterfaceNotification::class)) return;
        if ($event->notification->gameCode() && $event->notification->gameCode() != config('muck.muck_code')) return;
        $this->muck->externalNotificationSent($event->notifiable,
            $event->notification->character(), $event->notification->message());
    }
}
