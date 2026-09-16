<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your AuthnAuth password was changed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your password was just changed.')
            ->line("If you made this change, you don't need to do anything.")
            ->line('If you did not make this change, please contact your administrator immediately.');
    }
}
