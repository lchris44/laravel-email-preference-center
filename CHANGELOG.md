# Changelog

All notable changes to `laravel-email-preference-center` will be documented in this file.

## [v1.3.0](https://github.com/lchris44/laravel-email-preference-center/releases/tag/v1.3.0) - 2026-03-18

### Added
- Laravel 13 support
- PHP 8.4 support

## [1.2.0] - 2026-03-15

### Added
- `EmailPreferenceChannel` — a native Laravel notification channel (`'email-preferences'`) that checks user preferences before sending. Replaces manual `prefersEmail()` checks with a single `via` swap.
- `#[EmailCategory]` PHP attribute — declare a notification's category directly on the class
- `HasEmailCategory` interface — alternative to the attribute for runtime category resolution
- `notification_categories` config key — map third-party notification classes to categories without modifying them
- `NotificationCategoryResolver` — resolves category from attribute → interface → config map, in priority order
- `PreferenceUpdated` event — fired on every subscribe, unsubscribe, and frequency change, carrying notifiable, category, action, and via
- `UserUnsubscribed` event — focused event fired specifically on unsubscribe, useful for CRM sync and suppression lists
- `DigestQueued` event — fired when an item is stored for daily/weekly batching (not fired for instant)
- `DigestSent` event — fired after a digest email is sent or queued, carrying item count
- `email-preferences:seed` artisan command — seeds default `EmailPreference` rows for existing notifiables with `--model`, `--frequency`, and `--force` options
- 26 new tests covering channel routing, category resolution, event dispatch, and seeder behaviour (117 total)

## [1.1.0] - 2026-03-14

### Added
- `DigestQueue::dispatch()` helper - one call routes notifiables to instant send or daily/weekly batch queue
- `PendingDigestItem` model and migration included in the package (polymorphic, no app-level setup needed)
- `SendDigestListener` built into the package and auto-registered - no `AppServiceProvider` wiring required
- Default `DigestMail` mailable and digest view, publishable via `--tag=email-preferences-digest`
- `digest_mailable` config key to specify which mailable the listener sends
- `digest_queue` config key (`EMAIL_PREFERENCES_DIGEST_QUEUE`) to dispatch digest emails via queue worker
- Migrations auto-loaded via `loadMigrationsFrom` - `vendor:publish` for migrations is now optional

### Fixed
- Preference center incorrectly showed stored frequency (e.g. `instant`) instead of `never` for unsubscribed users on frequency categories
- Saving frequency categories from the preference center always unsubscribed due to missing checkbox field - now driven by dropdown value
