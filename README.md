# laravel-email-preference-center

Give your users control over which emails they receive and how often, with one-click unsubscribe and a self-service preference center — no login required.

## Features

- Per-category email preferences with required (locked) and optional categories
- Frequency controls per category: instant, daily digest, weekly digest, or never
- One-click unsubscribe via signed URLs, no login required
- Automatic `List-Unsubscribe` and `List-Unsubscribe-Post` headers (Gmail/Yahoo 2024 compliance)
- GDPR consent log — every change recorded with timestamp, IP, and source
- Self-service preference center UI included
- Works with any notifiable model, not just `User`

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

## Installation

```bash
composer require lchris44/laravel-email-preference-center
```

```bash
php artisan vendor:publish --tag=email-preferences-config
php artisan vendor:publish --tag=email-preferences-migrations
php artisan migrate
```

## Setup

### 1. Add the trait to your User model

```php
use Lchris44\EmailPreferenceCenter\Traits\HasEmailPreferences;

class User extends Authenticatable
{
    use HasEmailPreferences;
}
```

### 2. Define your categories

In `config/email-preferences.php`:

```php
'categories' => [
    'security' => [
        'label'       => 'Security Alerts',
        'description' => 'Password changes and new login alerts.',
        'required'    => true, // cannot be unsubscribed
    ],
    'billing' => [
        'label'       => 'Billing & Invoices',
        'description' => 'Receipts and payment notifications.',
        'required'    => true,
    ],
    'digest' => [
        'label'       => 'Activity Digest',
        'description' => 'A summary of your recent activity.',
        'required'    => false,
        'frequency'   => ['instant', 'daily', 'weekly', 'never'],
    ],
    'marketing' => [
        'label'       => 'Product Updates & Promotions',
        'description' => 'New features, offers, and announcements.',
        'required'    => false,
    ],
],
```

## Checking Preferences

```php
// Should this user receive this email?
$user->prefersEmail('marketing'); // true or false

// What frequency has the user chosen?
$user->emailFrequency('digest'); // 'instant', 'daily', 'weekly', or 'never'
```

Always check `prefersEmail()` before sending:

```php
if (! $user->prefersEmail('marketing')) {
    return;
}

Mail::to($user)->send(new MarketingMail($user));
```

## Unsubscribe Links and Headers

Add the `BelongsToCategory` trait to any `Mailable` to inject RFC 8058 unsubscribe headers automatically:

```php
use Lchris44\EmailPreferenceCenter\Traits\BelongsToCategory;

class MarketingMail extends Mailable
{
    use BelongsToCategory;

    public string $category = 'marketing';

    public function __construct(public User $user)
    {
        $this->withUnsubscribeHeaders($user);
    }
}
```

This injects two headers into every outgoing email:

```
List-Unsubscribe: <https://yourapp.com/email-preferences/unsubscribe?...&signature=...>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

Gmail and Apple Mail show an "Unsubscribe" button. Gmail also supports one-click unsubscribe in the background via POST — this is handled automatically.

The `$unsubscribeUrl` variable is available in your Blade view:

```blade
<a href="{{ $unsubscribeUrl }}">Unsubscribe</a>
```

## Preference Center

A self-service page where users manage all their categories at once. Access is via a signed URL — no login required.

### Generate a link

```php
use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;

$url = SignedUnsubscribeUrl::generateForCenter($user);
```

Include this in your email footer so users can manage all preferences at once.

### Customise the view

```bash
php artisan vendor:publish --tag=email-preferences-views
```

This copies the view to `resources/views/vendor/email-preferences/preference-center.blade.php`.

## Digest Batching

For frequency-controlled categories, check the user's frequency before sending and batch accordingly:

```php
$frequency = $user->emailFrequency('digest');

if (! $user->prefersEmail('digest')) {
    return; // opted out
}

if ($frequency === 'instant') {
    Mail::to($user)->send(new DigestMail($user));
    return;
}

// Store for batching — send later via the digest command
PendingDigest::create(['user_id' => $user->id, 'frequency' => $frequency]);
```

When the digest command runs, it fires a `DigestReadyToSend` event for each matching user:

```php
use Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend;

class SendDigestListener
{
    public function handle(DigestReadyToSend $event): void
    {
        // $event->notifiable — the user
        // $event->category   — e.g. 'digest'
        // $event->frequency  — 'daily' or 'weekly'

        Mail::to($event->notifiable)->send(
            new DigestMail($event->notifiable)
        );
    }
}
```

Register your listener in `EventServiceProvider`:

```php
protected $listen = [
    DigestReadyToSend::class => [
        SendDigestListener::class,
    ],
];
```

### Running the command manually

```bash
php artisan email-preferences:send-digests daily
php artisan email-preferences:send-digests weekly
```

### Auto-scheduling

When `auto_schedule = true` in the config, the commands are scheduled automatically:

- Daily digest: every day at 08:00
- Weekly digest: every Monday at 08:00

Override the schedule via environment variables:

```env
EMAIL_PREFERENCES_DAILY_SCHEDULE="0 9 * * *"
EMAIL_PREFERENCES_WEEKLY_SCHEDULE="0 9 * * 1"
```

## Managing Preferences Programmatically

```php
$user->subscribe('marketing');
$user->unsubscribe('marketing');
$user->setEmailFrequency('digest', 'weekly');

// Specify the source of the change (logged in the audit trail)
$user->subscribe('marketing', 'admin');
$user->unsubscribe('marketing', 'admin');
```

## GDPR Consent Log

Every preference change is recorded in an immutable audit log.

```php
// Was the user subscribed to marketing on a given date?
$user->wasSubscribedTo('marketing', '2026-01-01'); // true or false
// Returns false if no history exists — no consent assumed

// Get the most recent record for a category
$log = $user->lastConsentFor('marketing');
$log->action;     // 'subscribed' or 'unsubscribed'
$log->via;        // 'preference_center', 'unsubscribe_link', 'api', 'admin'
$log->ip_address;
$log->created_at;

// Full log for a category
$user->emailPreferenceLogs()->forCategory('marketing')->get();
```

## License

MIT — [Lenos Christodoulou](https://github.com/lchris44)
