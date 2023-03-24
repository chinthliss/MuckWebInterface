<?php

namespace App\Notifications;

use App\MuckWebInterfaceChannel;
use App\Muck\MuckDbref;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class MuckWebInterfaceNotification
 * @package App\Notifications
 */
class MuckWebInterfaceNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private string     $message,
        private ?int       $gameCode = null,
        private ?MuckDbref $character = null
    )
    {

    }

    /**
     * Get the notification's delivery channels.
     * @param object $notifiable
     * @return string
     */
    public function via(object $notifiable): string
    {
        return MuckWebInterfaceChannel::class;
    }

    /**
     * Get the database representation of the notification.
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable): array
    {
        return [
            'aid' => $notifiable->id(),
            'message' => $this->message,
            'game_code' => $this->gameCode,
            'character_dbref' => $this->character?->dbref
        ];
    }

    public function character(): ?MuckDbref
    {
        return $this->character;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function gameCode(): ?int
    {
        return $this->gameCode;
    }

    #region Utilities

    /**
     * Utility to send a message that will be visible on the account through any game
     * @param User $user
     * @param string $message
     */
    public static function notifyAccount(User $user, string $message)
    {
        $user->notify(new MuckWebInterfaceNotification($message));
    }

    /**
     * Utility to send a message that will be visible on any character on this game
     * @param User $user
     * @param string $message
     * @param int|null $gameCode Optional, defaults to this game.
     */
    public static function notifyUser(User $user, string $message, ?int $gameCode = null)
    {
        if (!$gameCode) $gameCode = config('muck.muck_code');
        $user->notify(new MuckWebInterfaceNotification($message, $gameCode));
    }

    /**
     * Utility to send a message that will be visible to a specific character on a game
     * @param User $user
     * @param MuckDbref $character
     * @param string $message
     */
    public static function notifyCharacter(User $user, MuckDbref $character, string $message)
    {
        $user->notify(new MuckWebInterfaceNotification($message, config('muck.muck_code'), $character));
    }

    /**
     * Utility to send a message to either a character, or user if no character is set
     * @param User $user
     * @param MuckDbref|null $character
     * @param string $message
     * @return void
     */
    public static function notifyUserOrCharacter(User $user, ?MuckDbref $character, string $message)
    {
        if ($character)
            self::notifyCharacter($user, $character, $message);
        else
            self::notifyUser($user, $message);
    }
    #endregion Utilities
}
