<?php

namespace Lchris44\EmailPreferenceCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PendingDigestItem extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'category',
        'frequency',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
