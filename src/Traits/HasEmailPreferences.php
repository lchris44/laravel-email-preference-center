<?php

namespace Lchris44\EmailPreferenceCenter\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Lchris44\EmailPreferenceCenter\Models\EmailPreference;
use Lchris44\EmailPreferenceCenter\Models\EmailPreferenceLog;
use Lchris44\EmailPreferenceCenter\Support\CategoryRegistry;

trait HasEmailPreferences
{
    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function emailPreferences(): MorphMany
    {
        return $this->morphMany(EmailPreference::class, 'notifiable');
    }

    public function emailPreferenceLogs(): MorphMany
    {
        return $this->morphMany(EmailPreferenceLog::class, 'notifiable');
    }

    // ------------------------------------------------------------------
    // Querying preferences
    // ------------------------------------------------------------------

    /**
     * Whether this notifiable wants to receive emails for the given category.
     *
     * Required categories always return true.
     * If no preference row exists, the default is subscribed.
     * A frequency of "never" counts as unsubscribed.
     */
    public function prefersEmail(string $category): bool
    {
        $registry = app(CategoryRegistry::class);

        if ($registry->isRequired($category)) {
            return true;
        }

        $preference = $this->emailPreferences()
            ->forCategory($category)
            ->first();

        if (! $preference) {
            return true; // default: subscribed
        }

        if ($preference->isUnsubscribed()) {
            return false;
        }

        if ($preference->frequency === 'never') {
            return false;
        }

        return true;
    }

    /**
     * Get the current frequency setting for a category.
     * Returns "instant" if no preference row exists.
     */
    public function emailFrequency(string $category): string
    {
        $preference = $this->emailPreferences()
            ->forCategory($category)
            ->first();

        return $preference?->frequency ?? 'instant';
    }

    // ------------------------------------------------------------------
    // Mutating preferences
    // ------------------------------------------------------------------

    public function subscribe(string $category, string $via = 'api'): void
    {
        $registry = app(CategoryRegistry::class);
        $registry->get($category); // throws if category doesn't exist

        $preference = $this->emailPreferences()->forCategory($category)->first();

        if (! $preference) {
            $this->emailPreferences()->create([
                'category'        => $category,
                'frequency'       => 'instant',
                'unsubscribed_at' => null,
            ]);
        } elseif ($preference->isUnsubscribed()) {
            $preference->update(['unsubscribed_at' => null]);
        } else {
            return; // already subscribed — no log needed
        }

        $this->logPreferenceChange($category, 'subscribed', $via);
    }

    public function unsubscribe(string $category, string $via = 'api'): void
    {
        $registry = app(CategoryRegistry::class);

        if ($registry->isRequired($category)) {
            return; // silently ignore — required categories cannot be unsubscribed
        }

        $registry->get($category); // throws if category doesn't exist

        $preference = $this->emailPreferences()->forCategory($category)->first();

        if (! $preference) {
            $this->emailPreferences()->create([
                'category'        => $category,
                'frequency'       => 'instant',
                'unsubscribed_at' => now(),
            ]);
        } elseif ($preference->isSubscribed()) {
            $preference->update(['unsubscribed_at' => now()]);
        } else {
            return; // already unsubscribed — no log needed
        }

        $this->logPreferenceChange($category, 'unsubscribed', $via);
    }

    public function setEmailFrequency(string $category, string $frequency, string $via = 'api'): void
    {
        $registry = app(CategoryRegistry::class);
        $registry->get($category); // throws if category doesn't exist

        if (! $registry->supportsFrequency($category)) {
            return;
        }

        if (! in_array($frequency, $registry->allowedFrequencies($category), true)) {
            throw new \InvalidArgumentException(
                "Frequency [{$frequency}] is not allowed for category [{$category}]."
            );
        }

        $preference = $this->emailPreferences()->forCategory($category)->first();

        if (! $preference) {
            $this->emailPreferences()->create([
                'category'        => $category,
                'frequency'       => $frequency,
                'unsubscribed_at' => null,
            ]);
        } else {
            $old = $preference->frequency;
            $preference->update(['frequency' => $frequency]);

            if ($old === $frequency) {
                return; // nothing changed — no log needed
            }
        }

        $this->logPreferenceChange($category, 'frequency_changed', $via);
    }

    // ------------------------------------------------------------------
    // GDPR consent log helpers
    // ------------------------------------------------------------------

    public function wasSubscribedTo(string $category, string|\DateTimeInterface $date): bool
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;

        // Find the last log entry at or before the given date
        $lastEntry = $this->emailPreferenceLogs()
            ->forCategory($category)
            ->where('created_at', '<=', $date)
            ->latest('created_at')
            ->first();

        if (! $lastEntry) {
            return true; // no history = default subscribed
        }

        return in_array($lastEntry->action, ['subscribed', 'frequency_changed'], true);
    }

    public function lastConsentFor(string $category): ?EmailPreferenceLog
    {
        return $this->emailPreferenceLogs()
            ->forCategory($category)
            ->latest('id')
            ->first();
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    protected function logPreferenceChange(string $category, string $action, string $via): void
    {
        $this->emailPreferenceLogs()->create([
            'category'   => $category,
            'action'     => $action,
            'via'        => $via,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
