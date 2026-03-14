<?php

namespace Lchris44\EmailPreferenceCenter\Contracts;

interface HasEmailCategory
{
    /**
     * Return the email preference category this notification belongs to.
     *
     * The category must be defined in config('email-preferences.categories').
     *
     * Example:
     *   public function emailCategory(): string
     *   {
     *       return 'marketing';
     *   }
     */
    public function emailCategory(): string;
}
