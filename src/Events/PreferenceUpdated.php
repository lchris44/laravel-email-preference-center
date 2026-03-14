<?php

namespace Lchris44\EmailPreferenceCenter\Events;

class PreferenceUpdated
{
    /**
     * @param  mixed   $notifiable  The model whose preference changed
     * @param  string  $category    The affected category key (e.g. 'marketing')
     * @param  string  $action      'subscribed' | 'unsubscribed' | 'frequency_changed'
     * @param  string  $via         'api' | 'preference_center' | 'unsubscribe_link' | 'admin'
     */
    public function __construct(
        public readonly mixed  $notifiable,
        public readonly string $category,
        public readonly string $action,
        public readonly string $via,
    ) {}
}
