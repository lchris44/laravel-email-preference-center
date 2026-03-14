<?php

namespace Lchris44\EmailPreferenceCenter\Listeners;

use Illuminate\Support\Facades\Mail;
use Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend;
use Lchris44\EmailPreferenceCenter\Events\DigestSent;
use Lchris44\EmailPreferenceCenter\Models\PendingDigestItem;

class SendDigestListener
{
    public function handle(DigestReadyToSend $event): void
    {
        $notifiable = $event->notifiable;
        $mailable   = config('email-preferences.digest_mailable');

        if (! $mailable || ! class_exists($mailable)) {
            return;
        }

        $items = PendingDigestItem::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->getKey())
            ->where('category', $event->category)
            ->where('frequency', $event->frequency)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $instance = new $mailable($notifiable, $items, $event->frequency);
        $queue    = config('email-preferences.digest_queue');

        if ($queue !== null) {
            if (method_exists($instance, 'onQueue')) {
                $instance->onQueue($queue);
            }
            Mail::to($notifiable->email)->queue($instance);
        } else {
            Mail::to($notifiable->email)->send($instance);
        }

        PendingDigestItem::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->getKey())
            ->where('category', $event->category)
            ->where('frequency', $event->frequency)
            ->delete();

        event(new DigestSent($notifiable, $event->category, $event->frequency, $items->count()));
    }
}
