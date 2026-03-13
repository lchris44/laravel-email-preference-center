<?php

namespace Lchris44\EmailPreferenceCenter\Support;

use Illuminate\Support\Facades\URL;

class SignedUnsubscribeUrl
{
    /**
     * Generate a signed URL for unsubscribing from a category.
     * The URL is valid for the configured number of days.
     */
    public static function generate(mixed $notifiable, string $category): string
    {
        $expiryDays = config('email-preferences.signed_url_expiry_days', 30);

        return URL::temporarySignedRoute(
            config('email-preferences.unsubscribe_route', 'email-preferences.unsubscribe'),
            now()->addDays($expiryDays),
            [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id'   => $notifiable->getKey(),
                'category'        => $category,
            ]
        );
    }

    /**
     * Generate a signed URL for the preference center page.
     * Covers all categories — no specific category in the URL.
     */
    public static function generateForCenter(mixed $notifiable): string
    {
        $expiryDays = config('email-preferences.signed_url_expiry_days', 30);

        return URL::temporarySignedRoute(
            'email-preferences.center',
            now()->addDays($expiryDays),
            [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id'   => $notifiable->getKey(),
            ]
        );
    }

    /**
     * Resolve the notifiable model from route parameters.
     * Returns null if the model cannot be found.
     */
    public static function resolveNotifiable(string $notifiableType, int|string $notifiableId): ?object
    {
        if (! class_exists($notifiableType)) {
            return null;
        }

        return $notifiableType::find($notifiableId);
    }
}
