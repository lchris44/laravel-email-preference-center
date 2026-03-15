# Laravel Email Preference Center

A drop-in email preference center for Laravel applications that allows users to manage email categories, notification frequency, and unsubscribe preferences while staying compliant with modern email standards.

Perfect for SaaS apps, newsletters, and platforms that send many types of emails.

[![Latest Version](https://img.shields.io/packagist/v/lchris44/laravel-email-preference-center.svg)](https://packagist.org/packages/lchris44/laravel-email-preference-center)
[![Total Downloads](https://img.shields.io/packagist/dt/lchris44/laravel-email-preference-center.svg)](https://packagist.org/packages/lchris44/laravel-email-preference-center)
[![License](https://img.shields.io/packagist/l/lchris44/laravel-email-preference-center.svg)](https://packagist.org/packages/lchris44/laravel-email-preference-center)

![Demo](docs/demo.gif)

---

## Documentation

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Notification Channel](#notification-channel)
- [Unsubscribe Links and Headers](#unsubscribe-links-and-headers)
- [Preference Center](#preference-center)
- [Digest Batching](#digest-batching)
- [Managing Preferences Programmatically](#managing-preferences-programmatically)
- [GDPR Consent Log](#gdpr-consent-log)

---

# Features

## Email Preference Categories

Define multiple email categories:

- marketing emails
- product updates
- newsletters
- security alerts
- system notifications

Users can enable or disable each category individually.

## Frequency Control

Users can control how often they receive emails.

Supported frequencies:

- Instant
- Daily Digest
- Weekly Digest
- Never

Your application automatically respects these preferences.

## Digest Engine

The package includes a built-in digest system that batches notifications.

Example:

Instead of sending:

```
10 notifications today
```

Users receive:

```
Daily summary email
```

This reduces inbox spam and improves user experience.

## Blade Preference Center UI

The package includes a ready-to-use Blade interface. Developers can add a preference center to their application with minimal effort.

Route:

```
/email-preferences
```

Users can:

- toggle email categories
- select notification frequency
- unsubscribe from emails

## One-Click Unsubscribe

Emails include secure unsubscribe links using signed URLs.

Route:

```
/unsubscribe/{token}
```

Users can instantly unsubscribe without logging in.

## List-Unsubscribe Email Headers

The package automatically adds `List-Unsubscribe` headers to outgoing emails.

Benefits:

- Gmail and other providers show an unsubscribe button
- improves email deliverability
- required for bulk email compliance

## GDPR Consent Logging

The package logs every user preference change.

Example:

```
User enabled marketing emails
User disabled product updates
User unsubscribed from all emails
```

This helps with:

- GDPR compliance
- auditing
- debugging user issues

## Laravel Notification Integration

The package works seamlessly with Laravel notifications. Swap `'mail'` for `'email-preferences'` in your notification's `via()` method and the package handles the rest — checking preferences, routing to digest, or dropping silently.

```php
#[EmailCategory('marketing')]
class NewsletterNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['email-preferences'];
    }
}
```

Or check preferences manually when sending via `Mail::`:

```php
if ($user->prefersEmail('marketing')) {
    Mail::to($user)->send(new MarketingEmail());
}
```

---

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

---

## Installation

Install via Composer:

```bash
composer require lchris44/laravel-email-preference-center
```

Publish configuration and run migrations:

```bash
php artisan vendor:publish --tag=email-preferences-config
php artisan migrate
```

> Migrations run automatically from the package. No need to publish them unless you want to modify the schema.

---

## Setup

### Add the trait to your User model

```php
use Lchris44\EmailPreferenceCenter\Traits\HasEmailPreferences;

class User extends Authenticatable
{
    use HasEmailPreferences;
}
```

### Define your categories

In `config/email-preferences.php`:

```php
'categories' => [
    'security' => [
        'label'       => 'Security Alerts',
        'description' => 'Password changes and new login alerts.',
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

---

## Notification Channel

The package registers an `'email-preferences'` channel. Use it in place of `'mail'` and the package automatically:

- checks whether the user has opted out of that category
- sends immediately if their frequency is `instant`
- queues into the digest pipeline if their frequency is `daily` or `weekly`
- silently drops the notification if they have unsubscribed or set frequency to `never`

### Declare a category on your notification

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

Gmail and Apple Mail will show an "Unsubscribe" button. The `$unsubscribeUrl` variable is also available in your Blade view:

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

---

## Digest Batching

The package handles the entire digest pipeline. If you are using the notification channel, this is wired automatically. To dispatch digest items directly:

```php
use Lchris44\EmailPreferenceCenter\Support\DigestQueue;

DigestQueue::dispatch($user, 'digest', 'your_type', [
    'title' => $event->title,
    'body'  => $event->body,
]);
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

## Example Workflow

1. Application sends a notification
2. Package checks user preferences
3. System determines delivery method:
   - Instant email
   - Daily digest
   - Weekly digest
   - Blocked

This ensures users only receive emails they want.

---

## Use Cases

This package is useful for:

- SaaS applications
- community platforms
- marketplaces
- newsletters
- membership sites

---

## Contributing

Contributions are welcome. If you discover bugs or want to improve the package, feel free to submit a pull request.

---

## License

MIT — [Lenos Christodoulou](https://github.com/lchris44)

---

If you find this package useful, consider starring the repository.
