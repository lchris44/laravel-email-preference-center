<?php

namespace Lchris44\EmailPreferenceCenter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailPreferenceLog extends Model
{
    public const UPDATED_AT = null; // immutable — no updated_at

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('email-preferences.table_names.logs', 'email_preference_logs');
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }
}
