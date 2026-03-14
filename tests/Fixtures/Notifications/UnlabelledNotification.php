<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A notification with no category declared — used to test fall-through behaviour.
 */
class UnlabelledNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['email-preferences'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('No category notification')
            ->line('This notification has no email category declared.');
    }
}
