<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lchris44\EmailPreferenceCenter\Contracts\HasEmailCategory;

class MarketingInterfaceNotification extends Notification implements HasEmailCategory
{
    public function emailCategory(): string
    {
        return 'marketing';
    }

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
