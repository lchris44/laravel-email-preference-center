# laravel-email-preference-center

Give your users control over which emails they receive and how often, with one-click unsubscribe and a self-service preference center. No login required.

[![Latest Version](https://img.shields.io/packagist/v/lchris44/laravel-email-preference-center.svg)](https://packagist.org/packages/lchris44/laravel-email-preference-center)
[![Total Downloads](https://img.shields.io/packagist/dt/lchris44/laravel-email-preference-center.svg)](https://packagist.org/packages/lchris44/laravel-email-preference-center)
[![License](https://img.shields.io/packagist/l/lchris44/laravel-email-preference-center.svg)](https://packagist.org/packages/lchris44/laravel-email-preference-center)

![Demo](docs/demo.gif)

## Documentation

- [Installation](#installation)
- [Setup](#setup)
- [Notification Channel](#notification-channel)
- [Checking Preferences Manually](#checking-preferences-manually)
- [Unsubscribe Links and Headers](#unsubscribe-links-and-headers)
- [Preference Center](#preference-center)
- [Digest Batching](#digest-batching)
- [Managing Preferences Programmatically](#managing-preferences-programmatically)
- [GDPR Consent Log](#gdpr-consent-log)

## Features

- **Notification channel** — swap `'mail'` for `'email-preferences'` and the package handles the rest
- **Category declaration** via PHP attribute, interface, or config map — category lives with the notification
- Per-category email preferences with required (locked) and optional categories
- Frequency controls per category: instant, daily digest, weekly digest, or never
- One-click unsubscribe via signed URLs, no login required
- Automatic `List-Unsubscribe` and `List-Unsubscribe-Post` headers (Gmail/Yahoo 2024 compliance)
- Built-in digest batching — one call handles instant send and daily/weekly queuing
- Queue support for digest emails
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
php artisan migrate
```

> Migrations run automatically from the package. No need to publish them unless you want to modify the schema.

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

---

## Notification Channel

The package registers an `'email-preferences'` notification channel. Swap it in place of `'mail'` and the package automatically:

- Checks whether the user has opted out of that category
- Sends immediately if their frequency is `instant`
- Queues into the digest pipeline if their frequency is `daily` or `weekly`
- Silently drops the notification if they've unsubscribed or set frequency to `never`

You write zero preference-checking logic.

### Declare a category on your notification

Choose whichever approach fits your style. All three are equivalent.

**Option 1 — PHP attribute** *(recommended)*

```php
use Lchris44\EmailPreferenceCenter\Attributes\EmailCategory;

#[EmailCategory('marketing')]
class NewsletterNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['email-preferences'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('What\'s new this month')
            ->line('Here is your monthly update...');
    }
}
```

**Option 2 — Interface**

```php
use Lchris44\EmailPreferenceCenter\Contracts\HasEmailCategory;

class NewsletterNotification extends Notification implements HasEmailCategory
{
    public function emailCategory(): string
    {
        return 'marketing';
    }

    public function via(object $notifiable): array
    {
        return ['email-preferences'];
    }
}
```

**Option 3 — Config map** *(for third-party notifications you cannot modify)*

```php
// config/email-preferences.php
'notification_categories' => [
    \Cashier\Notifications\PaymentFailed::class => 'billing',
    \App\Notifications\NewsletterNotification::class => 'marketing',
],
```

### How the channel routes each notification

```
$user->notify(new NewsletterNotification())
              │
              ▼
    EmailPreferenceChannel
              │
              ├─ No category declared? ──────────────► send via mail (unchanged)
              │
              ├─ prefersEmail('marketing') = false? ──► drop silently
              │
              ├─ frequency = 'instant' ───────────────► send via mail immediately
              │
              └─ frequency = 'daily' / 'weekly' ──────► queue into digest pipeline
```

### Notifications without a category

If a notification has no category declared (no attribute, interface, or config map entry), the channel falls through to normal mail sending. Existing notifications without a category are never broken.

### Notifications that return a Mailable

If `toMail()` returns a `Mailable` instance instead of a `MailMessage`, the notification is always sent immediately — `Mailable` instances cannot be serialized into the digest pipeline.

---

## Checking Preferences Manually

If you are sending via `Mail::to()->send()` directly rather than through the notification channel, check preferences yourself before sending:

```php
$user->prefersEmail('marketing');       // true or false
$user->emailFrequency('digest');        // 'instant', 'daily', 'weekly', or 'never'
```

```php
if (! $user->prefersEmail('marketing')) {
    return;
}

Mail::to($user)->send(new MarketingMail($user));
```

---

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

Gmail and Apple Mail show an "Unsubscribe" button. One-click POST unsubscribe is handled automatically.

The `$unsubscribeUrl` variable is available in your Blade view:

```blade
<a href="{!! $unsubscribeUrl !!}">Unsubscribe</a>
```

---

## Preference Center

A self-service page where users manage all their categories at once. Access is via a signed URL — no login required.

### Generate a link

```php
use Lchris44\EmailPreferenceCenter\Support\SignedUnsubscribeUrl;

$url = SignedUnsubscribeUrl::generateForCenter($user);
```

### Customise the view

```bash
php artisan vendor:publish --tag=email-preferences-views
```

This copies the view to `resources/views/vendor/email-preferences/preference-center.blade.php`.

---

## Digest Batching

The package handles the entire digest pipeline. One call routes each user to an immediate send or a scheduled batch based on their chosen frequency.

> If you are using the **notification channel**, the digest pipeline is wired automatically — `DigestQueue::dispatch()` is called for you when a user's frequency is `daily` or `weekly`. The steps below apply when dispatching digest items directly, without a notification.

### 1. Set the mailable

In `config/email-preferences.php`:

```php
'digest_mailable' => \Lchris44\EmailPreferenceCenter\Mail\DigestMail::class,
```

The mailable must accept `(mixed $notifiable, Collection $items, string $frequency)`.

Publish the default mail and view to customise them:

```bash
php artisan vendor:publish --tag=email-preferences-digest
```

### 2. Dispatch items from your listener

```php
use Lchris44\EmailPreferenceCenter\Support\DigestQueue;

DigestQueue::dispatch($user, 'digest', 'your_type', [
    'title' => $event->title,
    'body'  => $event->body,
]);
```

`DigestQueue::dispatch()` handles everything:
- Skips users who have opted out
- **Instant** — saves the item and fires `DigestReadyToSend` immediately
- **Daily / Weekly** — saves the item; the scheduled command sends it later

### Queue support

```php
// config/email-preferences.php
'digest_queue' => env('EMAIL_PREFERENCES_DIGEST_QUEUE', null),
```

```env
EMAIL_PREFERENCES_DIGEST_QUEUE=emails
```

### Running digests manually

```bash
php artisan email-preferences:send-digests daily
php artisan email-preferences:send-digests weekly
```

### Auto-scheduling

When `auto_schedule = true`, the commands are scheduled automatically:

- Daily: every day at 08:00
- Weekly: every Monday at 08:00

Override via environment variables:

```env
EMAIL_PREFERENCES_DAILY_SCHEDULE="0 9 * * *"
EMAIL_PREFERENCES_WEEKLY_SCHEDULE="0 9 * * 1"
```

---

## Managing Preferences Programmatically

```php
$user->subscribe('marketing');
$user->unsubscribe('marketing');
$user->setEmailFrequency('digest', 'weekly');

// Specify the source of the change (recorded in the audit log)
$user->subscribe('marketing', 'admin');
$user->unsubscribe('marketing', 'admin');
```

---

## GDPR Consent Log

Every preference change is recorded in an immutable audit log.

```php
$user->wasSubscribedTo('marketing', '2026-01-01'); // true or false

$log = $user->lastConsentFor('marketing');
$log->action;     // 'subscribed' or 'unsubscribed'
$log->via;        // 'preference_center', 'unsubscribe_link', 'api', 'admin'
$log->ip_address;
$log->created_at;

$user->emailPreferenceLogs()->forCategory('marketing')->get();
```

---

## License

MIT - [Lenos Christodoulou](https://github.com/lchris44)
