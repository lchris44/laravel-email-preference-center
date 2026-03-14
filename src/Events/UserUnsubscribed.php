<?php

namespace Lchris44\EmailPreferenceCenter\Events;

class UserUnsubscribed
{
    /**
     * Fired specifically when a user unsubscribes from a category.
     * More focused than PreferenceUpdated — useful for CRM sync, analytics,
     * and suppression list updates without filtering on $action.
     *
     * @param  mixed   $notifiable  The model that unsubscribed
     * @param  string  $category    The category unsubscribed from (e.g. 'marketing')
     * @param  string  $via         'api' | 'preference_center' | 'unsubscribe_link' | 'admin'
     */
    public function __construct(
        public readonly mixed  $notifiable,
        public readonly string $category,
        public readonly string $via,
    ) {}
}
