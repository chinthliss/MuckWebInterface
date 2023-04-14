<?php

namespace App\Notifications;

use App\Payment\PaymentTransaction;

// use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentTransactionPaid extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    public string $purchaseDescription;

    /**
     * @var float
     */
    public float $totalAmountUsd;

    /**
     * @var string
     */
    public string $paymentMethod;

    /**
     * @var string
     */
    public string $transactionId;

    /**
     * @var string|null
     */
    public ?string $subscriptionId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->purchaseDescription = $transaction->purchaseDescription;
        $this->transactionId = $transaction->id;
        $this->subscriptionId = $transaction->subscriptionId;
        $this->totalAmountUsd = $transaction->totalPriceUsd();
        $this->paymentMethod = $transaction->type();
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

    public function transactionUrl(): string
    {
        return route('accountcurrency.transaction', ['id' => $this->transactionId]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = new MailMessage;
        $mail->subject('Receipt of Payment for ' . config('app.name'))
            ->greeting('Payment Receipt')
            ->line('A ' . $this->paymentMethod . ' payment for $'
                . round($this->totalAmountUsd, 2) . '(USD) has been made for the following:')
            ->line($this->purchaseDescription)
            ->action('View Further Details', $this->transactionUrl());

        if ($this->subscriptionId) {
            $mail->line("This payment was made as part of your subscription.");
        }

        $mail->line("Thank you for supporting " . config('app.name') . ".");

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            //
        ];
    }
}
