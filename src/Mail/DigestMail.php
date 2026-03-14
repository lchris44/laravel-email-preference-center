<?php

namespace Lchris44\EmailPreferenceCenter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;
use Lchris44\EmailPreferenceCenter\Traits\BelongsToCategory;

class DigestMail extends Mailable
{
    use BelongsToCategory, Queueable, SerializesModels;

    public string $category = 'digest';

    public string $preferenceCenterUrl;

    public function __construct(
        public mixed $notifiable,
        public Collection $items,
        public string $frequency,
    ) {
        $this->withUnsubscribeHeaders($notifiable);
        $this->preferenceCenterUrl = SignedUnsubscribeUrl::generateForCenter($notifiable);
    }

    public function build(): static
    {
        $subject = match ($this->frequency) {
            'weekly'  => 'Your Weekly Digest',
            'daily'   => 'Your Daily Digest',
            default   => 'Your Latest Update',
        };

        return $this->subject($subject)
                    ->view('email-preferences::emails.digest');
    }
}
