<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangedNotification extends Notification
{
    public function __construct(
        private readonly string $oldEmail,
        private readonly string $newEmail,
        private readonly string $actorName,
    ) {}

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
            ->subject('Your AuthnAuth account email was changed')
            ->greeting('Hello,')
            ->line("The email on this account was changed from {$this->oldEmail} to {$this->newEmail} by {$this->actorName}.")
            ->line('If you expected this change, you don\'t need to do anything.')
            ->line('If you did not expect this change, please contact your administrator immediately.');
    }
}
