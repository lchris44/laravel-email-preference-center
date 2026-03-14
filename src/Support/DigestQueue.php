<?php

namespace Lchris44\EmailPreferenceCenter\Support;

use Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend;
use Lchris44\EmailPreferenceCenter\Models\PendingDigestItem;

class DigestQueue
{
    /**
     * Queue a digest item for the given notifiable.
     *
     * - If the notifiable has opted out of the category, the call is a no-op.
     * - If their frequency is 'instant', the item is saved and DigestReadyToSend
     *   is fired immediately so the configured mailable is sent right away.
     * - For 'daily' / 'weekly', the item is saved for the next scheduled batch.
     */
    public static function dispatch(
        mixed $notifiable,
        string $category,
        string $type,
        array $payload,
    ): void {
        if (! $notifiable->prefersEmail($category)) {
            return;
        }

        $frequency = $notifiable->emailFrequency($category);

        PendingDigestItem::create([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id'   => $notifiable->getKey(),
            'category'        => $category,
            'frequency'       => $frequency,
            'type'            => $type,
            'payload'         => $payload,
        ]);

        if ($frequency === 'instant') {
            event(new DigestReadyToSend($notifiable, $category, $frequency));
        }
    }
}
