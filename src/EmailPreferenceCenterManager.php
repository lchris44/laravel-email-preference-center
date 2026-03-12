<?php

namespace Lchris44\EmailPreferenceCenter;

use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;

class EmailPreferenceCenterManager
{
    public function __construct(
        protected readonly CategoryRegistry $registry
    ) {}

    public function categories(): CategoryRegistry
    {
        return $this->registry;
    }
}
