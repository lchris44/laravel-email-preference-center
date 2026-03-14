<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Preference Categories
    |--------------------------------------------------------------------------
    | Define the categories of emails your app sends. Each category can be
    | toggled by users independently. Mark a category as 'required' to
    | prevent users from unsubscribing (e.g. security, billing alerts).
    |
    | Frequency options: 'instant', 'daily', 'weekly', 'never'
    | Omit 'frequency' to make a category on/off only (no batching).
    */
    'categories' => [
        'security' => [
            'label'       => 'Security Alerts',
            'description' => 'Password changes, new logins, and suspicious activity.',
            'required'    => true,
        ],
        'billing' => [
            'label'       => 'Billing & Invoices',
            'description' => 'Receipts, failed payments, and subscription changes.',
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

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    | Override if the defaults conflict with existing tables in your app.
    */
    'table_names' => [
        'preferences' => 'email_preferences',
        'logs'        => 'email_preference_logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Schedule
    |--------------------------------------------------------------------------
    | When true, the package registers its digest send commands automatically
    | via the service provider. Set to false to schedule them manually.
    */
    'auto_schedule' => env('EMAIL_PREFERENCES_AUTO_SCHEDULE', true),

    /*
    |--------------------------------------------------------------------------
    | Digest Schedule
    |--------------------------------------------------------------------------
    | When auto_schedule is true, these cron expressions control when
    | daily and weekly digests are dispatched.
    */
    'digest_schedules' => [
        'daily'  => env('EMAIL_PREFERENCES_DAILY_SCHEDULE', '0 8 * * *'),    // 08:00 daily
        'weekly' => env('EMAIL_PREFERENCES_WEEKLY_SCHEDULE', '0 8 * * 1'),   // 08:00 Monday
    ],

    /*
    |--------------------------------------------------------------------------
    | Unsubscribe URL
    |--------------------------------------------------------------------------
    | The route name used to generate signed unsubscribe URLs injected into
    | email headers and mailable views.
    */
    'unsubscribe_route' => 'email-preferences.unsubscribe',

    /*
    |--------------------------------------------------------------------------
    | Signed URL Expiry
    |--------------------------------------------------------------------------
    | How long (in days) a signed unsubscribe or preference-center URL
    | remains valid after being generated.
    */
    'signed_url_expiry_days' => env('EMAIL_PREFERENCES_URL_EXPIRY_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Digest Mailable
    |--------------------------------------------------------------------------
    | The fully-qualified class name of the Mailable to send when a digest is
    | ready. The class must accept (mixed $notifiable, Collection $items,
    | string $frequency) in its constructor.
    |
    | Defaults to the package's built-in DigestMail. Publish and override:
    |   php artisan vendor:publish --tag=email-preferences-digest
    */
    'digest_mailable' => \Lchris44\EmailPreferenceCenter\Mail\DigestMail::class,

    /*
    |--------------------------------------------------------------------------
    | Digest Queue
    |--------------------------------------------------------------------------
    | Set to a queue name (e.g. 'emails') to dispatch digest mails via the
    | queue worker. null sends synchronously (default).
    */
    'digest_queue' => env('EMAIL_PREFERENCES_DIGEST_QUEUE', null),

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    | Route settings for the preference center page.
    */
    'dashboard' => [
        'enabled'    => env('EMAIL_PREFERENCES_DASHBOARD_ENABLED', true),
        'path'       => env('EMAIL_PREFERENCES_PATH', 'email-preferences'),
        'middleware' => ['web'],
    ],

];
