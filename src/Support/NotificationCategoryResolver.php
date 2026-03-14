<?php

namespace Lchris44\EmailPreferenceCenter\Support;

use Lchris44\EmailPreferenceCenter\Attributes\EmailCategory;
use Lchris44\EmailPreferenceCenter\Contracts\HasEmailCategory;
use ReflectionClass;

class NotificationCategoryResolver
{
    /**
     * Resolve the email preference category from a notification instance.
     *
     * Resolution order:
     *   1. #[EmailCategory('...')] attribute on the notification class
     *   2. HasEmailCategory interface → emailCategory() method
     *   3. config('email-preferences.notification_categories') class map
     *   4. null — no category declared, channel will fall through to normal mail
     */
    public function resolve(object $notification): ?string
    {
        // 1. PHP attribute
        $reflection = new ReflectionClass($notification);
        $attributes = $reflection->getAttributes(EmailCategory::class);

        if (! empty($attributes)) {
            return $attributes[0]->newInstance()->category;
        }

        // 2. Interface
        if ($notification instanceof HasEmailCategory) {
            return $notification->emailCategory();
        }

        // 3. Config class map
        $map = config('email-preferences.notification_categories', []);
        $class = get_class($notification);

        return $map[$class] ?? null;
    }
}
