<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lchris44\EmailPreferenceCenter\Attributes\EmailCategory;

#[EmailCategory('marketing')]
class MarketingAttributeNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['email-preferences'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Monthly update')
            ->line('Here is what is new this month.');
    }
}
