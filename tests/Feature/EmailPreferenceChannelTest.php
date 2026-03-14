<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\Mail;
use Lchris44\EmailPreferenceCenter\Models\PendingDigestItem;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\MailableReturnNotification;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\MarketingAttributeNotification;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\MarketingInterfaceNotification;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\Notifications\UnlabelledNotification;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;
use Mockery;

class EmailPreferenceChannelTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Bind a mock MailChannel that expects send() to be called once.
     */
    private function expectMailSent(): Mockery\MockInterface
    {
        $mock = Mockery::mock(MailChannel::class);
        $mock->shouldReceive('send')->once();
        $this->app->instance(MailChannel::class, $mock);

        return $mock;
    }

    /**
     * Bind a mock MailChannel that expects send() never to be called.
     */
    private function expectNoMailSent(): Mockery\MockInterface
    {
        $mock = Mockery::mock(MailChannel::class);
        $mock->shouldReceive('send')->never();
        $this->app->instance(MailChannel::class, $mock);

        return $mock;
    }

    // -------------------------------------------------------------------------
    // Instant frequency — sends immediately
    // -------------------------------------------------------------------------

    public function test_sends_mail_immediately_when_frequency_is_instant(): void
    {
        $this->expectMailSent();

        $this->user->subscribe('marketing');
        $this->user->notify(new MarketingAttributeNotification());
    }

    public function test_sends_mail_immediately_when_no_preference_row_exists(): void
    {
        $this->expectMailSent();

        // No preference row → defaults to instant
        $this->user->notify(new MarketingAttributeNotification());
    }

    // -------------------------------------------------------------------------
    // Blocked — unsubscribed or frequency = never
    // -------------------------------------------------------------------------

    public function test_drops_notification_when_user_is_unsubscribed(): void
    {
        $this->expectNoMailSent();

        $this->user->unsubscribe('marketing');
        $this->user->notify(new MarketingAttributeNotification());

        $this->assertSame(0, PendingDigestItem::count());
    }

    public function test_drops_notification_when_frequency_is_never(): void
    {
        $this->expectNoMailSent();

        config(['email-preferences.categories.marketing.frequency' => ['instant', 'never']]);
        $this->user->subscribe('marketing');
        $this->user->setEmailFrequency('marketing', 'never');
        $this->user->notify(new MarketingAttributeNotification());

        $this->assertSame(0, PendingDigestItem::count());
    }

    // -------------------------------------------------------------------------
    // Digest frequency — queued into pipeline
    // -------------------------------------------------------------------------

    public function test_queues_into_digest_when_frequency_is_daily(): void
    {
        $this->expectNoMailSent();

        config(['email-preferences.categories.marketing.frequency' => ['instant', 'daily', 'weekly', 'never']]);

        $this->user->subscribe('marketing');
        $this->user->setEmailFrequency('marketing', 'daily');
        $this->user->notify(new MarketingAttributeNotification());

        $this->assertSame(1, PendingDigestItem::count());

        $item = PendingDigestItem::first();
        $this->assertSame('marketing', $item->category);
        $this->assertSame('daily', $item->frequency);
        $this->assertSame(MarketingAttributeNotification::class, $item->type);
    }

    public function test_queues_into_digest_when_frequency_is_weekly(): void
    {
        $this->expectNoMailSent();

        config(['email-preferences.categories.marketing.frequency' => ['instant', 'daily', 'weekly', 'never']]);

        $this->user->subscribe('marketing');
        $this->user->setEmailFrequency('marketing', 'weekly');
        $this->user->notify(new MarketingAttributeNotification());

        $this->assertSame(1, PendingDigestItem::count());
        $this->assertSame('weekly', PendingDigestItem::first()->frequency);
    }

    public function test_digest_item_payload_contains_mail_message_data(): void
    {
        $this->expectNoMailSent();

        config(['email-preferences.categories.marketing.frequency' => ['instant', 'daily', 'weekly']]);

        $this->user->subscribe('marketing');
        $this->user->setEmailFrequency('marketing', 'daily');
        $this->user->notify(new MarketingAttributeNotification());

        $payload = PendingDigestItem::first()->payload;

        $this->assertSame('Monthly update', $payload['subject']);
        $this->assertContains('Here is what is new this month.', $payload['intro_lines']);
    }

    // -------------------------------------------------------------------------
    // Fall-through — no category declared
    // -------------------------------------------------------------------------

    public function test_falls_through_to_mail_when_no_category_declared(): void
    {
        $this->expectMailSent();

        $this->user->notify(new UnlabelledNotification());

        $this->assertSame(0, PendingDigestItem::count());
    }

    public function test_falls_through_to_mail_when_notifiable_lacks_has_email_preferences(): void
    {
        $this->expectMailSent();

        $notifiable = new class {
            use \Illuminate\Notifications\Notifiable;

            public string $email = 'plain@example.com';

            public function routeNotificationForMail(): string
            {
                return $this->email;
            }
        };

        $notifiable->notify(new UnlabelledNotification());
    }

    // -------------------------------------------------------------------------
    // Mailable return — always sends immediately
    // -------------------------------------------------------------------------

    public function test_sends_immediately_when_tomail_returns_a_mailable(): void
    {
        $this->expectMailSent();

        config(['email-preferences.categories.marketing.frequency' => ['instant', 'daily', 'weekly']]);

        $this->user->subscribe('marketing');
        $this->user->setEmailFrequency('marketing', 'daily');

        // Even though frequency is daily, a Mailable cannot be batched
        $this->user->notify(new MailableReturnNotification());

        $this->assertSame(0, PendingDigestItem::count());
    }

    // -------------------------------------------------------------------------
    // Category declaration — interface and config map
    // -------------------------------------------------------------------------

    public function test_channel_works_with_interface_declaration(): void
    {
        $this->expectMailSent();

        $this->user->subscribe('marketing');
        $this->user->notify(new MarketingInterfaceNotification());
    }

    public function test_channel_works_with_config_map_declaration(): void
    {
        $this->expectMailSent();

        config(['email-preferences.notification_categories' => [
            UnlabelledNotification::class => 'marketing',
        ]]);

        $this->user->subscribe('marketing');
        $this->user->notify(new UnlabelledNotification());

        $this->assertSame(0, PendingDigestItem::count());
    }

    public function test_config_map_category_is_blocked_when_user_unsubscribed(): void
    {
        $this->expectNoMailSent();

        config(['email-preferences.notification_categories' => [
            UnlabelledNotification::class => 'marketing',
        ]]);

        $this->user->unsubscribe('marketing');
        $this->user->notify(new UnlabelledNotification());
    }

    // -------------------------------------------------------------------------
    // Required categories — cannot be blocked
    // -------------------------------------------------------------------------

    public function test_required_category_always_sends_regardless_of_preference(): void
    {
        $this->expectMailSent();

        $notification = new class extends \Illuminate\Notifications\Notification {
            public function via(object $notifiable): array
            {
                return ['email-preferences'];
            }

            public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
            {
                return (new \Illuminate\Notifications\Messages\MailMessage)->subject('Security alert');
            }
        };

        // 'security' is required in default config — prefersEmail() always returns true
        config(['email-preferences.notification_categories' => [
            get_class($notification) => 'security',
        ]]);

        $this->user->notify($notification);
    }

    // -------------------------------------------------------------------------
    // Channel registration
    // -------------------------------------------------------------------------

    public function test_email_preferences_channel_is_registered(): void
    {
        $channel = $this->app
            ->make(\Illuminate\Notifications\ChannelManager::class)
            ->channel('email-preferences');

        $this->assertInstanceOf(
            \Lchris44\EmailPreferenceCenter\Channels\EmailPreferenceChannel::class,
            $channel
        );
    }
}
