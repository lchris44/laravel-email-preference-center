<?php

namespace Lchris44\EmailPreferenceCenter\Events;

class DigestReadyToSend
{
    public function __construct(
        public readonly mixed  $notifiable,
        public readonly string $category,
        public readonly string $frequency,
    ) {}
}
