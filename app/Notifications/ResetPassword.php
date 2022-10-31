<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Log;

class ResetPassword extends Notification // implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        Log::debug("Password reset - new temporary URL created: $resetUrl");
        return (new MailMessage)
            ->subject('Reset Password Request')
            ->line('Please click the button below to reset your password.')
            ->action('Reset Password', $resetUrl)
            ->line('If you did not create an account, no further action is required.');
    }

    protected function resetUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'auth.password.reset',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmail())
            ]
        );
    }
}
