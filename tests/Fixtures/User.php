<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Lchris44\EmailPreferenceCenter\Traits\HasEmailPreferences;

class User extends Model
{
    use HasEmailPreferences;
    use Notifiable;

    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
    protected $attributes = ['email' => 'user@example.com'];
}
