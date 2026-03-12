<?php

namespace Lchris44\EmailPreferenceCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Lchris44\EmailPreferenceCenter\EmailPreferenceCenterManager;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;

/**
 * @method static CategoryRegistry categories()
 *
 * @see EmailPreferenceCenterManager
 */
class EmailPreferences extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmailPreferenceCenterManager::class;
    }
}
