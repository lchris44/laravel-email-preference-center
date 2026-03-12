<?php

namespace Lchris44\EmailPreferenceCenter\Traits;

use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;

/**
 * Add this trait to any Mailable to enable:
 * - Automatic List-Unsubscribe + List-Unsubscribe-Post header injection
 * - $unsubscribeUrl available in Blade views
 *
 * Usage:
 *   class MyMail extends Mailable {
 *       use BelongsToCategory;
 *       public string $category = 'marketing';
 *   }
 *
 * In your notification's toMail():
 *   return (new MyMail())->withUnsubscribeHeaders($notifiable);
 */
trait BelongsToCategory
{
    public ?string $unsubscribeUrl = null;

    /**
     * Inject List-Unsubscribe headers and populate $unsubscribeUrl
     * for use in Blade views.
     */
    public function withUnsubscribeHeaders(mixed $notifiable): static
    {
        $this->unsubscribeUrl = SignedUnsubscribeUrl::generate($notifiable, $this->category);

        $url = $this->unsubscribeUrl;

        return $this->withSymfonyMessage(function ($message) use ($url) {
            // RFC 8058 — one-click unsubscribe (required by Gmail/Yahoo 2024)
            $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$url}>");
            $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        });
    }
}
