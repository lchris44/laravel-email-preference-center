<?php

namespace Lchris44\EmailPreferenceCenter\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Lchris44\EmailPreferenceCenter\Events\DigestReadyToSend;
use Lchris44\EmailPreferenceCenter\Listeners\SendDigestListener;
use Lchris44\EmailPreferenceCenter\Mail\DigestMail;
use Lchris44\EmailPreferenceCenter\Models\PendingDigestItem;
use Lchris44\EmailPreferenceCenter\Support\DigestQueue;
use Lchris44\EmailPreferenceCenter\Tests\Fixtures\User;
use Lchris44\EmailPreferenceCenter\Tests\TestCase;

class DigestBatchingTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
    }

    // -------------------------------------------------------------------------
    // DigestQueue::dispatch()
    // -------------------------------------------------------------------------

    public function test_dispatch_skips_unsubscribed_user(): void
    {
        Event::fake();

        $this->user->unsubscribe('digest');

        DigestQueue::dispatch($this->user, 'digest', 'alert', ['title' => 'Test']);

        $this->assertSame(0, PendingDigestItem::count());
        Event::assertNotDispatched(DigestReadyToSend::class);
    }

    public function test_dispatch_stores_item_for_daily_frequency(): void
    {
        Event::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'daily');

        DigestQueue::dispatch($this->user, 'digest', 'alert', ['title' => 'EUR/USD alert']);

        $this->assertSame(1, PendingDigestItem::count());

        $item = PendingDigestItem::first();
        $this->assertSame(User::class, $item->notifiable_type);
        $this->assertSame($this->user->id, $item->notifiable_id);
        $this->assertSame('digest', $item->category);
        $this->assertSame('daily', $item->frequency);
        $this->assertSame('alert', $item->type);
        $this->assertSame(['title' => 'EUR/USD alert'], $item->payload);

        Event::assertNotDispatched(DigestReadyToSend::class);
    }

    public function test_dispatch_stores_item_for_weekly_frequency(): void
    {
        Event::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'weekly');

        DigestQueue::dispatch($this->user, 'digest', 'alert', ['title' => 'Weekly alert']);

        $this->assertSame(1, PendingDigestItem::count());
        Event::assertNotDispatched(DigestReadyToSend::class);
    }

    public function test_dispatch_stores_item_and_fires_event_for_instant_frequency(): void
    {
        Event::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'instant');

        DigestQueue::dispatch($this->user, 'digest', 'alert', ['title' => 'Instant alert']);

        $this->assertSame(1, PendingDigestItem::count());

        Event::assertDispatched(DigestReadyToSend::class, function (DigestReadyToSend $e) {
            return $e->notifiable->id === $this->user->id
                && $e->category === 'digest'
                && $e->frequency === 'instant';
        });
    }

    public function test_dispatch_stores_correct_payload(): void
    {
        Event::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'daily');

        $payload = ['title' => 'Test', 'body' => 'Body text', 'instrument' => 'EUR/USD', 'type' => 'opportunity'];

        DigestQueue::dispatch($this->user, 'digest', 'market_alert', $payload);

        $this->assertSame($payload, PendingDigestItem::first()->payload);
    }

    // -------------------------------------------------------------------------
    // SendDigestListener
    // -------------------------------------------------------------------------

    public function test_listener_sends_mail_and_deletes_items(): void
    {
        Mail::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'daily');

        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'Test alert'],
        ]);

        $listener = new SendDigestListener();
        $listener->handle(new DigestReadyToSend($this->user, 'digest', 'daily'));

        Mail::assertSent(DigestMail::class, fn ($mail) => $mail->hasTo($this->user->email));
        $this->assertSame(0, PendingDigestItem::count());
    }

    public function test_listener_does_nothing_when_no_pending_items(): void
    {
        Mail::fake();

        $listener = new SendDigestListener();
        $listener->handle(new DigestReadyToSend($this->user, 'digest', 'daily'));

        Mail::assertNothingSent();
    }

    public function test_listener_does_nothing_when_mailable_not_configured(): void
    {
        Mail::fake();

        config(['email-preferences.digest_mailable' => null]);

        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'Test'],
        ]);

        $listener = new SendDigestListener();
        $listener->handle(new DigestReadyToSend($this->user, 'digest', 'daily'));

        Mail::assertNothingSent();
        $this->assertSame(1, PendingDigestItem::count());
    }

    public function test_listener_only_deletes_items_for_matching_notifiable_and_frequency(): void
    {
        Mail::fake();

        $otherUser = User::create(['name' => 'Other User', 'email' => 'other@example.com']);

        // Item for this user — daily
        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'User daily'],
        ]);

        // Item for other user — should NOT be deleted
        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $otherUser->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'Other user daily'],
        ]);

        // Item for this user — weekly — should NOT be deleted
        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'weekly',
            'type'            => 'alert',
            'payload'         => ['title' => 'User weekly'],
        ]);

        $listener = new SendDigestListener();
        $listener->handle(new DigestReadyToSend($this->user, 'digest', 'daily'));

        $this->assertSame(2, PendingDigestItem::count());
    }

    // -------------------------------------------------------------------------
    // Queue support
    // -------------------------------------------------------------------------

    public function test_listener_queues_mail_when_digest_queue_configured(): void
    {
        Mail::fake();

        config(['email-preferences.digest_queue' => 'emails']);

        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'Queued alert'],
        ]);

        $listener = new SendDigestListener();
        $listener->handle(new DigestReadyToSend($this->user, 'digest', 'daily'));

        Mail::assertQueued(DigestMail::class, fn ($mail) => $mail->hasTo($this->user->email));
        Mail::assertNotSent(DigestMail::class);
        $this->assertSame(0, PendingDigestItem::count());
    }

    public function test_listener_sends_synchronously_when_digest_queue_is_null(): void
    {
        Mail::fake();

        config(['email-preferences.digest_queue' => null]);

        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'Sync alert'],
        ]);

        $listener = new SendDigestListener();
        $listener->handle(new DigestReadyToSend($this->user, 'digest', 'daily'));

        Mail::assertSent(DigestMail::class, fn ($mail) => $mail->hasTo($this->user->email));
        Mail::assertNotQueued(DigestMail::class);
    }

    // -------------------------------------------------------------------------
    // Service provider auto-registration
    // -------------------------------------------------------------------------

    public function test_service_provider_auto_registers_send_digest_listener(): void
    {
        Mail::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'daily');

        PendingDigestItem::create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
            'category'        => 'digest',
            'frequency'       => 'daily',
            'type'            => 'alert',
            'payload'         => ['title' => 'Auto-registered test'],
        ]);

        // Fire the event directly — the package's listener should handle it automatically
        event(new DigestReadyToSend($this->user, 'digest', 'daily'));

        Mail::assertSent(DigestMail::class);
        $this->assertSame(0, PendingDigestItem::count());
    }

    // -------------------------------------------------------------------------
    // Full flow: DigestQueue instant → email sent end-to-end
    // -------------------------------------------------------------------------

    public function test_full_instant_flow_sends_email_and_leaves_no_pending_items(): void
    {
        Mail::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'instant');

        DigestQueue::dispatch($this->user, 'digest', 'market_alert', [
            'title' => 'EUR/USD breaks resistance',
            'body'  => 'Strong buy signal.',
        ]);

        Mail::assertSent(DigestMail::class, fn ($mail) => $mail->hasTo($this->user->email));
        $this->assertSame(0, PendingDigestItem::count());
    }

    public function test_full_daily_flow_queues_item_and_sends_on_command(): void
    {
        Mail::fake();

        $this->user->subscribe('digest');
        $this->user->setEmailFrequency('digest', 'daily');

        DigestQueue::dispatch($this->user, 'digest', 'market_alert', ['title' => 'Daily alert']);

        Mail::assertNothingSent();
        $this->assertSame(1, PendingDigestItem::count());

        $this->artisan('email-preferences:send-digests daily')->assertSuccessful();

        Mail::assertSent(DigestMail::class);
        $this->assertSame(0, PendingDigestItem::count());
    }
}
