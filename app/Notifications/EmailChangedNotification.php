<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class EmailChangedNotification extends Notification
{
    public function __construct(
        private readonly string $oldEmail,
        private readonly string $newEmail,
        private readonly string $actorName,
    ) {}

    /**
     * Send to both the old and the new address — the old one is the one
     * that matters if the change wasn't the owner's doing.
     */
    public static function sendFor(string $oldEmail, User $user, User $actor): void
    {
        NotificationFacade::route('mail', array_unique([$oldEmail, $user->email]))
            ->notify(new self($oldEmail, $user->email, $actor->name));
    }

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
