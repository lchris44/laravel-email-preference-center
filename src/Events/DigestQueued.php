<?php

namespace Lchris44\EmailPreferenceCenter\Events;

class DigestQueued
{
    /**
     * Fired when an item is successfully stored in the digest queue.
     * Not fired for instant-frequency users — those fire DigestReadyToSend instead.
     *
     * @param  mixed   $notifiable  The model that will receive the digest
     * @param  string  $category    The digest category (e.g. 'digest')
     * @param  string  $frequency   'daily' | 'weekly'
     * @param  string  $type        The item type identifier (e.g. 'comment_activity')
     */
    public function __construct(
        public readonly mixed  $notifiable,
        public readonly string $category,
        public readonly string $frequency,
        public readonly string $type,
    ) {}
}
