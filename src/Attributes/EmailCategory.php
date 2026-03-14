<?php

namespace Lchris44\EmailPreferenceCenter\Attributes;

use Attribute;

/**
 * Declare the email preference category for a notification.
 *
 * The category must be defined in config('email-preferences.categories').
 *
 * Usage:
 *   #[EmailCategory('marketing')]
 *   class MarketingNewsletterNotification extends Notification { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class EmailCategory
{
    public function __construct(
        public readonly string $category,
    ) {}
}
