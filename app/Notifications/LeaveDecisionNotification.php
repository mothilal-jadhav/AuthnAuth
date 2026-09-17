<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveDecisionNotification extends Notification
{
    public function __construct(private LeaveRequest $leaveRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $leaveRequest = $this->leaveRequest;
        $decision = $leaveRequest->status === 'approved' ? 'approved' : 'rejected';

        $mail = (new MailMessage)
            ->subject("Your leave request was {$decision}")
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your {$leaveRequest->leaveType->name} leave request from {$leaveRequest->start_date->toFormattedDateString()} to {$leaveRequest->end_date->toFormattedDateString()} has been {$decision}.");

        if ($decision === 'rejected' && $leaveRequest->decision_note) {
            $mail->line("Reason: {$leaveRequest->decision_note}");
        }

        return $mail;
    }
}
