<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Lchris44\EmailPreferenceCenter\Attributes\EmailCategory;

/**
 * A notification whose toMail() returns a Mailable instead of a MailMessage.
 * The channel must fall back to immediate sending — Mailables cannot be batched.
 */
#[EmailCategory('marketing')]
class MailableReturnNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['email-preferences'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return new class extends Mailable {
            public function build(): static
            {
                return $this->subject('Mailable notification')->html('<p>Hello</p>');
            }
        };
    }
}
