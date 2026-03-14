<?php

namespace Lchris44\EmailPreferenceCenter\Channels;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Lchris44\EmailPreferenceCenter\Support\DigestQueue;
use Lchris44\EmailPreferenceCenter\Support\NotificationCategoryResolver;

class EmailPreferenceChannel
{
    public function __construct(
        protected NotificationCategoryResolver $resolver,
    ) {}

    /**
     * Send the notification through the email preference pipeline.
     *
     * Flow:
     *   1. Resolve category from the notification (attribute → interface → config map)
     *   2. If no category → fall through to normal mail (never breaks uncategorised notifications)
     *   3. Check notifiable has HasEmailPreferences trait
     *   4. Check prefersEmail($category) — blocks unsubscribed / required-ignored
     *   5. Route by frequency:
     *      - instant → send via MailChannel (same as the 'mail' channel)
     *      - daily / weekly → queue into DigestQueue for batching
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        $category = $this->resolver->resolve($notification);

        // No category declared — behave exactly like the 'mail' channel
        if ($category === null) {
            $this->mailChannel()->send($notifiable, $notification);
            return;
        }

        // Notifiable doesn't use HasEmailPreferences — fall through to normal mail
        if (! method_exists($notifiable, 'prefersEmail')) {
            $this->mailChannel()->send($notifiable, $notification);
            return;
        }

        // Blocked: unsubscribed or frequency set to 'never'
        if (! $notifiable->prefersEmail($category)) {
            return;
        }

        $frequency = $notifiable->emailFrequency($category);

        // Instant: send immediately via the standard mail pipeline
        if ($frequency === 'instant') {
            $this->mailChannel()->send($notifiable, $notification);
            return;
        }

        // Daily / weekly: serialize and queue for digest batching
        $message = $notification->toMail($notifiable);

        // If the notification returns a raw Mailable we cannot batch it —
        // fall back to sending it immediately.
        if (! $message instanceof MailMessage) {
            $this->mailChannel()->send($notifiable, $notification);
            return;
        }

        DigestQueue::dispatch(
            notifiable: $notifiable,
            category:   $category,
            type:       get_class($notification),
            payload:    $this->serializeMailMessage($message),
        );
    }

    /**
     * Extract the relevant display data from a MailMessage for digest storage.
     *
     * The DigestMail view receives these as items in $items collection,
     * so it can render each notification's subject, body lines, and CTA.
     */
    /**
     * Resolve MailChannel fresh from the container on each send.
     * This allows tests to bind a mock via $this->app->instance(MailChannel::class, $mock).
     */
    protected function mailChannel(): MailChannel
    {
        return app(MailChannel::class);
    }

    protected function serializeMailMessage(MailMessage $message): array
    {
        return array_filter([
            'subject'      => $message->subject,
            'level'        => $message->level,        // info | success | error
            'greeting'     => $message->greeting,
            'intro_lines'  => $message->introLines,
            'outro_lines'  => $message->outroLines,
            'action_text'  => $message->actionText,
            'action_url'   => $message->actionUrl,
            'salutation'   => $message->salutation,
        ]);
    }
}
