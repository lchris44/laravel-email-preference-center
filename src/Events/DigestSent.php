<?php

namespace Lchris44\EmailPreferenceCenter\Events;

class DigestSent
{
    /**
     * Fired after a digest email is successfully sent or queued.
     * Useful for analytics, logging, and monitoring delivery rates.
     *
     * @param  mixed   $notifiable  The model that received the digest
     * @param  string  $category    The digest category (e.g. 'digest')
     * @param  string  $frequency   'instant' | 'daily' | 'weekly'
     * @param  int     $itemCount   Number of items included in the digest
     */
    public function __construct(
        public readonly mixed  $notifiable,
        public readonly string $category,
        public readonly string $frequency,
        public readonly int    $itemCount,
    ) {}
}
