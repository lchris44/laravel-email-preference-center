# laravel-email-preference-center

Give your users control over which emails they receive, how often, and make it easy to unsubscribe without losing them entirely.

## Features

- Per-category email preferences with required (locked) categories
- Frequency controls per category: instant, daily digest, weekly digest, or never
- One-click unsubscribe via signed URLs, no login required
- Automatic `List-Unsubscribe` and `List-Unsubscribe-Post` headers for Gmail and Yahoo compliance
- GDPR consent log every change recorded with timestamp, IP, and source
- Publishable preference center UI
- Works with any notifiable model, not just `User`

## Requirements

- PHP 8.2+
- Laravel 10+

## Installation

```bash
composer require lchris44/laravel-email-preference-center
```

```bash
php artisan vendor:publish --tag=email-preferences-config
php artisan vendor:publish --tag=email-preferences-migrations
php artisan migrate
```

## Configuration

Define your email categories in `config/email-preferences.php`:

```php
'categories' => [
    'security' => [
        'label'       => 'Security Alerts',
        'description' => 'Password changes, new logins, suspicious activity.',
        'required'    => true,
    ],
    'billing' => [
        'label'       => 'Billing & Invoices',
        'description' => 'Receipts, failed payments, subscription changes.',
        'required'    => true,
    ],
    'digest' => [
        'label'       => 'Activity Digest',
        'description' => 'A summary of your recent activity.',
        'frequency'   => ['instant', 'daily', 'weekly', 'never'],
    ],
    'marketing' => [
        'label'       => 'Product Updates & Promotions',
        'description' => 'New features, offers, and announcements.',
        'required'    => false,
    ],
],
```

## Usage

### Add the trait to your User model

```php
use Lchris44\EmailPreferenceCenter\Traits\HasEmailPreferences;

class User extends Authenticatable
{
    use HasEmailPreferences;
}
```

### Gate your notifications

```php
class WeeklyDigestNotification extends Notification
{
    public function via($notifiable): array
    {
        return $notifiable->prefersEmail('digest') ? ['mail'] : [];
    }
}
```

### Add unsubscribe support to mailables

```php
use Lchris44\EmailPreferenceCenter\Traits\BelongsToCategory;

class WeeklyDigestMail extends Mailable
{
    use BelongsToCategory;

    public string $category = 'digest';
}
```

This automatically injects `List-Unsubscribe` headers and makes `$unsubscribeUrl` available in your Blade view.

### Add the preference center route

```php
Route::get('/email-preferences', \Lchris44\EmailPreferenceCenter\Http\Controllers\PreferenceCenterController::class)
    ->middleware(['web', 'auth']);

// Unauthenticated access via signed link from email
Route::get('/email-preferences/{token}', \Lchris44\EmailPreferenceCenter\Http\Controllers\PreferenceCenterController::class)
    ->middleware('web');
```

Publish and customise the views:

```bash
php artisan vendor:publish --tag=email-preferences-views
```

## Consent Log

```php
$user->emailPreferenceLogs()->get();

$user->wasSubscribedTo('marketing', '2026-02-01');

$user->lastConsentFor('marketing');
```

## Digest Batching

When a user sets a category to `daily` or `weekly`, their notifications are held and delivered as a single email on schedule.

```bash
php artisan email-preferences:send-digests --frequency=daily
php artisan email-preferences:send-digests --frequency=weekly
```

These run automatically when `auto_schedule = true` in the config.

## License

MIT — [Lenos Christodoulou](https://github.com/lchris44)
