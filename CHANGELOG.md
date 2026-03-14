# Changelog

All notable changes to `laravel-email-preference-center` will be documented in this file.

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
